<?php

namespace App\Support;

use App\Models\User;
use App\Services\Forecast;
use Illuminate\Support\Facades\DB;

/**
 * Gamifikasi: streak, badge bertingkat, poin, level, tantangan mingguan.
 * Port dari lib/gamify.ts versi Next.js.
 *
 * Badge didapat dari KEBIASAAN NYATA (hemat, patuh anggaran, menabung, patungan, melunasi),
 * bukan sekadar membuka aplikasi. Setiap badge punya 3 tingkat: Perunggu, Perak, Emas.
 * hitung() murni; untuk() mengambil data dari database.
 */
class Gamify
{
    public const LEVELS = [
        [0, 'Pemula'], [300, 'Hemat Muda'], [800, 'Pengatur Cerdas'], [1600, 'Master Budget'], [2800, 'Legenda ForeCash'],
    ];
    public const TINGKAT = ['Belum', 'Perunggu', 'Perak', 'Emas'];
    /** poin kumulatif per tingkat badge */
    public const POIN_TINGKAT = [0, 50, 150, 350];
    public const BATAS_TANTANGAN = 200000;

    /** Berapa hari berturut-turut mencatat transaksi (boleh belum mencatat hari ini). */
    public static function hitungStreak(array $tanggal, string $hariIni): int
    {
        $ada = array_flip($tanggal);
        $d = isset($ada[$hariIni]) ? $hariIni : Forecast::tambahHari($hariIni, -1);
        $n = 0;
        while (isset($ada[$d])) {
            $n++;
            $d = Forecast::tambahHari($d, -1);
        }

        return $n;
    }

    /** Rekor streak terpanjang yang pernah dicapai. */
    public static function streakTerpanjang(array $tanggal): int
    {
        $urut = array_values(array_unique($tanggal));
        sort($urut);
        $best = 0;
        $jalan = 0;
        foreach ($urut as $i => $d) {
            $jalan = $i > 0 && Forecast::selisihHari($urut[$i - 1], $d) === 1 ? $jalan + 1 : 1;
            $best = max($best, $jalan);
        }

        return $best;
    }

    /** Senin = 1 ... Minggu = 7 */
    public static function hariKe(string $t): int
    {
        return (int) gmdate('N', strtotime($t . ' 00:00:00 UTC'));
    }

    /** Senin minggu ini (minggu dihitung Senin-Minggu). */
    public static function seninMingguIni(string $hariIni): string
    {
        return Forecast::tambahHari($hariIni, -(self::hariKe($hariIni) - 1));
    }

    /** Berapa minggu berturut-turut ada setoran tabungan (minggu ini boleh belum). */
    public static function mingguBeruntun(array $tanggal, string $hariIni): int
    {
        $minggu = array_flip(array_map([self::class, 'seninMingguIni'], $tanggal));
        $s = self::seninMingguIni($hariIni);
        if (! isset($minggu[$s])) {
            $s = Forecast::tambahHari($s, -7);
        }
        $n = 0;
        while (isset($minggu[$s])) {
            $n++;
            $s = Forecast::tambahHari($s, -7);
        }

        return $n;
    }

    public static function buatBadge(string $key, string $nama, string $ket, float $nilai, array $ambang, string $satuan = 'angka'): array
    {
        $tingkat = count(array_filter($ambang, fn ($a) => $nilai >= $a));
        $berikut = $tingkat < 3 ? $ambang[$tingkat] : null;
        $dari = $tingkat > 0 ? $ambang[$tingkat - 1] : 0;
        $persen = $berikut === null ? 100 : (int) max(0, min(99, floor(($nilai - $dari) / ($berikut - $dari) * 100)));

        return [
            'key' => $key, 'nama' => $nama, 'ket' => $ket, 'nilai' => $nilai, 'ambang' => $ambang,
            'tingkat' => $tingkat, 'berikut' => $berikut, 'persen' => $persen, 'satuan' => $satuan,
            'poin' => self::POIN_TINGKAT[$tingkat], 'dapat' => $tingkat > 0,
        ];
    }

    /** "1/3 lagi", "Rp 120.000 lagi" menuju tingkat berikutnya */
    public static function sisaBadge(array $b): string
    {
        if ($b['berikut'] === null) {
            return 'Emas';
        }
        $kurang = $b['berikut'] - $b['nilai'];

        return $b['satuan'] === 'rp' ? Format::rupiah($kurang) . ' lagi' : ((int) $kurang) . ' lagi';
    }

