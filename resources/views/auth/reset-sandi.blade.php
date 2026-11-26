{{-- Buat kata sandi baru dari link email (port dari app/reset-password/page.tsx versi Next.js). --}}
@extends('layouts.auth')

@section('judul', 'Kata Sandi Baru')
@section('judul_auth', 'Kata Sandi Baru')
@section('sub_auth', 'Buat kata sandi baru untuk akunmu (minimal 8 karakter).')

@section('isi')
<div class="hs-card p-6 sm:p-8 space-y-5 text-left">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        @if ($errors->any())
            <p class="text-sm font-bold text-hsRed" role="alert">{{ $errors->first() }}</p>
        @endif
        <div class="space-y-1">
            <label for="email" class="text-xs font-bold uppercase tracking-wider">Email</label>
            <input id="email" type="email" name="email" required value="{{ old('email', $email) }}" autocomplete="email"
                   class="w-full px-4 py-3 border text-sm font-semibold">
        </div>
        <div class="space-y-1">
            <label for="password" class="text-xs font-bold uppercase tracking-wider">Kata Sandi Baru</label>
            <x-input-sandi id="password" name="password" required minlength="8" autocomplete="new-password" />
        </div>
        <div class="space-y-1">
            <label for="password_confirmation" class="text-xs font-bold uppercase tracking-wider">Ulangi Kata Sandi</label>
            <x-input-sandi id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password" />
        </div>
        <button type="submit" class="hs-btn bg-hsOrange text-white w-full py-3.5 text-base">Simpan Kata Sandi</button>
    </form>
</div>
@endsection
