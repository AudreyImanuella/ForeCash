<?php

namespace App\Http\Controllers;

use App\Services\Forecast;
use App\Support\Format;
use App\Support\Gamify;
use App\Support\Pets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Petualangan (peta perjalanan, telur, koleksi teman hemat) dan Pencapaian (level, streak, badge).
 * Port dari app/(app)/petualangan dan app/(app)/pencapaian versi Next.js.
 */
class PetualanganController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $hari = Format::hariIni();
        $f = Forecast::untuk($user);

        // koleksi yang sudah dimiliki: pet_id => tanggal menetas
        $owned = [];
        $dipakai = null;
        foreach ($user->pets()->orderBy('menetas_pada')->orderBy('id')->get() as $p) {
            if (! isset(Pets::PETS[$p->pet_id])) {
                continue;
            }
            $owned[$p->pet_id] = $p->menetas_pada->toDateString();
            if ($p->dipakai) {
                $dipakai = $p->pet_id;
            }
        }

        // telur: hari baik sejak hewan terakhir menetas
        $transaksi = $user->transactions()->where('tanggal', '>', Forecast::tambahHari($hari, -120))
            ->toBase()->get(['tanggal', 'jenis', 'nominal'])
            ->map(fn ($t) => ['tanggal' => substr((string) $t->tanggal, 0, 10), 'jenis' => $t->jenis, 'nominal' => (float) $t->nominal]);
        $hariBaik = Pets::hitungHariBaik($transaksi, (float) $user->batas_harian);
        $progress = Pets::progresTelur($hariBaik, array_values($owned));

        // telur penuh -> menetas jadi teman acak yang belum dimiliki
        $baru = null;
        $belumPunya = array_values(array_diff(array_keys(Pets::PETS), array_keys($owned)));
        if ($progress >= Pets::TELUR_TARGET && $belumPunya) {
            $pilih = $belumPunya[random_int(0, count($belumPunya) - 1)];
            $dibuat = $user->pets()->firstOrCreate(['pet_id' => $pilih], ['menetas_pada' => $hari, 'dipakai' => ! $dipakai]);
            if ($dibuat->wasRecentlyCreated) {
                $baru = $pilih;
                $owned[$pilih] = $hari;
                $dipakai ??= $pilih;
                $progress = 0;
            }
        }

        // data peta
        $keluarPerHari = [];
        foreach ($transaksi as $t) {
            if ($t['jenis'] === 'pengeluaran') {
                $keluarPerHari[$t['tanggal']] = ($keluarPerHari[$t['tanggal']] ?? 0) + $t['nominal'];
            }
        }
        $pemasukanTerakhir = $user->transactions()->where('jenis', 'pemasukan')->where('tanggal', '<=', $hari)->max('tanggal');
        $j = Pets::dataPeta(
            $hari, $f['next']['tanggal_perkiraan'] ?? null, $pemasukanTerakhir ? substr((string) $pemasukanTerakhir, 0, 10) : null,
            $f['saldo'], $f['runway'], $keluarPerHari, array_flip($transaksi->pluck('tanggal')->all()), $hariBaik,
        );
        $hitung = fn ($s) => count(array_filter($j['hari'], fn ($h) => $h['status'] === $s));
        $hariKe = array_search('hariini', array_column($j['hari'], 'status'), true);

        return view('petualangan.index', [
            'user' => $user, 'f' => $f, 'j' => $j, 'owned' => $owned, 'dipakai' => $dipakai, 'baru' => $baru,
            'progress' => $progress, 'hariKe' => $hariKe === false ? null : $hariKe + 1,
            'jumlahBaik' => $hitung('baik'), 'jumlahBoros' => $hitung('boros'),
        ]);
    }

    /** Pilih teman hemat yang menemani di peta & banner (hanya satu). */
    public function pakai(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->validate(['pet' => ['required', Rule::in(array_keys(Pets::PETS))]], ['pet.in' => 'Teman hemat tidak dikenal.']);
        $pet = $request->input('pet');

        if (! $user->pets()->where('pet_id', $pet)->exists()) {
            return back()->with('error', 'Teman hemat ini belum menetas.');
        }
        DB::transaction(function () use ($user, $pet) {
            $user->pets()->update(['dipakai' => false]);
            $user->pets()->where('pet_id', $pet)->update(['dipakai' => true]);
        });

        return back()->with('sukses', Pets::PETS[$pet]['nama'] . ' sekarang menemanimu.');
    }

    public function pencapaian(Request $request): View
    {
        $user = $request->user();
        $g = Gamify::untuk($user);
        $urut = $g['badges'];
        usort($urut, fn ($a, $b) => $b['tingkat'] <=> $a['tingkat'] ?: $b['persen'] <=> $a['persen']);

        return view('pencapaian.index', [
            'user' => $user, 'g' => $g, 'urut' => $urut,
            'koleksi' => array_sum(array_column($g['badges'], 'tingkat')),
        ]);
    }
}
