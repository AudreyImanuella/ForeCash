<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;

/**
 * Laporan bulanan (port dari lib/laporan.ts versi Next.js).
 * Transfer antar wallet tidak dihitung. Dipakai halaman Laporan dan Pantau Anak.
 */
class Laporan
{
    /**
     * Ringkas daftar transaksi (fungsi murni).
     *
     * @param  iterable<array{jenis: string, nominal: float, kategori: ?string}>  $baris
     * @return array{masuk: float, keluar: float, perKategori: array<int, array{kategori: string, total: float}>, rasio: ?int}
     */
    public static function ringkas(iterable $baris): array
    {
        $masuk = 0.0;
        $keluar = 0.0;
        $per = [];
        foreach ($baris as $b) {
            $n = (float) $b['nominal'];
            if ($b['jenis'] === 'pemasukan') {
                $masuk += $n;
            }
            if ($b['jenis'] === 'pengeluaran') {
                $keluar += $n;
                $k = $b['kategori'] ?? 'Tanpa kategori';
                $per[$k] = ($per[$k] ?? 0) + $n;
            }
        }
        arsort($per);
        $perKategori = array_map(fn ($k, $t) => ['kategori' => $k, 'total' => $t], array_keys($per), $per);
        $rasio = $masuk > 0 ? (int) round(($masuk - $keluar) / $masuk * 100) : null;

        return compact('masuk', 'keluar', 'perKategori', 'rasio');
    }

    /** Ambil transaksi satu bulan ('YYYY-MM') milik user lalu ringkas. */
    public static function untuk(User $user, string $bulan): array
    {
        $dari = Carbon::parse($bulan . '-01');

        $baris = $user->transactions()
            ->leftJoin('categories', 'categories.id', '=', 'transactions.category_id')
            ->whereIn('transactions.jenis', ['pemasukan', 'pengeluaran'])
            ->where('transactions.tanggal', '>=', $dari->toDateString())
            ->where('transactions.tanggal', '<', $dari->copy()->addMonth()->toDateString())
            ->get(['transactions.jenis', 'transactions.nominal', 'categories.nama as kategori'])
            ->map(fn ($t) => ['jenis' => $t->jenis, 'nominal' => (float) $t->nominal, 'kategori' => $t->kategori]);

        return self::ringkas($baris);
    }

    /** ?bulan=YYYY-MM dari URL, atau bulan ini kalau kosong/tidak valid. */
    public static function bulanDariParam(mixed $q): string
    {
        return is_string($q) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $q) ? $q : substr(Format::hariIni(), 0, 7);
    }
}
