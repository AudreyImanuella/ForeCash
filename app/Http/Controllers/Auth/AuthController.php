<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    // ---------- Login ----------

    // Login dan Registrasi memakai satu halaman dengan dua tab (sama dengan LoginForm.tsx).
    public function tampilLogin(): View
    {
        return view('auth.login', ['mode' => 'masuk']);
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum benar.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        // Auth::attempt mencocokkan email + password (bcrypt) ke tabel users.
        if (! Auth::attempt($data, $request->boolean('ingat'))) {
            return back()
                ->withErrors(['email' => 'Email atau kata sandi salah.'])
                ->onlyInput('email');
        }

        // Ganti ID session setelah login supaya session lama tidak bisa dipakai orang lain.
        $request->session()->regenerate();

        return redirect()->intended($request->user()->halamanAwal());
    }

    // ---------- Daftar ----------

    public function tampilDaftar(): View
    {
        return view('auth.login', ['mode' => 'daftar']);
    }

    public function daftar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:mahasiswa,orangtua'],
            'avatar' => ['required', 'in:a1,a2,a3,a4,a5,a6'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 100 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum benar.',
            'email.unique' => 'Email ini sudah terdaftar. Coba masuk.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'role.required' => 'Pilih jenis akun.',
            'role.in' => 'Jenis akun tidak dikenal.',
            'avatar.required' => 'Pilih avatar.',
            'avatar.in' => 'Avatar tidak dikenal.',
        ]);

        // Simpan user + wallet pertama dalam satu transaksi database:
        // kalau salah satu gagal, dua-duanya dibatalkan.
        $user = DB::transaction(function () use ($data) {
            $mahasiswa = $data['role'] === 'mahasiswa';

            $user = User::create([
                'name' => $data['name'],
                'email' => Str::lower($data['email']),
                'password' => $data['password'],          // di-hash otomatis lewat casts()
                'role' => $data['role'],
                'avatar' => $data['avatar'],
                'kode_teman' => $mahasiswa ? User::buatKodeTeman() : null,
                'kode_hubung' => $mahasiswa ? Str::upper(Str::random(8)) : null,
            ]);

            if ($mahasiswa) {
                $user->wallets()->create(['nama' => 'Utama', 'tipe' => 'tunai', 'saldo_awal' => 0]);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect($user->halamanAwal())
            ->with('sukses', 'Akun berhasil dibuat. Selamat datang di ForeCash!');
    }

    // ---------- Logout ----------

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();       // hapus isi session
        $request->session()->regenerateToken();  // token CSRF baru

        return redirect()->route('login')->with('sukses', 'Kamu sudah keluar.');
    }
}
