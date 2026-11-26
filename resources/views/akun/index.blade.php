{{-- Akun (port dari app/(app)/akun/page.tsx + components/FormAkun.tsx versi Next.js).
     Bisa dibuka semua peran; bagian kode hubung hanya untuk mahasiswa. --}}
@extends('layouts.app')
@use('App\Http\Controllers\AkunController')

@section('judul', 'Akun')
@section('judul_halaman', 'Akun')

@section('isi')
    <div><p class="text-xs text-slate-500">Kelola profil, kata sandi, dan akses orang tua.</p></div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="space-y-6">
            <form method="POST" action="{{ route('akun.profil') }}" class="hs-card p-6 space-y-3">
                @csrf
                @method('PUT')
                <h4 class="text-sm font-black border-b pb-2">Profil</h4>
                @if ($errors->profil->any())
                    <p class="fc-flash is-error" role="alert">{{ $errors->profil->first() }}</p>
                @endif
                <div>
                    <label for="an" class="text-xs font-bold">Nama</label>
                    <input id="an" name="name" value="{{ old('name', $user->name) }}" required maxlength="100" class="w-full px-3 py-2 text-xs border">
                </div>
                <div>
                    <label for="ae" class="text-xs font-bold">Email</label>
                    <input id="ae" type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full px-3 py-2 text-xs border">
                </div>
                <div>
                    <p class="text-xs font-bold mb-1">Avatar</p>
                    <div class="grid grid-cols-6 gap-2">
                        @foreach (AkunController::AVATAR as $id => $nama)
                            <label class="cursor-pointer text-center">
                                <input type="radio" name="avatar" value="{{ $id }}" class="sr-only peer" @checked(old('avatar', $user->avatar) === $id)>
                                <span class="block rounded-2xl border-2 p-1.5 transition-all border-transparent bg-slate-50 peer-checked:border-hsOrange peer-checked:bg-orange-50! peer-focus-visible:outline-3 peer-focus-visible:outline-hsBlue">
                                    <x-avatar :id="$id" :size="44" />
                                </span>
                                <span class="text-[10px] font-bold text-slate-500">{{ $nama }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <p class="text-[11px] text-slate-500">Peran: <b>{{ ['mahasiswa' => 'Mahasiswa', 'orangtua' => 'Orang Tua', 'admin' => 'Admin'][$user->role] ?? $user->role }}</b></p>
                <button type="submit" class="hs-btn bg-hsBlue text-white w-full py-2 text-xs">Simpan Profil</button>
            </form>

            <form method="POST" action="{{ route('akun.password') }}" class="hs-card p-6 space-y-3">
                @csrf
                @method('PUT')
                <h4 class="text-sm font-black border-b pb-2">Ganti Kata Sandi</h4>
                @if ($errors->sandi->any())
                    <p class="fc-flash is-error" role="alert">{{ $errors->sandi->first() }}</p>
                @endif
                <div>
                    <label for="lama" class="text-xs font-bold">Kata sandi lama</label>
                    <x-input-sandi id="lama" name="lama" required autocomplete="current-password" />
                </div>
                <div>
                    <label for="baru" class="text-xs font-bold">Kata sandi baru <span class="font-normal text-slate-400">(min. 8 karakter)</span></label>
                    <x-input-sandi id="baru" name="baru" required minlength="8" autocomplete="new-password" />
                </div>
                <button type="submit" class="hs-btn bg-slate-800 text-white w-full py-2 text-xs">Ganti</button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="hs-btn bg-hsRed text-white w-full py-2.5 text-xs"><x-ikon nama="log-out" :size="14" /> Keluar Akun</button>
            </form>
        </div>

        <div class="lg:col-span-2 space-y-6">
            @if ($user->isMahasiswa())
                <div class="hs-card p-6 space-y-3">
                    <h4 class="text-sm font-black border-b pb-2"><x-ikon nama="link" :size="15" class="inline -mt-0.5 text-hsPurple" /> Hubungkan dengan Orang Tua</h4>
                    <p class="text-xs text-slate-600">Bagikan kode ini ke orang tua. Setelah memasukkan kode di akunnya, orang tua dapat melihat ringkasan keuanganmu tanpa bisa mengubah data.</p>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-2xl font-black tracking-[.3em] bg-purple-50 text-hsPurple px-4 py-2 rounded-2xl">{{ $user->kode_hubung ?? '-' }}</span>
                        <form method="POST" action="{{ route('akun.kode') }}" data-konfirmasi="Buat kode baru? Kode lama tidak berlaku lagi.">
                            @csrf
                            <button type="submit" class="hs-btn bg-slate-200 px-3 py-2 text-xs">Buat kode baru</button>
                        </form>
                    </div>
                    @forelse ($ortu as $o)
                        <form method="POST" action="{{ route('akun.cabut', $o) }}" class="flex justify-between items-center gap-2 bg-slate-50 rounded-2xl px-4 py-2"
                              data-konfirmasi="Cabut akses {{ $o->name }}?">
                            @csrf
                            @method('DELETE')
                            <span class="text-xs min-w-0"><b>{{ $o->name }}</b> · {{ $o->email }}</span>
                            <button type="submit" class="text-xs font-bold text-hsRed shrink-0">Cabut akses</button>
                        </form>
                    @empty
                        <p class="text-[11px] text-slate-500">Belum ada orang tua yang terhubung.</p>
                    @endforelse
                </div>
            @endif

            <div class="hs-card p-6 space-y-3 text-xs">
                <h4 class="text-sm font-black border-b pb-2">Bantuan &amp; FAQ</h4>
                @foreach (AkunController::FAQ as [$tanya, $jawab])
                    <div class="p-3 bg-slate-50 rounded-2xl">
                        <p class="font-bold">{{ $tanya }}</p>
                        <p class="text-slate-600">{{ $jawab }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
