{{-- Kerangka halaman login / registrasi / lupa sandi — desain baru (Nov 2026).
     Desktop: panel kuning (kiri) + form (kanan). HP: panel kuning di atas form.
     Halaman bisa menambah layar pembukaan lewat @section('pembukaan'). Maskot: Chip (gambar diam, ringan). --}}
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body class="antialiased">
@yield('pembukaan')
<div id="loginPage" class="fb fx ax">
    <section class="ax-panel" aria-label="Tentang ForeCash">
        <a href="{{ route('login') }}" class="ax-logo"><x-logo width="46" height="46" /><span class="fb-d">ForeCash</span></a>
        <h1 class="ax-panel-judul fb-d">Your money, aman sampai kiriman berikutnya.</h1>
        <p class="ax-panel-sub">Buat <b>mahasiswa</b> yang pengin uangnya cukup, dan <b>orang tua</b> yang mau ikut memantau.</p>
        <ul class="ax-poin">
            <li><span style="background: #C7ECF1"><x-ikon nama="cloud-sun" :size="16" /></span>Prediksi kapan saldo habis</li>
            <li><span style="background: #E3F2CF"><x-ikon nama="target" :size="16" /></span>Budget plan &amp; savings goal</li>
            <li><span style="background: #FDDDEB"><x-ikon nama="users" :size="16" /></span>Split bill bareng teman</li>
        </ul>
        <x-tupai pose="melambai" alt="" class="ax-chip" loading="eager" />
    </section>

    <main class="ax-isi">
        <div class="ax-kolom">
            <div>
                <h2 class="ax-judul fb-d" data-auth-judul>@yield('judul_auth', 'Welcome back!')</h2>
                <p class="ax-sub" data-auth-sub>@yield('sub_auth')</p>
            </div>
            @yield('isi')
        </div>
    </main>
</div>
</body>
</html>
