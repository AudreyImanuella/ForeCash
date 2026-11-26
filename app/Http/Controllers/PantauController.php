<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Forecast;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Pantau Anak, khusus orang tua (port dari app/(app)/pantau/page.tsx versi Next.js). */
class PantauController extends Controller
{
    public function index(Request $request): View
    {
        $anakList = $request->user()->anak()->orderBy('name')->get();

        // hanya anak yang terhubung yang bisa dipilih (?anak=id)
        $anak = $anakList->firstWhere('id', (int) $request->query('anak')) ?? $anakList->first();

        $data = null;
        if ($anak) {
            $bulanIni = substr(Format::hariIni(), 0, 7);   // '2026-11'
            $ringkas = $anak->transactions()
                ->where('tanggal', 'like', $bulanIni . '-%')
                ->selectRaw('jenis, SUM(nominal) AS total')->groupBy('jenis')->pluck('total', 'jenis');

            $data = [
                'f' => Forecast::untuk($anak),
                'masuk' => (float) ($ringkas['pemasukan'] ?? 0),
                'keluar' => (float) ($ringkas['pengeluaran'] ?? 0),
                'transaksi' => $anak->transactions()->with('category')->where('jenis', '!=', 'transfer')
                    ->orderByDesc('tanggal')->orderByDesc('id')->limit(8)->get(),
                'laporan' => \App\Support\Laporan::untuk($anak, $bulanIni),
                'namaBulan' => Format::namaBulan($bulanIni),
            ];
        }

        return view('pantau.index', compact('anakList', 'anak', 'data'));
    }

    /** Orang tua memasukkan kode hubung dari akun anak. */
    public function hubungkan(Request $request): RedirectResponse
    {
        $data = $request->validate(
            ['kode' => ['required', 'string', 'max:8']],
            ['kode.required' => 'Masukkan kode hubung dari akun anak.']
        );

        $anak = User::where('role', 'mahasiswa')
            ->where('kode_hubung', Str::upper(trim($data['kode'])))
            ->first();

        if (! $anak) {
            return back()->with('error', 'Kode tidak ditemukan. Cek lagi di halaman Akun anak.');
        }

        // syncWithoutDetaching: tambah hubungan tanpa menghapus yang sudah ada, dan tidak dobel
        $request->user()->anak()->syncWithoutDetaching([$anak->id]);

        return redirect()->route('pantau', ['anak' => $anak->id])
            ->with('sukses', "Terhubung dengan {$anak->name}.");
    }
}