    /** @param array $i lihat untuk() untuk daftar kuncinya */
    public static function hitung(array $i): array
    {
        $streak = self::hitungStreak($i['tanggalTransaksi'], $i['hariIni']);
        $terpanjang = max($streak, self::streakTerpanjang($i['tanggalTransaksi']));

        // hari hemat: hari dalam 30 hari terakhir yang pengeluarannya tidak melebihi batas harian
        $hariHemat = 0;
        if ($i['batasHarian'] > 0) {
            for ($k = 0; $k < 30; $k++) {
                if (($i['pengeluaranPerHari'][Forecast::tambahHari($i['hariIni'], -$k)] ?? 0) <= $i['batasHarian']) {
                    $hariHemat++;
                }
            }
        }

        $badges = [
            self::buatBadge('pencatat', 'Pencatat', 'Jumlah transaksi yang kamu catat', $i['jumlahTransaksi'], [10, 50, 150]),
            self::buatBadge('rajin', 'Rajin Mencatat', 'Rekor mencatat berhari-hari tanpa putus', $terpanjang, [3, 7, 30]),
            self::buatBadge('hemat', 'Hari Hemat', $i['batasHarian'] > 0 ? 'Hari di bawah batas harian (30 hari terakhir)' : 'Atur batas harian dulu di menu Transaksi', $hariHemat, [5, 15, 25]),
            self::buatBadge('anggaran', 'Patuh Anggaran', 'Kategori yang masih di bawah anggaran bulan ini', $i['anggaranAman'], [1, 3, 5]),
            self::buatBadge('penabung', 'Penabung', 'Total setoran ke target tabungan', $i['totalSetoran'], [100000, 500000, 2000000], 'rp'),
            self::buatBadge('rutin', 'Nabung Rutin', 'Minggu berturut-turut ada setoran tabungan', self::mingguBeruntun($i['tanggalSetoran'], $i['hariIni']), [2, 4, 8]),
            self::buatBadge('impian', 'Impian Tercapai', 'Target tabungan yang berhasil tercapai', $i['targetTercapai'], [1, 3, 5]),
            self::buatBadge('perencana', 'Perencana', 'Rencana pengeluaran & jadwal pemasukan yang dibuat', $i['jumlahRencana'], [1, 5, 15]),
            self::buatBadge('patungan', 'Raja Patungan', 'Tagihan split bill yang dikirim ke teman ForeCash', $i['patunganTerkirim'], [1, 5, 15]),
            self::buatBadge('lingkaran', 'Lingkaran Hemat', 'Teman yang tersambung di ForeCash', $i['jumlahTeman'], [1, 3, 8]),
            self::buatBadge('bebas', 'Bebas Utang', 'Utang yang sudah kamu lunasi', $i['utangLunas'], [1, 5, 10]),
            self::buatBadge('penagih', 'Penagih Andal', 'Piutang yang sudah dilunasi temanmu', $i['piutangLunas'], [1, 5, 15]),
            self::buatBadge('pemindai', 'Pemindai Struk', 'Transaksi yang dilengkapi foto struk', $i['jumlahStruk'], [1, 10, 30]),
        ];

        $poin = array_sum(array_column($badges, 'poin')) + min($streak, 30) * 10;
        $li = 0;
        foreach (self::LEVELS as $idx => [$min]) {
            if ($poin >= $min) {
                $li = $idx;
            }
        }
        $next = self::LEVELS[$li + 1] ?? null;

        // badge yang paling dekat naik tingkat
        $kandidat = array_filter($badges, fn ($b) => $b['berikut'] !== null && $b['nilai'] > 0 && ! ($b['key'] === 'hemat' && $i['batasHarian'] <= 0));
        usort($kandidat, fn ($a, $b) => $b['persen'] <=> $a['persen']);

        return [
            'streak' => $streak, 'streakTerpanjang' => $terpanjang, 'badges' => $badges, 'poin' => $poin,
            'level' => $li + 1, 'levelNama' => self::LEVELS[$li][1], 'prevPoin' => self::LEVELS[$li][0],
            'nextPoin' => $next[0] ?? null, 'nextNama' => $next[1] ?? null,
            'tantangan' => self::tantanganMingguIni($i),
            'hampir' => $kandidat[0] ?? null,
        ];
    }

