<?php

namespace App\Services;

use App\Models\User;
use App\Support\Format;

/**
 * Inti ForeCash: burn rate & peringatan dini.
 * Port 1:1 dari lib/forecast.ts (Next.js), yang sendirinya port dari forecast() versi PHP.
 *
 * hitung() sengaja "murni" (tidak mengambil data sendiri) supaya bisa diuji dengan data contoh.
 * untuk() mengambil data dari database lalu memanggil hitung().
 * Semua tanggal berupa teks 'YYYY-MM-DD'.
 */
class Forecast
{
    public const HARI_BURN_MAKS = 14;
    public const HARI_BURN_MIN = 3;
    /** Hari ke depan yang dipakai untuk rencana kalau belum ada jadwal pemasukan. */
    public const HARI_RENCANA_TANPA_JADWAL = 30;

    /**
     * @param  array<int, array{tanggal: string, nominal: float}>  $pengeluaran  pengeluaran > hari ini - 14 (tanpa transfer)
     * @param  array<int, array{id: int, sumber: string, nominal: float, tanggal_perkiraan: string, status: string}>  $jadwal
     * @param  array<int, array{tanggal: string, nominal: float}>  $rencana  rencana pengeluaran yang belum dibayar
     */
    public static function hitung(
        string $hariIni,
        float $saldo,
        ?string $pengeluaranPertama,
        array $pengeluaran,
        array $jadwal,
        array $rencana = [],
    ): array {
        // burn rate = rata-rata pengeluaran harian 14 hari terakhir (pengguna baru: min 3, maks 14 hari)
        $hari = self::HARI_BURN_MAKS;
        if ($pengeluaranPertama) {
            $sejak = abs(self::selisihHari($pengeluaranPertama, $hariIni)) + 1;
            $hari = max(self::HARI_BURN_MIN, min(self::HARI_BURN_MAKS, $sejak));
        }
        $batasBawah = self::tambahHari($hariIni, -$hari);
        $keluar = 0.0;
        foreach ($pengeluaran as $p) {
            if ($p['tanggal'] > $batasBawah) {
                $keluar += (float) $p['nominal'];
            }
        }
        $burn = $keluar / $hari;

        // jadwal yang masih menunggu, urut tanggal
        $menunggu = array_values(array_filter($jadwal, fn ($j) => $j['status'] === 'menunggu'));
        usort($menunggu, fn ($a, $b) => strcmp($a['tanggal_perkiraan'], $b['tanggal_perkiraan']) ?: $a['id'] <=> $b['id']);
        $next = null;
        foreach ($menunggu as $j) {
            if ($j['tanggal_perkiraan'] >= $hariIni) {
                $next = $j;
                break;
            }
        }
        $terlambat = array_values(array_filter($menunggu, fn ($j) => $j['tanggal_perkiraan'] < $hariIni));

        // rencana yang jatuh sebelum uang berikutnya datang mengurangi saldo bebas
        $batasRencana = $next ? $next['tanggal_perkiraan'] : self::tambahHari($hariIni, self::HARI_RENCANA_TANPA_JADWAL);
        $rencanaDulu = 0.0;
        foreach ($rencana as $r) {
            if ($r['tanggal'] < $batasRencana) {
                $rencanaDulu += (float) $r['nominal'];
            }
        }
        $saldoBebas = $saldo - $rencanaDulu;

        $runway = $burn > 0 ? (int) floor(max(0, $saldoBebas) / $burn) : null;
        $habis = $runway !== null ? self::tambahHari($hariIni, $runway) : null;

        $sisaHari = $next ? self::selisihHari($hariIni, $next['tanggal_perkiraan']) : null;
        $batasAman = $next && $sisaHari > 0 ? max(0, $saldoBebas) / $sisaHari : null;

        // status: aman / waspada (kurang 1-3 hari) / kritis (kurang > 3 hari atau saldo habis)
        $status = 'aman';
        if ($saldo <= 0 || ($rencanaDulu > 0 && $saldoBebas <= 0)) {
            $status = 'kritis';
        } elseif ($runway !== null && $sisaHari !== null && $runway < $sisaHari) {
            $status = $sisaHari - $runway > 3 ? 'kritis' : 'waspada';
        }
        if ($status === 'aman' && count($terlambat) > 0) {
            $status = 'waspada';
        }

        return compact('saldo', 'burn', 'hari', 'runway', 'habis', 'next', 'sisaHari', 'batasAman', 'terlambat', 'rencanaDulu', 'status');
    }

    /** Ambil data user dari database lalu hitung (pengganti ambilForecast() di lib/data.ts). */
    public static function untuk(User $user, ?float $saldo = null): array
    {
        $hari = Format::hariIni();
        $saldo ??= (float) $user->wallets->sum(fn ($w) => $w->saldo());

        $pengeluaran = $user->transactions()->where('jenis', 'pengeluaran');

        return self::hitung(
            hariIni: $hari,
            saldo: $saldo,
            pengeluaranPertama: (clone $pengeluaran)->min('tanggal'),
            pengeluaran: (clone $pengeluaran)
                ->where('tanggal', '>', self::tambahHari($hari, -self::HARI_BURN_MAKS))
                ->toBase()->get(['tanggal', 'nominal'])
                ->map(fn ($p) => ['tanggal' => (string) $p->tanggal, 'nominal' => (float) $p->nominal])->all(),
            jadwal: $user->incomeSchedules()->where('status', 'menunggu')
                ->toBase()->get(['id', 'sumber', 'nominal', 'tanggal_perkiraan', 'status'])
                ->map(fn ($j) => [
                    'id' => (int) $j->id, 'sumber' => $j->sumber, 'nominal' => (float) $j->nominal,
                    'tanggal_perkiraan' => (string) $j->tanggal_perkiraan, 'status' => $j->status,
                ])->all(),
            rencana: $user->rencana()->where('status', 'rencana')
                ->toBase()->get(['tanggal', 'nominal'])
                ->map(fn ($r) => ['tanggal' => (string) $r->tanggal, 'nominal' => (float) $r->nominal])->all(),
        );
    }

    public static function tambahHari(string $tgl, int $n): string
    {
        return gmdate('Y-m-d', strtotime($tgl . ' 00:00:00 UTC') + $n * 86400);
    }

    /** Selisih hari dari a ke b (b - a). */
    public static function selisihHari(string $a, string $b): int
    {
        return (int) round((strtotime($b . ' 00:00:00 UTC') - strtotime($a . ' 00:00:00 UTC')) / 86400);
    }
}
