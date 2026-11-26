{{-- Login & Registrasi dalam satu halaman — desain baru (Nov 2026).
     Kedua form selalu ada; tab menukar form di tempat (animasi geser, tanpa memuat ulang halaman)
     dan mengganti alamat jadi /login atau /daftar. Tanpa JavaScript, tab tetap berfungsi sebagai link biasa.
     Layar pembukaan "Meet Chip." muncul sekali per sesi browser (tidak muncul kalau ada pesan/error).
     $mode = 'masuk' (route login) atau 'daftar' (route daftar). --}}
@extends('layouts.auth')

@php
    $TEKS = [
        'masuk' => ['Welcome back!', 'Masuk dulu, yuk cek uangmu.', 'Login'],
        'daftar' => ['Hi, new friend!', 'Bikin akun dulu, gratis kok.', 'Registrasi'],
    ];
@endphp

@section('judul', $TEKS[$mode][2])
@section('judul_auth', $TEKS[$mode][0])
@section('sub_auth', $TEKS[$mode][1])

@if ($mode === 'masuk' && ! $errors->any() && ! session('sukses') && ! old('email'))
    @section('pembukaan')
        <section class="ax-intro" id="axIntro" aria-label="Pembukaan" hidden>
            <span class="ax-logo"><x-logo width="46" height="46" /><span class="fb-d">ForeCash</span></span>
            <p class="ax-intro-tanya">Uang saku suka habis duluan sebelum kiriman datang? Same.</p>
            <h1 class="ax-intro-judul fb-d">Meet<br>Chip.</h1>
            <p class="ax-intro-sub">Your little money buddy. Chip bantu hitung sampai kapan uangmu cukup, and kasih heads-up sebelum terlambat.</p>
            <button type="button" class="fb-btn fb-btn-dark ax-intro-btn" id="axMulai">Let's go</button>
            <div class="ax-intro-bukit" aria-hidden="true"></div>
            <x-tupai pose="melambai" alt="Chip, maskot ForeCash, melambai" class="ax-intro-chip" loading="eager" />
        </section>
        <script>
            // tampil sekali per sesi; langsung (tanpa menunggu halaman selesai) supaya form tidak sempat berkedip
            (function () {
                var intro = document.getElementById('axIntro');
                var sudah = false;
                try { sudah = sessionStorage.getItem('fcIntro') === '1'; } catch (e) {}
                if (sudah) { intro.remove(); return; }
                intro.hidden = false;
                document.documentElement.classList.add('ax-kunci');
                document.getElementById('axMulai').addEventListener('click', function () {
                    try { sessionStorage.setItem('fcIntro', '1'); } catch (e) {}
                    intro.classList.add('is-pergi');
                    document.documentElement.classList.remove('ax-kunci');
                    setTimeout(function () { intro.remove(); document.getElementById('email')?.focus(); }, 350);
                });
            })();
        </script>
    @endsection
@endif

