<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Kelola Wallet mahasiswa (port dari app/(app)/wallet versi Next.js). */
class WalletController extends Controller
{
    public function index(Request $request): View
    {
        $wallets = $request->user()->wallets()->orderBy('id')->get()
            ->each(fn ($w) => $w->saldo_sekarang = $w->saldo());

        return view('wallet.index', compact('wallets'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->user()->wallets()->create($this->isian($request));

        return back()->with('sukses', 'Wallet ditambahkan.');
    }

    public function update(Request $request, Wallet $wallet): RedirectResponse
    {
        $this->pastikanMilik($request, $wallet);
        $data = $this->isian($request);

        // saldo awal baru tidak boleh membuat saldo sekarang jadi minus
        $mutasi = $wallet->saldo() - (float) $wallet->saldo_awal;
        if ($data['saldo_awal'] + $mutasi < 0) {
            return back()->with('error', 'Saldo awal ' . $wallet->nama . ' minimal ' . Format::rupiah(-$mutasi)
                . ', karena transaksi di wallet ini sudah memakai uang sebanyak itu. Saldo tidak boleh minus.');
        }

        $wallet->update($data);

        return back()->with('sukses', 'Wallet diperbarui.');
    }

    public function destroy(Request $request, Wallet $wallet): RedirectResponse
    {
        $this->pastikanMilik($request, $wallet);
        $user = $request->user();

        if ($user->wallets()->count() <= 1) {
            return back()->with('error', 'Minimal harus ada satu wallet.');
        }
        $dipakai = $user->transactions()
            ->where(fn ($q) => $q->where('wallet_id', $wallet->id)->orWhere('wallet_tujuan_id', $wallet->id))
            ->exists();
        if ($dipakai) {
            return back()->with('error', 'Wallet masih dipakai di transaksi, tidak dapat dihapus.');
        }

        $wallet->delete();

        return back()->with('sukses', 'Wallet dihapus.');
    }

    /** Validasi isian form wallet (tambah & ubah). */
    private function isian(Request $request): array
    {
        $request->merge(['saldo_awal' => Format::bacaNominal($request->input('saldo_awal'))]);
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:50'],
            'tipe' => ['required', Rule::in(array_keys(Wallet::TIPE))],
            'saldo_awal' => ['numeric', 'min:0', 'max:999999999999'],
        ], [
            'nama.required' => 'Nama wallet wajib diisi.',
            'nama.max' => 'Nama wallet maksimal 50 karakter.',
            'tipe.in' => 'Tipe wallet tidak dikenal.',
            'saldo_awal.min' => 'Saldo awal tidak boleh minus.',
            'saldo_awal.max' => 'Saldo awal terlalu besar.',
        ]);

        return ['nama' => trim($data['nama']), 'tipe' => $data['tipe'], 'saldo_awal' => (float) $data['saldo_awal']];
    }

    private function pastikanMilik(Request $request, Wallet $wallet): void
    {
        abort_unless((int) $wallet->user_id === (int) $request->user()->id, 404);
    }
}
