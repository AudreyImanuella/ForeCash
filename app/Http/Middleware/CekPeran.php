<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi halaman berdasarkan peran (role) pengguna.
 * Contoh di routes/web.php: ->middleware(CekPeran::class.':mahasiswa')
 * atau beberapa peran sekaligus: CekPeran::class.':mahasiswa,admin'
 */
class CekPeran
{
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! in_array($user->role, $peran, true)) {
            return redirect($user->halamanAwal())
                ->with('error', 'Halaman itu bukan untuk jenis akun kamu.');
        }

        return $next($request);
    }
}
