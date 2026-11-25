{{-- Login & Registrasi dalam satu kartu dengan dua tab (port dari app/login/LoginForm.tsx).
     $mode = 'masuk' (route login) atau 'daftar' (route daftar). --}}
@extends('layouts.auth')

@section('judul', $mode === 'daftar' ? 'Registrasi' : 'Login')

@section('sub_auth')
    Untuk <span class="text-hsOrange font-extrabold">mahasiswa</span> yang ingin uangnya aman sampai kiriman berikutnya,
    dan <span class="text-hsBlue font-extrabold">orang tua</span> yang ingin ikut memantau.
@endsection

@section('isi')
<div class="hs-card p-6 sm:p-8 space-y-5 text-left">
    {{-- tab Login / Registrasi --}}
    <div class="flex bg-slate-100 p-1.5 rounded-2xl">
        <a href="{{ route('login') }}"
           class="w-1/2 py-2 text-sm font-bold rounded-xl text-center {{ $mode === 'masuk' ? 'bg-hsYellow text-hsDark shadow-sm' : 'text-slate-500' }}">Login</a>
        <a href="{{ route('daftar') }}"
           class="w-1/2 py-2 text-sm font-bold rounded-xl text-center {{ $mode === 'daftar' ? 'bg-hsYellow text-hsDark shadow-sm' : 'text-slate-500' }}">Registrasi</a>
    </div>

    {{-- pesan error validasi (cukup yang pertama, sama seperti versi Next.js) --}}
    @if ($errors->any())
        <p class="text-sm font-bold text-hsRed" role="alert">{{ $errors->first() }}</p>
    @endif
    @if (session('sukses'))
        <p class="text-sm font-bold text-emerald-700 bg-emerald-50 rounded-xl px-3 py-2" role="status">{{ session('sukses') }}</p>
    @endif

    <form method="POST" action="{{ $mode === 'daftar' ? route('daftar') : route('login') }}" class="space-y-4" novalidate>
        @csrf

        @if ($mode === 'daftar')
            <div class="space-y-1">
                <label for="role" class="text-xs font-bold uppercase tracking-wider">Daftar Sebagai</label>
                <select id="role" name="role" class="w-full px-4 py-3 border text-sm font-semibold">
                    <option value="mahasiswa" @selected(old('role') !== 'orangtua')>Mahasiswa</option>
                    <option value="orangtua" @selected(old('role') === 'orangtua')>Orang Tua / Wali</option>
                </select>
            </div>
            <div class="space-y-1">
                <label for="name" class="text-xs font-bold uppercase tracking-wider">Nama Lengkap</label>
                <input id="name" name="name" value="{{ old('name') }}" maxlength="100" placeholder="Nama lengkap" required
                       autocomplete="name" class="w-full px-4 py-3 border text-sm font-semibold">
            </div>
            <div class="space-y-1">
                <p class="text-xs font-bold uppercase tracking-wider">Pilih Avatar</p>
                {{-- pilihan avatar (port dari components/AvatarPicker.tsx) --}}
                <div class="grid grid-cols-6 gap-2">
                    @foreach (['a1' => 'Koko', 'a2' => 'Rara', 'a3' => 'Bima', 'a4' => 'Lili', 'a5' => 'Dodo', 'a6' => 'Nana'] as $id => $nama)
                        <label class="cursor-pointer text-center">
                            <input type="radio" name="avatar" value="{{ $id }}" class="sr-only peer" @checked(old('avatar', 'a1') === $id)>
                            <span class="block rounded-2xl border-2 p-1.5 transition-all border-transparent bg-slate-50 peer-checked:border-hsOrange peer-checked:bg-orange-50! peer-focus-visible:outline-3 peer-focus-visible:outline-hsBlue">
                                <x-avatar :id="$id" :size="44" />
                            </span>
                            <span class="text-[10px] font-bold text-slate-500">{{ $nama }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="space-y-1">
            <label for="email" class="text-xs font-bold uppercase tracking-wider">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required placeholder="nama@email.com"
                   autocomplete="email" class="w-full px-4 py-3 border text-sm font-semibold">
        </div>
        <div class="space-y-1">
            <label for="password" class="text-xs font-bold uppercase tracking-wider">Kata Sandi</label>
            <x-input-sandi id="password" name="password" required
                           :minlength="$mode === 'daftar' ? 8 : null"
                           :autocomplete="$mode === 'daftar' ? 'new-password' : 'current-password'" />
        </div>

        @if ($mode === 'masuk')
            <label class="flex items-center gap-2 text-xs font-bold">
                <input type="checkbox" name="ingat" value="1"> Ingat saya di perangkat ini
            </label>
        @endif

        <button type="submit" class="hs-btn bg-hsOrange text-white w-full py-3.5 text-base">
            {{ $mode === 'daftar' ? 'Buat Akun' : 'Masuk' }}
        </button>
    </form>

    {{-- akun demo hanya tampil saat aplikasi berjalan di laptop (APP_ENV=local) --}}
    @env('local')
        @if ($mode === 'masuk')
            <div class="rounded-2xl bg-slate-50 px-4 py-3 text-xs">
                <p class="font-bold">Akun demo (kata sandi: demo12345)</p>
                <p class="text-slate-500 mt-1">demo@forecash.id · ortu@forecash.id · admin@forecash.id</p>
            </div>
        @endif
    @endenv
</div>
@endsection
