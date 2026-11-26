<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Lupa & reset kata sandi (port dari app/lupa-password dan app/reset-password versi Next.js).
 * Memakai fitur reset bawaan Laravel (tabel password_reset_tokens, link berlaku 60 menit).
 * Email dikirim lewat MAIL_MAILER di .env; saat ini "log", jadi linknya muncul di storage/logs/laravel.log.
 */
class LupaSandiController extends Controller
{
    public function tampilLupa(): View
    {
        return view('auth.lupa-sandi');
    }

    public function kirimLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum benar.',
        ]);

        $status = Password::sendResetLink(['email' => Str::lower($request->input('email'))]);
        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput()->withErrors(['email' => 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.']);
        }

        // pesan selalu sama, jadi orang lain tidak bisa mengecek email mana yang terdaftar
        return back()->with('sukses', 'Jika email tersebut terdaftar, link reset sudah dikirim. Cek kotak masuk (atau folder spam). Link berlaku 1 jam.');
    }

    public function tampilReset(Request $request, string $token): View
    {
        return view('auth.reset-sandi', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                // di-hash otomatis lewat cast 'hashed' di model User
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Link reset tidak valid atau sudah kedaluwarsa. Minta link baru.']);
        }

        return redirect()->route('login')->with('sukses', 'Kata sandi diganti. Silakan masuk dengan kata sandi baru.');
    }
}
