{{-- Konfirmasi undangan teman (link /teman/tambah/{kode} dari QR atau tombol Bagikan).
     Pertemanan baru dibuat setelah user menekan "Ya, tambahkan", supaya membuka link saja tidak cukup. --}}
@extends('layouts.app')

@section('judul', 'Undangan teman')
@section('judul_halaman', 'Undangan teman')

@section('isi')
<div class="fb fx ux">
    <section class="ux-undangan">
        @if ($status === 'baru')
            <span class="ux-undangan-av"><x-avatar :id="$dia->avatar" :size="88" /></span>
            <h2 class="fx-form-title" style="padding-right: 0">Tambahkan {{ $dia->name }} sebagai teman?</h2>
            <p class="fx-sub">Setelah berteman, tagihan split bill bisa langsung masuk ke akun masing-masing, dan status lunasnya ikut sama di kedua akun.</p>
            <div class="ux-undangan-aksi">
                <form method="POST" action="{{ route('teman.store') }}">
                    @csrf
                    <input type="hidden" name="kode" value="{{ $kode }}">
                    <input type="hidden" name="dari_undangan" value="1">
                    <button type="submit" class="fb-btn fb-btn-dark">Ya, tambahkan</button>
                </form>
                <a href="{{ route('utang.index') }}" class="fb-btn fb-btn-light">Nanti saja</a>
            </div>
        @else
            <x-tupai :pose="$status === 'sudah' ? 'senang' : 'cemas'" alt="" class="ux-undangan-tupai" />
            <h2 class="fx-form-title" style="padding-right: 0">
                @if ($status === 'sudah') Kamu sudah berteman dengan {{ $dia->name }}
                @elseif ($status === 'diri-sendiri') Ini link undanganmu sendiri
                @else Link undangan tidak ditemukan
                @endif
            </h2>
            <p class="fx-sub">
                @if ($status === 'sudah') Kalian sudah bisa saling kirim tagihan split bill.
                @elseif ($status === 'diri-sendiri') Bagikan link ini ke temanmu supaya mereka bisa menambahkanmu.
                @else Kodenya mungkin salah ketik. Minta temanmu membagikan ulang link atau QR-nya.
                @endif
            </p>
            <div class="ux-undangan-aksi">
                <a href="{{ route('utang.index') }}" class="fb-btn fb-btn-dark">Ke Utang &amp; Patungan</a>
            </div>
        @endif
    </section>
</div>
@endsection
