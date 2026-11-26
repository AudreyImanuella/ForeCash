<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Forecast;
use App\Services\Gemini;
use App\Support\Chatbot;
use App\Support\Format;
use App\Support\Gamify;
use App\Support\Laporan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ForeCash Assistant. Menerima { pesan, riwayat? }, membalas { jawaban, sumber }.
 * - Kalau GEMINI_API_KEY diisi: dijawab AI (Gemini) berdasarkan ringkasan data keuangan user.
 * - Kalau key kosong, kuota habis, atau Gemini error: memakai jawaban berbasis aturan (port dari versi Next.js).
 */
class ChatbotController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $pesan = mb_substr(trim((string) $request->input('pesan')), 0, 500);
        if ($pesan === '') {
            return response()->json(['error' => 'Pertanyaan kosong.'], 422);
        }

        $user = $request->user();

        if (Gemini::aktif()) {
            $jawaban = Gemini::jawab($this->instruksiAi($user), $this->riwayat($request), $pesan);
            if ($jawaban !== null) {
                return response()->json(['jawaban' => $jawaban, 'sumber' => 'ai']);
            }
        }

        return response()->json(['jawaban' => $this->jawabanAturan($user, $pesan), 'sumber' => 'aturan']);
    }

    /** Riwayat percakapan dari browser: maksimal 8 pesan terakhir, tiap pesan dipotong 500 karakter. */
    private function riwayat(Request $request): array
    {
        return collect($request->input('riwayat', []))
            ->filter(fn ($r) => is_array($r) && in_array($r['dari'] ?? null, ['user', 'bot'], true) && is_string($r['teks'] ?? null))
            ->map(fn ($r) => ['dari' => $r['dari'], 'teks' => mb_substr($r['teks'], 0, 500)])
            ->take(-8)->values()->all();
    }

    /**
     * Instruksi sistem untuk AI: peran, aturan menjawab, dan ringkasan data keuangan user.
     * Data pribadi (nama, email, nama teman/orang tua) TIDAK dikirim ke Google; hanya angka ringkasan.
     */
    private function instruksiAi(User $user): string
    {
        $hariIni = Format::hariIni();
        $wallets = $user->wallets()->orderBy('id')->get()->map(fn ($w) => ['nama' => $w->nama, 'saldo' => $w->saldo()]);
        $f = Forecast::untuk($user, (float) $wallets->sum('saldo'));
        $r = Laporan::untuk($user, substr($hariIni, 0, 7));
        $utang = $user->debts()->where('status', 'belum')->get(['arah', 'nominal']);
        $g = Gamify::untuk($user);

        $rp = fn ($n) => Format::rupiah((float) $n);
        $baris = [
            "Tanggal hari ini: {$hariIni}",
            'Status keuangan: ' . ucfirst($f['status']),
            'Total saldo: ' . $rp($f['saldo']) . ' (' . $wallets->map(fn ($w) => "{$w['nama']} " . $rp($w['saldo']))->implode(', ') . ')',
            'Pengeluaran rata-rata per hari: ' . $rp($f['burn']),
            'Perkiraan saldo habis: ' . ($f['habis'] ?? 'tidak bisa dihitung') . ($f['runway'] !== null ? " ({$f['runway']} hari lagi)" : ''),
            'Pemasukan berikutnya: ' . ($f['next'] ? "{$f['next']['sumber']} " . $rp($f['next']['nominal'] ?? 0) . " pada {$f['next']['tanggal_perkiraan']} ({$f['sisaHari']} hari lagi)" : 'belum ada jadwal'),
            'Batas belanja aman per hari: ' . ($f['batasAman'] !== null ? $rp($f['batasAman']) : '-'),
            'Rencana pengeluaran sebelum pemasukan berikutnya: ' . $rp($f['rencanaDulu']),
            'Bulan ini: pemasukan ' . $rp($r['masuk']) . ', pengeluaran ' . $rp($r['keluar']) . ($r['rasio'] !== null ? ", rasio tabungan {$r['rasio']}%" : ''),
            'Pengeluaran per kategori bulan ini: ' . (collect($r['perKategori'])->take(6)->map(fn ($k) => "{$k['kategori']} " . $rp($k['total']))->implode(', ') ?: 'belum ada'),
            'Utang belum lunas: ' . $rp($utang->where('arah', 'utang')->sum('nominal')) . ' (' . $utang->where('arah', 'utang')->count() . ' catatan)',
            'Piutang belum dibayar teman: ' . $rp($utang->where('arah', 'piutang')->sum('nominal')) . ' (' . $utang->where('arah', 'piutang')->count() . ' catatan)',
            "Gamifikasi: level {$g['level']} ({$g['levelNama']}), {$g['poin']} poin, streak {$g['streak']} hari, badge "
                . count(array_filter($g['badges'], fn ($b) => $b['dapat'])) . '/' . count($g['badges']),
        ];

        return <<<TXT
        Kamu adalah ForeCash Assistant, maskot tupai di aplikasi ForeCash: aplikasi peringatan dini keuangan untuk mahasiswa Indonesia.
        Panggil pengguna dengan "kamu".

        Aturan menjawab:
        - Pakai bahasa Indonesia santai dan ramah, seperti teman sebaya. Singkat: maksimal sekitar 5 kalimat atau 5 poin.
        - Jawab berdasarkan DATA KEUANGAN di bawah. Jangan mengarang angka; kalau datanya tidak ada, bilang terus terang.
        - Tulis nominal dengan format Rupiah, contoh: Rp 25.000.
        - Format yang boleh dipakai hanya **teks tebal**, baris baru, dan link ke halaman aplikasi dengan format [teks](/halaman).
          Halaman yang ada: /beranda, /transaksi, /utang, /rencana, /prediksi, /laporan, /petualangan, /pencapaian, /wallet, /akun.
        - Kamu tidak bisa mengubah data. Kalau diminta mencatat, menghapus, atau mentransfer, arahkan ke halaman yang sesuai.
        - Jangan memberi saran investasi spesifik (saham, kripto, pinjol). Fokus pada pengaturan uang saku, hemat, dan menabung.
        - Kalau pertanyaan tidak berhubungan dengan keuangan atau ForeCash, jawab singkat lalu arahkan kembali ke topik keuangan.

        DATA KEUANGAN PENGGUNA:
        {$this->gabung($baris)}
        TXT;
    }

    private function gabung(array $baris): string
    {
        return implode("\n", array_map(fn ($b) => "- {$b}", $baris));
    }

    /** Jawaban cadangan tanpa AI (logika lama, port dari app/api/chatbot/route.ts). */
    private function jawabanAturan(User $user, string $pesan): string
    {
        $intent = Chatbot::deteksiIntent($pesan);
        $perlu = Chatbot::kebutuhanData($intent);
        $data = ['namaDepan' => $user->namaDepan()];

        // ambil data seperlunya saja, sesuai jenis pertanyaan
        if ($perlu['wallets'] || $perlu['forecast']) {
            $wallets = $user->wallets()->orderBy('id')->get()->map(fn ($w) => ['nama' => $w->nama, 'saldo' => $w->saldo()]);
            $data['wallets'] = $wallets->all();
            if ($perlu['forecast']) {
                $data['f'] = Forecast::untuk($user, (float) $wallets->sum('saldo'));
            }
        }
        if ($perlu['laporan']) {
            $r = Laporan::untuk($user, substr(Format::hariIni(), 0, 7));
            $data['kategoriTeratas'] = $r['perKategori'] ? $r['perKategori'][0] + ['totalKeluar' => $r['keluar']] : null;
        }
        if ($perlu['utang']) {
            $rows = $user->debts()->where('status', 'belum')->get(['arah', 'nominal']);
            $data['utang'] = [
                'piutangTotal' => (float) $rows->where('arah', 'piutang')->sum('nominal'), 'piutangN' => $rows->where('arah', 'piutang')->count(),
                'utangTotal' => (float) $rows->where('arah', 'utang')->sum('nominal'), 'utangN' => $rows->where('arah', 'utang')->count(),
            ];
        }
        if ($perlu['gamify']) {
            $g = Gamify::untuk($user);
            $data['gamify'] = [
                'level' => $g['level'], 'levelNama' => $g['levelNama'], 'poin' => $g['poin'], 'streak' => $g['streak'],
                'badgeDapat' => count(array_filter($g['badges'], fn ($b) => $b['dapat'])), 'badgeTotal' => count($g['badges']),
            ];
        }

        return Chatbot::buatJawaban($intent, $data);
    }
}
