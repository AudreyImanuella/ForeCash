<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AkunController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PetualanganController;
use App\Http\Controllers\PrediksiController;
use App\Http\Controllers\RencanaController;
use App\Http\Controllers\UtangController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\LupaSandiController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\PantauController;
use App\Http\Controllers\TransaksiController;
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

    // lupa & reset kata sandi (nama route mengikuti bawaan Laravel supaya link di email otomatis benar)
    Route::get('/lupa-sandi', [LupaSandiController::class, 'tampilLupa'])->name('password.request');
    Route::post('/lupa-sandi', [LupaSandiController::class, 'kirimLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-sandi/{token}', [LupaSandiController::class, 'tampilReset'])->name('password.reset');
    Route::post('/reset-sandi', [LupaSandiController::class, 'simpan'])->middleware('throttle:5,1')->name('password.update');
});

// ---------- Sudah login ----------
Route::middleware('auth')->group(function () {
    Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');

    // Akun: semua peran
    Route::get('/akun', [AkunController::class, 'index'])->name('akun');
    Route::put('/akun/profil', [AkunController::class, 'profil'])->name('akun.profil');
    Route::put('/akun/password', [AkunController::class, 'password'])->name('akun.password');

    Route::middleware(CekPeran::class . ':mahasiswa')->group(function () {
        Route::get('/beranda', [BerandaController::class, 'index'])->name('beranda');

        // kode hubung & akses orang tua
        Route::post('/akun/kode', [AkunController::class, 'kodeBaru'])->name('akun.kode');
        Route::delete('/akun/orangtua/{orangtua}', [AkunController::class, 'cabut'])->name('akun.cabut');

        // Transaksi. Route tetap (batas, export) didaftarkan sebelum resource supaya tidak dianggap {transaksi}.
        Route::put('/transaksi/batas', [TransaksiController::class, 'batasHarian'])->name('transaksi.batas');
        Route::get('/transaksi/export', [TransaksiController::class, 'export'])->name('transaksi.export');
        Route::resource('transaksi', TransaksiController::class)
            ->except(['create', 'show'])
            ->parameters(['transaksi' => 'transaksi']);
        Route::get('/transaksi/{transaksi}/struk', [TransaksiController::class, 'struk'])->name('transaksi.struk');

        Route::post('/kategori', [KategoriController::class, 'store'])->name('kategori.store');
        Route::delete('/kategori/{kategori}', [KategoriController::class, 'destroy'])->name('kategori.destroy');

        Route::resource('wallet', WalletController::class)->only(['index', 'store', 'update', 'destroy']);

        // Utang & Patungan + Teman ForeCash
        Route::get('/utang', [UtangController::class, 'index'])->name('utang.index');
        Route::post('/utang', [UtangController::class, 'store'])->name('utang.store');
        Route::post('/utang/split', [UtangController::class, 'split'])->name('utang.split');
        Route::post('/utang/{utang}/lunas', [UtangController::class, 'lunas'])->name('utang.lunas');
        Route::post('/utang/{utang}/pelunasan', [UtangController::class, 'catatPelunasan'])->name('utang.pelunasan');
        Route::delete('/utang/{utang}', [UtangController::class, 'destroy'])->name('utang.destroy');
        Route::post('/teman', [UtangController::class, 'tambahTeman'])->name('teman.store');
        Route::delete('/teman/{teman}', [UtangController::class, 'hapusTeman'])->name('teman.destroy');

        // Rencana: anggaran, target tabungan, rencana pengeluaran
        Route::get('/rencana', [RencanaController::class, 'index'])->name('rencana.index');
        Route::post('/rencana', [RencanaController::class, 'tambahRencana'])->name('rencana.store');
        Route::post('/rencana/anggaran', [RencanaController::class, 'anggaran'])->name('rencana.anggaran');
        Route::post('/rencana/target', [RencanaController::class, 'tambahTarget'])->name('rencana.target');
        Route::post('/rencana/target/{target}/setor', [RencanaController::class, 'setor'])->name('rencana.setor');
        Route::delete('/rencana/target/{target}', [RencanaController::class, 'hapusTarget'])->name('rencana.target.hapus');
        Route::post('/rencana/{rencana}/bayar', [RencanaController::class, 'bayar'])->name('rencana.bayar');
        Route::delete('/rencana/{rencana}', [RencanaController::class, 'hapusRencana'])->name('rencana.hapus');

        // Prediksi (jadwal pemasukan) & Laporan
        Route::get('/prediksi', [PrediksiController::class, 'index'])->name('prediksi');
        Route::post('/prediksi', [PrediksiController::class, 'store'])->name('prediksi.store');
        Route::post('/prediksi/{jadwal}/terima', [PrediksiController::class, 'terima'])->name('prediksi.terima');
        Route::delete('/prediksi/{jadwal}', [PrediksiController::class, 'destroy'])->name('prediksi.hapus');
        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan');

        // Gamifikasi: Petualangan (peta, telur, pet) & Pencapaian (badge)
        Route::get('/petualangan', [PetualanganController::class, 'index'])->name('petualangan');
        Route::post('/petualangan/pakai', [PetualanganController::class, 'pakai'])->name('petualangan.pakai');
        Route::get('/pencapaian', [PetualanganController::class, 'pencapaian'])->name('pencapaian');

        // ForeCash Assistant (chatbot berbasis aturan)
        Route::post('/chatbot', ChatbotController::class)->middleware('throttle:30,1')->name('chatbot');
    });

    Route::middleware(CekPeran::class . ':orangtua')->group(function () {
        Route::get('/pantau', [PantauController::class, 'index'])->name('pantau');
        Route::post('/pantau', [PantauController::class, 'hubungkan'])->name('pantau.hubungkan');
    });

    Route::middleware(CekPeran::class . ':admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
    });
});
