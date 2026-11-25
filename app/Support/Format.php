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
