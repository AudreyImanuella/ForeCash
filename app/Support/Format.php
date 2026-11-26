<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Pembantu format tampilan (port dari lib/format.ts dan lib/tanggal.ts versi Next.js).
 * Dipakai di Blade, contoh: {{ \App\Support\Format::rupiah($x) }}
 */
class Format
{
    public const ZONA = 'Asia/Jakarta';

    private const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    private const HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    /** 1250000 -> "Rp 1.250.000", -5000 -> "-Rp 5.000" */
    public static function rupiah(float|int|string|null $nilai): string
    {
        $v = (int) round((float) $nilai);

        return ($v < 0 ? '-Rp ' : 'Rp ') . number_format(abs($v), 0, ',', '.');
    }

    /** Isian rupiah dari form -> angka. "25.000" -> 25000, "12.500,50" -> 12500.5, "" -> 0 */
    public static function bacaNominal(mixed $teks): float
    {
        $s = str_replace(['.', ' ', 'Rp', 'rp'], '', (string) $teks);

        return round((float) str_replace(',', '.', $s), 2);
    }

    /** Angka -> teks untuk isian form ber-pemisah ribuan. 25000 -> "25.000" */
    public static function isianNominal(float|int|string|null $nilai): string
    {
        return number_format((float) $nilai, 0, ',', '.');
    }

    /** Hari ini dalam WIB, format 'YYYY-MM-DD'. */
    public static function hariIni(): string
    {
        return Carbon::now(self::ZONA)->toDateString();
    }

    /** '2026-11-13' (atau objek tanggal) -> '13 Nov 2026' */
    public static function tgl(CarbonInterface|string|null $t): string
    {
        if (! $t) {
            return '-';
        }
        [$y, $m, $d] = array_map('intval', explode('-', substr(self::teks($t), 0, 10)));

        return $d . ' ' . self::BULAN[$m - 1] . ' ' . $y;
    }

    /** '2026-11-13' -> 'Jumat, 13 Nov 2026' */
    public static function tglPanjang(CarbonInterface|string $t): string
    {
        $hari = (int) date('w', strtotime(self::teks($t) . ' 00:00:00 UTC'));

        return self::HARI[$hari] . ', ' . self::tgl($t);
    }

    /** '2026-11' -> 'November 2026' */
    public static function namaBulan(string $bulan): string
    {
        $NAMA = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        [$y, $m] = array_map('intval', explode('-', $bulan));

        return $NAMA[$m - 1] . ' ' . $y;
    }

    /** Tambah n bulan, tanggal dipotong ke akhir bulan kalau kelebihan: '2026-01-31' + 1 -> '2026-02-28' */
    public static function tambahBulan(string $t, int $n = 1): string
    {
        [$y, $m, $d] = array_map('intval', explode('-', substr($t, 0, 10)));
        $total = $y * 12 + ($m - 1) + $n;
        $ty = intdiv($total, 12);
        $tm = $total % 12 + 1;
        $akhir = (int) date('t', gmmktime(0, 0, 0, $tm, 1, $ty));

        return sprintf('%04d-%02d-%02d', $ty, $tm, min($d, $akhir));
    }

    /** Sapaan sesuai jam WIB. */
    public static function sapaan(): string
    {
        $jam = Carbon::now(self::ZONA)->hour;

        return $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));
    }

    /** Emoji ikon transaksi per kategori (sama dengan kategoriEmoji() di lib/data.ts). */
    public static function kategoriEmoji(?string $nama, string $jenis = 'pengeluaran'): string
    {
        if ($jenis === 'transfer') {
            return '🔁';
        }
        $map = [
            'Makan & Minum' => '🍜', 'Transportasi' => '🛵', 'Kos / Sewa' => '🏠', 'Kebutuhan Kuliah' => '📚', 'Hiburan' => '🎬',
            'Uang Saku' => '💰', 'Kerja Sampingan' => '💼', 'Beasiswa' => '🎓', 'Pelunasan Utang/Piutang' => '🤝',
        ];

        return $map[$nama ?? ''] ?? ($jenis === 'pemasukan' ? '💵' : '🧾');
    }

    private static function teks(CarbonInterface|string $t): string
    {
        return $t instanceof CarbonInterface ? $t->toDateString() : $t;
    }
}