    /** Tantangan berganti tiap minggu (bergilir), supaya selalu ada hal baru untuk dikejar. */
    public static function tantanganMingguIni(array $i): array
    {
        $senin = self::seninMingguIni($i['hariIni']);
        $hariKe = self::hariKe($i['hariIni']);
        $sisaHari = 7 - $hariKe;
        $giliran = intdiv(abs(Forecast::selisihHari('2026-01-05', $senin)), 7) % 3; // 5 Jan 2026 = Senin

        // jumlah hari minggu ini yang ada catatannya / di bawah batas harian
        $hariMinggu = array_map(fn ($k) => Forecast::tambahHari($senin, $k), range(0, $hariKe - 1));
        $tanggal = array_flip($i['tanggalTransaksi']);
        $hariCatat = count(array_filter($hariMinggu, fn ($d) => isset($tanggal[$d])));
        $hariHemat = count(array_filter($hariMinggu, fn ($d) => ($i['pengeluaranPerHari'][$d] ?? 0) <= $i['batasHarian']));

        if ($giliran === 1 || ($giliran === 2 && $i['batasHarian'] <= 0)) {
            $max = 5;

            return ['judul' => 'Catat transaksi minimal 5 hari minggu ini', 'now' => $hariCatat, 'max' => $max, 'sisaHari' => $sisaHari,
                'jenis' => 'min', 'satuan' => 'hari', 'selesai' => $hariCatat >= $max, 'gagal' => $hariCatat + $sisaHari < $max];
        }
        if ($giliran === 2) {
            $max = 4;

            return ['judul' => 'Belanja di bawah batas harian selama 4 hari minggu ini', 'now' => $hariHemat, 'max' => $max, 'sisaHari' => $sisaHari,
                'jenis' => 'min', 'satuan' => 'hari', 'selesai' => $hariHemat >= $max, 'gagal' => $hariHemat + $sisaHari < $max];
        }
        $lewat = $i['makanMingguIni'] > self::BATAS_TANTANGAN;

        return [
            'judul' => 'Makan & minum minggu ini di bawah ' . Format::rupiah(self::BATAS_TANTANGAN),
            'now' => $i['makanMingguIni'], 'max' => self::BATAS_TANTANGAN, 'sisaHari' => $sisaHari, 'jenis' => 'maks', 'satuan' => 'rp',
            'selesai' => ! $lewat && $sisaHari === 0, 'gagal' => $lewat,
        ];
    }

    /** Ambil data dari database lalu hitung gamifikasi. */
    public static function untuk(User $user): array
    {
        $hari = Format::hariIni();
        $senin = self::seninMingguIni($hari);
        $awalBulan = substr($hari, 0, 7) . '-01';
        $akhirBulan = Format::tambahBulan($awalBulan);
        $tx = fn () => DB::table('transactions')->where('user_id', $user->id);

        $perHari = $tx()->where('jenis', 'pengeluaran')->where('tanggal', '>', Forecast::tambahHari($hari, -30))
            ->groupBy('tanggal')->selectRaw('tanggal, SUM(nominal) AS total')->pluck('total', 'tanggal')
            ->mapWithKeys(fn ($v, $k) => [substr((string) $k, 0, 10) => (float) $v])->all();

        $debts = DB::table('debts')->where('user_id', $user->id)->get(['arah', 'status', 'teman_id', 'pasangan_id']);
        $setoran = DB::table('setoran_tabungan')->where('user_id', $user->id)->get(['nominal', 'tanggal']);

        // anggaran aman baru dihitung mulai tanggal 7 (awal bulan semua kategori pasti "aman")
        $anggaranAman = 0;
        if ((int) substr($hari, 8) >= 7) {
            $pakai = $tx()->where('jenis', 'pengeluaran')->where('tanggal', '>=', $awalBulan)->where('tanggal', '<', $akhirBulan)
                ->whereNotNull('category_id')->groupBy('category_id')->selectRaw('category_id, SUM(nominal) AS total')
                ->pluck('total', 'category_id');
            $anggaranAman = DB::table('anggaran')->where('user_id', $user->id)->get(['category_id', 'batas'])
                ->filter(fn ($a) => (float) ($pakai[$a->category_id] ?? 0) <= (float) $a->batas)->count();
        }

        return self::hitung([
            'hariIni' => $hari,
            'tanggalTransaksi' => $tx()->distinct()->orderByDesc('tanggal')->limit(2000)->pluck('tanggal')
                ->map(fn ($t) => substr((string) $t, 0, 10))->all(),
            'jumlahTransaksi' => $tx()->count(),
            'batasHarian' => (float) $user->batas_harian,
            'pengeluaranPerHari' => $perHari,
            'makanMingguIni' => (float) DB::table('transactions')->join('categories', 'categories.id', '=', 'transactions.category_id')
                ->where('transactions.user_id', $user->id)->where('transactions.jenis', 'pengeluaran')->where('transactions.tanggal', '>=', $senin)
                ->where('categories.nama', 'Makan & Minum')->sum('transactions.nominal'),
            'jumlahStruk' => $tx()->whereNotNull('bukti')->count(),
            'utangLunas' => $debts->where('arah', 'utang')->where('status', 'lunas')->count(),
            'piutangLunas' => $debts->where('arah', 'piutang')->where('status', 'lunas')->count(),
            'patunganTerkirim' => $debts->filter(fn ($d) => $d->arah === 'piutang' && $d->teman_id && $d->pasangan_id)->count(),
            'jumlahTeman' => DB::table('teman')->where('user_id', $user->id)->count(),
            'jumlahRencana' => DB::table('rencana_pengeluaran')->where('user_id', $user->id)->count()
                + DB::table('income_schedules')->where('user_id', $user->id)->count(),
            'totalSetoran' => (float) $setoran->sum('nominal'),
            'tanggalSetoran' => $setoran->map(fn ($s) => substr((string) $s->tanggal, 0, 10))->all(),
            'targetTercapai' => DB::table('target_tabungan')->where('user_id', $user->id)->whereNotNull('tercapai_pada')->count(),
            'anggaranAman' => $anggaranAman,
        ]);
    }
}