@section('isi')
<div class="ax-auth" data-auth data-mode="{{ $mode }}"
     data-teks='@json($TEKS)' data-url-masuk="{{ route('login') }}" data-url-daftar="{{ route('daftar') }}">
    {{-- tab Login / Registrasi (indikator gelap bergeser) --}}
    <div class="ax-tab" role="tablist">
        <a href="{{ route('login') }}" role="tab" data-tab="masuk" aria-selected="{{ $mode === 'masuk' ? 'true' : 'false' }}">Login</a>
        <a href="{{ route('daftar') }}" role="tab" data-tab="daftar" aria-selected="{{ $mode === 'daftar' ? 'true' : 'false' }}">Registrasi</a>
    </div>

    @if ($errors->any())
        <p class="ax-pesan is-error" role="alert" data-pesan="{{ $mode }}">{{ $errors->first() }}</p>
    @endif
    @if (session('sukses'))
        <p class="ax-pesan" role="status" data-pesan="{{ $mode }}">{{ session('sukses') }}</p>
    @endif

    {{-- ===== Login ===== --}}
    <form method="POST" action="{{ route('login') }}" class="ax-form ax-pane" data-pane="masuk" novalidate>
        @csrf
        <div>
            <label for="email" class="fx-label">Email</label>
            <input id="email" name="email" type="email" value="{{ $mode === 'masuk' ? old('email') : '' }}" required placeholder="nama@email.com" autocomplete="email" class="fx-input">
        </div>
        <div>
            <label for="password" class="fx-label">Kata sandi</label>
            <x-input-sandi id="password" name="password" required class="fx-input" autocomplete="current-password" />
        </div>
        <label class="ax-ingat"><input type="checkbox" name="ingat" value="1"> Remember me</label>
        <button type="submit" class="fb-btn fb-btn-dark fx-submit">Masuk</button>

        {{-- akun demo hanya tampil saat aplikasi berjalan di laptop (APP_ENV=local) --}}
        @env('local')
            <div class="ax-demo">
                <p><b>Akun demo</b> (kata sandi: demo12345)</p>
                <p class="fx-meta">demo@forecash.id · ortu@forecash.id · admin@forecash.id</p>
            </div>
        @endenv
    </form>

    {{-- ===== Registrasi ===== --}}
    <form method="POST" action="{{ route('daftar') }}" class="ax-form ax-pane" data-pane="daftar" novalidate>
        @csrf
        <div>
            <label for="role" class="fx-label">Daftar sebagai</label>
            <select id="role" name="role" class="fx-input">
                <option value="mahasiswa" @selected(old('role') !== 'orangtua')>Mahasiswa</option>
                <option value="orangtua" @selected(old('role') === 'orangtua')>Orang Tua / Wali</option>
            </select>
        </div>
        <div>
            <label for="name" class="fx-label">Nama lengkap</label>
            <input id="name" name="name" value="{{ old('name') }}" maxlength="100" placeholder="Nama lengkap" required autocomplete="name" class="fx-input">
        </div>
        <fieldset>
            <legend class="fx-label">Pilih avatar</legend>
            <div class="ax-avatar">
                @foreach (['a1' => 'Koko', 'a2' => 'Rara', 'a3' => 'Bima', 'a4' => 'Lili', 'a5' => 'Dodo', 'a6' => 'Nana'] as $id => $nama)
                    <label>
                        <input type="radio" name="avatar" value="{{ $id }}" class="sr-only" @checked(old('avatar', 'a1') === $id)>
                        <span class="ax-avatar-img"><x-avatar :id="$id" :size="44" /></span>
                        <span class="ax-avatar-nama">{{ $nama }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>
        <div>
            <label for="d-email" class="fx-label">Email</label>
            <input id="d-email" name="email" type="email" value="{{ $mode === 'daftar' ? old('email') : '' }}" required placeholder="nama@email.com" autocomplete="email" class="fx-input">
        </div>
        <div>
            <label for="d-password" class="fx-label">Kata sandi <span class="fx-label-opt">(min. 8 karakter)</span></label>
            <x-input-sandi id="d-password" name="password" required minlength="8" class="fx-input" autocomplete="new-password" />
        </div>
        <button type="submit" class="fb-btn fb-btn-dark fx-submit">Buat akun</button>
    </form>
</div>

<script>
    // tukar Login <-> Registrasi di tempat: tanpa memuat ulang, posisi scroll tetap, alamat ikut berganti
    (function () {
        var box = document.querySelector('[data-auth]');
        var teks = JSON.parse(box.dataset.teks);
        var judul = document.querySelector('[data-auth-judul]');
        var sub = document.querySelector('[data-auth-sub]');
        function pindah(mode, simpan) {
            if (box.dataset.mode === mode) return;
            box.dataset.arah = mode === 'daftar' ? 'kanan' : 'kiri';
            box.dataset.mode = mode;
            box.querySelectorAll('[data-tab]').forEach(function (t) { t.setAttribute('aria-selected', t.dataset.tab === mode ? 'true' : 'false'); });
            box.querySelectorAll('[data-pesan]').forEach(function (p) { p.hidden = p.dataset.pesan !== mode; });
            judul.textContent = teks[mode][0];
            sub.textContent = teks[mode][1];
            document.title = document.title.replace(/^(Login|Registrasi)/, teks[mode][2]);
            if (simpan) history.pushState({ mode: mode }, '', mode === 'daftar' ? box.dataset.urlDaftar : box.dataset.urlMasuk);
        }
        box.querySelectorAll('[data-tab]').forEach(function (t) {
            t.addEventListener('click', function (e) { e.preventDefault(); pindah(t.dataset.tab, true); });
        });
        window.addEventListener('popstate', function () {
            pindah(location.pathname.replace(/\/$/, '').endsWith('/daftar') ? 'daftar' : 'masuk', false);
        });
    })();
</script>
@endsection
