<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\PantauController;
use App\Http\Middleware\CekPeran;
use Illuminate\Support\Facades\Route;

// Halaman awal: yang sudah login diarahkan sesuai peran, yang belum ke halaman masuk.
Route::get('/', fn () => auth()->check()
    ? redirect(auth()->user()->halamanAwal())
    : redirect()->route('login'));

// Dipakai Laravel untuk mengarahkan user yang sudah login saat membuka /masuk.
Route::get('/dashboard', fn () => redirect(auth()->user()->halamanAwal()))
    ->middleware('auth')->name('dashboard');

// ---------- Tamu (belum login) ----------
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'tampilLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');   // maksimal 5 percobaan per menit
    Route::get('/daftar', [AuthController::class, 'tampilDaftar'])->name('daftar');
    Route::post('/daftar', [AuthController::class, 'daftar'])->middleware('throttle:5,1');
});

// ---------- Sudah login ----------
Route::middleware('auth')->group(function () {
    Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');

    Route::middleware(CekPeran::class . ':mahasiswa')->group(function () {
        Route::get('/beranda', [BerandaController::class, 'index'])->name('beranda');
    });

    Route::middleware(CekPeran::class . ':orangtua')->group(function () {
        Route::get('/pantau', [PantauController::class, 'index'])->name('pantau');
        Route::post('/pantau', [PantauController::class, 'hubungkan'])->name('pantau.hubungkan');
    });

    Route::middleware(CekPeran::class . ':admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
    });
});
