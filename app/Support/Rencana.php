<?php

namespace App\Support;

use App\Services\Forecast;

/**
 * Hitungan fitur Rencana: anggaran per kategori dan target tabungan.
 * Port dari lib/rencana.ts versi Next.js. Murni (tanpa ambil data).
 */
class Rencana
{
    /**
     * Bandingkan anggaran dengan pengeluaran bulan ini. "Hampir" = sudah terpakai >= 80%.
     *
     * @param  array<int, array{category_id: int, nama: string, batas: float}>  $anggaran
     * @param  array<int, float>  $terpakaiPerKategori  [category_id => total]
     */
    public static function ringkasAnggaran(array $anggaran, array $terpakaiPerKategori): array
    {
        return array_map(function ($a) use ($terpakaiPerKategori) {
            $terpakai = (float) ($terpakaiPerKategori[$a['category_id']] ?? 0);
            $rasio = $a['batas'] > 0 ? $terpakai / $a['batas'] : 0;
            $status = $terpakai > $a['batas'] ? 'lewat' : ($rasio >= 0.8 ? 'hampir' : 'aman');

            return $a + [
                'terpakai' => $terpakai,
                'sisa' => $a['batas'] - $terpakai,
                'persen' => (int) min(100, round($rasio * 100)),
                'status' => $status,
            ];
        }, $anggaran);
    }

    /** Kemajuan target + saran setoran per minggu supaya tercapai sebelum tenggat. */
    public static function infoTarget(float $target, float $terkumpul, ?string $tenggat, string $hariIni): array
    {
        $kurang = max(0, $target - $terkumpul);
        $tercapai = $kurang == 0;
        $persen = $target > 0 ? (int) min(100, round($terkumpul / $target * 100)) : 0;
        $perMinggu = null;
        $telat = false;
        if (! $tercapai && $tenggat) {
            $hari = Forecast::selisihHari($hariIni, $tenggat);
            if ($hari <= 0) {
                $telat = true;
            } else {
                $perMinggu = ceil($kurang / max(1, ceil($hari / 7)) / 1000) * 1000;
            }
        }

        return compact('persen', 'kurang', 'tercapai', 'perMinggu', 'telat');
    }
}
