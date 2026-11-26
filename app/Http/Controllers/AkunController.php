<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Halaman Akun: profil, kata sandi, kode hubung orang tua (port dari app/(app)/akun versi Next.js). */
class AkunController extends Controller
{
    public const AVATAR = ['a1' => 'Koko', 'a2' => 'Rara', 'a3' => 'Bima', 'a4' => 'Lili', 'a5' => 'Dodo', 'a6' => 'Nana'];

    public const FAQ = [
        ['Bagaimana ForeCash menghitung tanggal saldo habis?', 'Total saldo dibagi burn rate, yaitu rata-rata pengeluaran harian dalam 14 hari terakhir. Hasilnya dibandingkan dengan jadwal pemasukan berikutnya.'],
        ['Apakah wallet wajib diisi?', 'Tidak. Semua transaksi otomatis masuk ke wallet "Utama". Tambah wallet lain hanya jika ingin memisahkan tunai, bank, dan e-wallet.'],
        ['Kenapa isi e-wallet dari rekening tidak dicatat sebagai pengeluaran?', 'Gunakan jenis "Transfer". Uang hanya berpindah tempat, jadi tidak mengubah total saldo maupun burn rate.'],
        ['Apa yang bisa dilihat orang tua?', 'Status peringatan, total saldo, burn rate, transaksi terakhir, dan laporan bulanan. Orang tua tidak dapat menambah atau mengubah data.'],
        ['Apakah ForeCash terhubung ke rekening bank atau e-wallet?', 'Tidak. Semua transaksi dicatat manual sehingga data rekening tetap aman.'],
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $ortu = $user->isMahasiswa()
            ? $user->orangtua()->orderBy('name')->get(['users.id', 'users.name', 'users.email'])
            : collect();

        return view('akun.index', compact('user', 'ortu'));
    }

    public function profil(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validateWithBag('profil', [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'avatar' => ['required', Rule::in(array_keys(self::AVATAR))],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum benar.',
            'email.unique' => 'Email sudah dipakai akun lain.',
            'avatar.in' => 'Avatar tidak dikenal.',
        ]);

        $user->update(['name' => trim($data['name']), 'email' => Str::lower($data['email']), 'avatar' => $data['avatar']]);

        return back()->with('sukses', 'Profil diperbarui.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('sandi', [
            'lama' => ['required', 'current_password'],
            'baru' => ['required', 'string', 'min:8', 'different:lama'],
        ], [
            'lama.required' => 'Masukkan kata sandi lama.',
            'lama.current_password' => 'Kata sandi lama salah.',
            'baru.required' => 'Masukkan kata sandi baru.',
            'baru.min' => 'Kata sandi baru minimal 8 karakter.',
            'baru.different' => 'Kata sandi baru harus berbeda dari yang lama.',
        ]);

        // di-hash otomatis lewat cast 'hashed' di model User
        $request->user()->update(['password' => $data['baru']]);

        return back()->with('sukses', 'Kata sandi diganti.');
    }

    /** Buat kode hubung baru; kode lama tidak berlaku lagi. */
    public function kodeBaru(Request $request): RedirectResponse
    {
        do {
            $kode = Str::upper(Str::random(8));
        } while (User::where('kode_hubung', $kode)->exists());

        $request->user()->update(['kode_hubung' => $kode]);

        return back()->with('sukses', 'Kode hubung baru dibuat. Kode lama tidak berlaku lagi.');
    }

    /** Cabut akses satu orang tua. */
    public function cabut(Request $request, User $orangtua): RedirectResponse
    {
        $request->user()->orangtua()->detach($orangtua->id);

        return back()->with('sukses', 'Akses orang tua dicabut.');
    }
}
