{{-- Minta link reset kata sandi (port dari app/lupa-password/page.tsx versi Next.js). --}}
@extends('layouts.auth')

@section('judul', 'Lupa Kata Sandi')
@section('judul_auth', 'Lupa Kata Sandi')
@section('sub_auth', 'Masukkan email akunmu. Kami kirim link untuk membuat kata sandi baru.')

@section('isi')
<div class="hs-card p-6 sm:p-8 space-y-5 text-left">
    @if (session('sukses'))
        <p class="text-sm font-bold text-emerald-700 bg-emerald-50 rounded-xl px-3 py-3" role="status">{{ session('sukses') }}</p>
    @else
        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            @if ($errors->any())
                <p class="text-sm font-bold text-hsRed" role="alert">{{ $errors->first() }}</p>
            @endif
            <div class="space-y-1">
                <label for="email" class="text-xs font-bold uppercase tracking-wider">Email</label>
                <input id="email" type="email" name="email" required value="{{ old('email') }}" placeholder="nama@email.com"
                       autocomplete="email" class="w-full px-4 py-3 border text-sm font-semibold">
            </div>
            <button type="submit" class="hs-btn bg-hsOrange text-white w-full py-3.5 text-base">Kirim Link Reset</button>
        </form>
    @endif
    <p class="text-center text-sm"><a href="{{ route('login') }}" class="font-bold text-hsOrange">Kembali ke halaman masuk</a></p>
</div>
@endsection
