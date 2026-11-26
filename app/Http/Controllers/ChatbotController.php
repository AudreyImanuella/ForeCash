<?php

namespace App\Http\Controllers;

use App\Services\Forecast;
use App\Support\Chatbot;
use App\Support\Format;
use App\Support\Gamify;
use App\Support\Laporan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** ForeCash Assistant (port dari app/api/chatbot/route.ts). Menerima { pesan }, membalas { jawaban }. */
class ChatbotController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $pesan = mb_substr(trim((string) $request->input('pesan')), 0, 200);
        if ($pesan === '') {
            return response()->json(['error' => 'Pertanyaan kosong.'], 422);
        }

        $user = $request->user();
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

        return response()->json(['jawaban' => Chatbot::buatJawaban($intent, $data)]);
    }
}
