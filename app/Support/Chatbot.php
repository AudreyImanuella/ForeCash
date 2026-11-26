<?php

namespace App\Support;

/**
 * ForeCash Assistant: chatbot berbasis aturan (tanpa AI API).
 * Port dari lib/chatbot.ts versi Next.js.
 * Cara kerja: 1) cari kata kunci di pertanyaan -> tentukan "intent"
 *             2) ambil data user yang dibutuhkan intent itu -> susun jawaban.
 * Format jawaban: **tebal**, [teks link](/halaman), dan baris baru (\n).
 */
class Chatbot
{
    private const TIPS = [
        'Makan & Minum' => 'Coba bawa bekal 2–3 kali seminggu atau batasi jajan kopi.',
        'Transportasi' => 'Gabungkan perjalanan atau pakai transportasi umum untuk rute rutin.',
        'Hiburan' => 'Tentukan jatah hiburan per minggu dan catat setiap kali dipakai.',
        'Kos / Sewa' => 'Ini biaya tetap, jadi fokuskan penghematan di kategori lain.',
    ];

    /** Urutan pengecekan: yang lebih atas didahulukan. */
    public static function deteksiIntent(string $teks): string
    {
        $q = mb_strtolower($teks);
        $ada = function (string ...$kata) use ($q) {
            foreach ($kata as $k) {
                if (str_contains($q, $k)) {
                    return true;
                }
            }

            return false;
        };

        return match (true) {
            $ada('saldo', 'uang aku', 'uangku', 'duit') && ! $ada('habis', 'kapan', 'cukup') => 'saldo',
            $ada('habis', 'kapan', 'runway', 'cukup', 'aman') => 'habis',
            $ada('hemat', 'tips', 'saran', 'boros', 'kategori', 'paling banyak') => 'tips',
            $ada('burn', 'rata-rata', 'per hari', 'harian') => 'burn',
            $ada('utang', 'piutang', 'pinjam', 'nagih', 'patungan') => 'utang',
            $ada('badge', 'level', 'poin', 'streak', 'pencapaian') => 'badge',
            $ada('transfer', 'wallet', 'dompet', 'gopay', 'bca', 'e-wallet') => 'transfer',
            $ada('struk', 'foto', 'bukti') => 'struk',
            $ada('catat', 'tambah', 'input', 'cara') => 'catat',
            $ada('halo', 'hai', 'hi', 'pagi', 'siang', 'malam') => 'sapa',
            default => 'tidak_paham',
        };
    }

    /** Data apa saja yang perlu diambil untuk intent ini (supaya query ke database seperlunya). */
    public static function kebutuhanData(string $intent): array
    {
        return [
            'forecast' => in_array($intent, ['saldo', 'habis', 'burn', 'badge'], true),
            'wallets' => $intent === 'saldo',
            'laporan' => $intent === 'tips',
            'utang' => $intent === 'utang',
            'gamify' => $intent === 'badge',
        ];
    }

    public static function buatJawaban(string $intent, array $d): string
    {
        $f = $d['f'] ?? null;

        switch ($intent) {
            case 'saldo':
                $baris = array_map(fn ($w) => '• ' . $w['nama'] . ': ' . Format::rupiah($w['saldo']), $d['wallets'] ?? []);

                return 'Total saldomu **' . Format::rupiah($f['saldo'] ?? 0) . "**.\n" . implode("\n", $baris);

            case 'habis':
                if ($f['burn'] <= 0) {
                    return 'Belum ada pengeluaran yang tercatat, jadi tanggal saldo habis belum bisa diprediksi.';
                }
                $s = 'Dengan pengeluaran rata-rata **' . Format::rupiah($f['burn']) . '/hari**, saldomu diperkirakan habis **'
                    . Format::tgl($f['habis']) . '** (' . $f['runway'] . ' hari lagi).';
                if ($f['next']) {
                    $s .= ' Pemasukan berikutnya ' . Format::tgl($f['next']['tanggal_perkiraan']) . ', jadi statusmu **' . strtoupper($f['status']) . '**.';
                }
                if ($f['status'] !== 'aman' && $f['batasAman']) {
                    $s .= ' Supaya aman, batasi pengeluaran maksimal **' . Format::rupiah($f['batasAman']) . '/hari**.';
                }

                return $s;

            case 'tips':
                $k = $d['kategoriTeratas'] ?? null;
                if (! $k) {
                    return 'Belum ada pengeluaran bulan ini. Mulai catat dulu, nanti aku kasih saran berdasarkan datamu.';
                }
                $pct = (int) round($k['total'] / $k['totalKeluar'] * 100);
                $tips = self::TIPS[$k['kategori']] ?? 'Coba tetapkan batas khusus untuk kategori ini setiap minggu.';

                return 'Bulan ini pengeluaran terbesarmu di **' . $k['kategori'] . '**: ' . Format::rupiah($k['total']) . " ({$pct}% dari total). {$tips}";

            case 'burn':
                return 'Burn rate-mu **' . Format::rupiah($f['burn']) . "/hari**, dihitung dari total pengeluaran {$f['hari']} hari terakhir dibagi {$f['hari']}. Transfer antar wallet tidak ikut dihitung.";

            case 'utang':
                $u = $d['utang'];
                if ($u['piutangN'] + $u['utangN'] === 0) {
                    return 'Tidak ada utang atau piutang yang belum lunas. Mantap!';
                }

                return 'Piutang (teman ke kamu): **' . Format::rupiah($u['piutangTotal']) . "** dari {$u['piutangN']} catatan.\n"
                    . 'Utang (kamu ke teman): **' . Format::rupiah($u['utangTotal']) . "** dari {$u['utangN']} catatan.\n"
                    . 'Kirim pengingat lewat menu [Utang & Patungan](/utang).';

            case 'badge':
                $g = $d['gamify'];

                return "Kamu di **Level {$g['level']} · {$g['levelNama']}** ({$g['poin']} poin), streak **{$g['streak']} hari**, dan sudah dapat {$g['badgeDapat']} dari {$g['badgeTotal']} badge. Lihat detail di [Pencapaian](/pencapaian).";

            case 'transfer':
                return 'Untuk isi e-wallet dari rekening, catat sebagai **Transfer** di menu Transaksi. Cukup sekali catat, dan tidak dihitung sebagai pengeluaran sehingga burn rate tetap akurat.';

            case 'struk':
                return 'Saat mencatat transaksi, pilih **Foto struk** (JPG/JPEG/PNG, maks. 2 MB). Ikon struk akan muncul di riwayat dan bisa diklik untuk melihat fotonya.';

            case 'catat':
                return 'Buka menu [Transaksi](/transaksi), pilih jenis, isi nominal, kategori, dan tanggal, lalu Simpan. Keterangan boleh dikosongkan, otomatis memakai nama kategori.';

            case 'sapa':
                return "Halo {$d['namaDepan']}! Mau cek saldo, kapan uang habis, atau minta tips hemat?";

            default:
                return 'Maaf, aku belum paham pertanyaan itu. Coba tanya soal **saldo**, **kapan uang habis**, **tips hemat**, **utang**, atau **pencapaian**.';
        }
    }
}
