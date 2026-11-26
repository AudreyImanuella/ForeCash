<?php

namespace App\Http\Controllers;

use App\Services\Forecast;
use App\Support\Gamify;
use App\Support\Format;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Beranda mahasiswa (port dari app/(app)/beranda/page.tsx versi Next.js). */
class BerandaController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // saldo tiap wallet (dihitung sekali, dipakai untuk kartu wallet dan forecast)
        $wallets = $user->wallets()->orderBy('id')->get()
            ->each(fn ($w) => $w->saldo_sekarang = $w->saldo());

        $f = Forecast::untuk($user, (float) $wallets->sum('saldo_sekarang'));

        $transaksi = $user->transactions()
            ->with(['category', 'wallet', 'walletTujuan'])
            ->orderByDesc('tanggal')->orderByDesc('id')
            ->limit(5)->get();

        // utang & piutang yang belum lunas
        $debts = $user->debts()->where('status', 'belum')
            ->selectRaw('arah, SUM(nominal) AS total')->groupBy('arah')->pluck('total', 'arah');
        $piutang = (float) ($debts['piutang'] ?? 0);
        $utang = (float) ($debts['utang'] ?? 0);

        $rencanaDekat = $user->rencana()->where('status', 'rencana')->orderBy('tanggal')->first();
        $targetAktif = $user->targetTabungan()->whereNull('tercapai_pada')->orderBy('created_at')->first();
        $tagihanTeman = $user->debts()
            ->where('arah', 'utang')->where('status', 'belum')->whereNotNull('teman_id')->count();

        // gamifikasi (streak, level, badge, tantangan) & teman hemat yang menemani
        $g = Gamify::untuk($user);
        $petDipakai = $user->pets()->where('dipakai', true)->value('pet_id');

        // mood tupai di kartu utama: urutan prioritasnya dari yang paling gawat
        $jajanHariIni = (float) $user->transactions()
            ->where('jenis', 'pengeluaran')
            ->where('tanggal', Format::hariIni())
            ->sum('nominal');
        $batasHarian = (float) $user->batas_harian;
        $mood = match (true) {
            $f['status'] === 'kritis' => 'nangis',
            $batasHarian > 0 && $jajanHariIni > $batasHarian => 'marah',
            $f['status'] === 'waspada' => 'cemas',
            (bool) ($g['tantangan']['selesai'] ?? false) => 'bangga',
            default => 'senang',
        };

        return view('beranda.index', compact(
            'user', 'wallets', 'f', 'transaksi', 'piutang', 'utang', 'rencanaDekat', 'targetAktif', 'tagihanTeman', 'g', 'petDipakai',
            'mood', 'jajanHariIni', 'batasHarian'
        ));
    }
}
