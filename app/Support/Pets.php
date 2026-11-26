<?php

namespace App\Support;

use App\Services\Forecast;

/**
 * Petualangan: katalog 12 teman hemat, telur, dan data peta perjalanan.
 * Port dari lib/pets.ts versi Next.js. Fungsi di sini murni (tanpa database).
 */
class Pets
{
    public const TELUR_TARGET = 7;

    public const PETS = [
        'celeng' => ['nama' => 'Celeng', 'warna' => '#FFB3C7', 'gelap' => '#F28AA8', 'tipe' => 'pig', 'sifat' => 'Rajin menabung', 'cerita' => 'Celengan yang bisa berjalan. Setiap koin yang kamu simpan bikin perutnya makin bulat.'],
        'mochi' => ['nama' => 'Mochi', 'warna' => '#FFFFFF', 'gelap' => '#E3DCEB', 'tipe' => 'bunny', 'sifat' => 'Lembut dan sabar', 'cerita' => 'Tidak pernah buru-buru belanja. Selalu tidur dulu sebelum memutuskan sesuatu.'],
        'kopi' => ['nama' => 'Kopi', 'warna' => '#C99A6E', 'gelap' => '#A5764C', 'tipe' => 'bear', 'sifat' => 'Pekerja keras', 'cerita' => 'Suka begadang menemani kamu mengerjakan tugas, tapi tidak suka jajan kopi mahal.'],
        'jeruk' => ['nama' => 'Jeruk', 'warna' => '#FFB347', 'gelap' => '#F08A1C', 'tipe' => 'leaf', 'sifat' => 'Ceria', 'cerita' => 'Saudara jauh logo ForeCash. Muncul setiap kali saldomu aman sampai akhir bulan.'],
        'boba' => ['nama' => 'Boba', 'warna' => '#B9A3F5', 'gelap' => '#9A80EA', 'tipe' => 'cat', 'sifat' => 'Penasaran', 'cerita' => 'Suka mengintip isi dompet. Kalau melihat struk, langsung dicatat.'],
        'lumut' => ['nama' => 'Lumut', 'warna' => '#9BE0A3', 'gelap' => '#6CC47A', 'tipe' => 'frog', 'sifat' => 'Santai', 'cerita' => 'Hidup di kolam dekat jalan. Melompat setiap kali kamu melunasi utang.'],
        'awan' => ['nama' => 'Awan', 'warna' => '#D7ECFF', 'gelap' => '#B4D6F7', 'tipe' => 'cloud', 'sifat' => 'Penyayang', 'cerita' => 'Datang saat langit mendung untuk menghibur. Percaya hujan pasti berhenti.'],
        'tahu' => ['nama' => 'Tahu', 'warna' => '#FFE680', 'gelap' => '#F5CB2E', 'tipe' => 'tuft', 'sifat' => 'Hemat', 'cerita' => 'Kecil tapi teliti. Tahu persis harga gorengan di setiap kantin kampus.'],
        'sapi' => ['nama' => 'Sapi', 'warna' => '#FFFFFF', 'gelap' => '#DCD6CC', 'tipe' => 'cow', 'sifat' => 'Tenang', 'cerita' => 'Tidak pernah panik walau saldo menipis. Selalu punya rencana cadangan.'],
        'kunyit' => ['nama' => 'Kunyit', 'warna' => '#FF9A5C', 'gelap' => '#E8742F', 'tipe' => 'fox', 'sifat' => 'Cerdik', 'cerita' => 'Jago mencari promo dan diskon mahasiswa di sekitar kampus.'],
        'ubi' => ['nama' => 'Ubi', 'warna' => '#C79BD9', 'gelap' => '#A773BE', 'tipe' => 'tuft', 'sifat' => 'Sederhana', 'cerita' => 'Bahagia dengan hal kecil. Bekal dari rumah adalah makanan favoritnya.'],
        'tempe' => ['nama' => 'Tempe', 'warna' => '#F2D3A0', 'gelap' => '#DDB173', 'tipe' => 'bear', 'sifat' => 'Setia kawan', 'cerita' => 'Selalu ingat siapa yang belum bayar patungan, tapi menagihnya dengan sopan.'],
    ];

    /**
     * Hari baik = ada transaksi tercatat dan pengeluaran hari itu tidak melewati batas harian (jika batas diatur).
     *
     * @param  iterable<array{tanggal: string, jenis: string, nominal: float}>  $transaksi
     * @return array<string, true>  tanggal => true
     */
    public static function hitungHariBaik(iterable $transaksi, float $batasHarian): array
    {
        $keluar = [];
        foreach ($transaksi as $t) {
            $keluar[$t['tanggal']] = ($keluar[$t['tanggal']] ?? 0) + ($t['jenis'] === 'pengeluaran' ? (float) $t['nominal'] : 0);
        }
        $hasil = [];
        foreach ($keluar as $tgl => $total) {
            if ($batasHarian <= 0 || $total <= $batasHarian) {
                $hasil[$tgl] = true;
            }
        }

        return $hasil;
    }

    /** Progres telur = jumlah hari baik SETELAH hewan terakhir menetas. */
    public static function progresTelur(array $hariBaik, array $tanggalMenetas): int
    {
        $sejak = $tanggalMenetas ? max($tanggalMenetas) : '0000-00-00';

        return count(array_filter(array_keys($hariBaik), fn ($d) => $d > $sejak));
    }

    /**
     * Rentang & status setiap titik di peta. Maksimal 35 titik.
     * Status: gajian | hariini | baik | boros | kosong | cukup | kurang
     */
    public static function dataPeta(
        string $hariIni, ?string $pemasukanBerikut, ?string $pemasukanTerakhir,
        float $saldo, ?int $runway, array $keluarPerHari, array $dicatat, array $hariBaik,
    ): array {
        $akhir = $pemasukanBerikut ?? Forecast::tambahHari($hariIni, 14);
        $mulai = $pemasukanTerakhir ?? Forecast::tambahHari($hariIni, -6);
        // jaga panjang peta tetap wajar (maks. 35 titik), hari ini selalu terlihat
        if (Forecast::selisihHari($mulai, $akhir) > 34) {
            $a = Forecast::tambahHari($akhir, -34);
            $b = Forecast::tambahHari($hariIni, -20);
            $mulai = max($a, $b);
        }
        if (Forecast::selisihHari($mulai, $akhir) > 34) {
            $akhir = Forecast::tambahHari($mulai, 34);
        }

        $habis = $runway !== null ? Forecast::tambahHari($hariIni, $runway) : null;
        $hari = [];
        for ($d = $mulai; $d <= $akhir; $d = Forecast::tambahHari($d, 1)) {
            if ($d === $akhir && $pemasukanBerikut) {
                $status = 'gajian';
            } elseif ($d === $hariIni) {
                $status = 'hariini';
            } elseif ($d < $hariIni) {
                $status = isset($hariBaik[$d]) ? 'baik' : (isset($dicatat[$d]) ? 'boros' : 'kosong');
            } else {
                $status = $saldo > 0 && ($habis === null || $d <= $habis) ? 'cukup' : 'kurang';
            }
            $hari[] = ['tanggal' => $d, 'status' => $status, 'keluar' => $d <= $hariIni ? ($keluarPerHari[$d] ?? 0) : 0];
        }

        return ['hari' => $hari, 'mulai' => $mulai, 'akhir' => $akhir, 'adaJadwal' => (bool) $pemasukanBerikut];
    }
}
