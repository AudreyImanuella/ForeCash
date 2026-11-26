{{-- Kerangka halaman setelah login (port dari components/layout/AppShell.tsx versi Next.js).
     Desktop : sidebar krem melayang, bisa diciutkan jadi ikon saja (diingat lewat cookie fc_side).
     HP      : tab bar bawah berisi 4 menu utama + tombol "Menu" yang membuka laci berisi semua menu. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    {{-- halaman setelah login tidak perlu muncul di mesin pencari --}}
    <meta name="robots" content="noindex, nofollow">
</head>
@php
    $user = auth()->user();

    // [nama route, label, label pendek HP, ikon, warna] — urutan & warna sama dengan lib/navigation.ts
    $menus = match ($user->role) {
        'orangtua' => [['pantau', 'Pantau Anak', 'Pantau', 'eye', 'purple']],
        'admin' => [['admin.index', 'Kelola Pengguna', 'Pengguna', 'shield', 'orange']],
        default => [
            ['beranda', 'Beranda', 'Beranda', 'house', 'orange'],
            ['transaksi.index', 'Transaksi', 'Transaksi', 'receipt', 'blue'],
            ['utang.index', 'Utang & Patungan', 'Utang', 'users', 'purple'],
            ['rencana.index', 'Rencana', 'Rencana', 'clipboard', 'teal'],
            ['prediksi', 'Prediksi', 'Prediksi', 'cloud-sun', 'green'],
            ['laporan', 'Laporan', 'Laporan', 'chart-pie', 'pink'],
            ['petualangan', 'Petualangan', 'Peta', 'map', 'teal'],
            ['pencapaian', 'Pencapaian', 'Badge', 'trophy', 'yellow'],
        ],
    };
    $aktif = fn (string $rute) => request()->routeIs($rute) || request()->routeIs(\Illuminate\Support\Str::before($rute, '.') . '.*');
    $home = Route::has($menus[0][0]) ? route($menus[0][0]) : url('/');

    // tab bar HP: 4 menu utama
    $TAB_HP = ['beranda', 'transaksi.index', 'utang.index', 'rencana.index', 'pantau', 'admin.index'];
    $tabHp = array_slice(array_values(array_filter($menus, fn ($m) => in_array($m[0], $TAB_HP))), 0, 4);
    $laciAktif = collect($tabHp)->doesntContain(fn ($m) => $aktif($m[0]));

    // sidebar ciut dibaca dari cookie biasa (bukan cookie terenkripsi Laravel), karena ditulis oleh JavaScript
    $ciut = ($_COOKIE['fc_side'] ?? '') === 'ciut';
    $namaPeran = ['mahasiswa' => 'Mahasiswa', 'orangtua' => 'Orang tua', 'admin' => 'Admin'][$user->role] ?? '';
@endphp
<body class="antialiased">
<div class="fc-body {{ $ciut ? 'is-ciut' : '' }}">
    <div class="fc-scrim" data-tutup-laci hidden aria-hidden="true"></div>

    {{-- ===== Sidebar (desktop) / laci (HP) ===== --}}
    <aside class="fc-side" aria-label="Menu utama">
        {{-- kepala sidebar: langit oranye + awan + maskot --}}
        <div class="fc-side-head">
            <a href="{{ $home }}" class="fc-brand" title="ForeCash">
                <span class="fc-brand-badge"><x-logo width="40" height="40" /></span>
                <span class="fc-nav-label min-w-0">
                    <span class="fc-brand-name">ForeCash</span>
                    <span class="fc-brand-tag">Uang aman sampai kiriman berikutnya</span>
                </span>
            </a>
            <button type="button" class="fc-side-toggle" data-ciut
                    aria-label="{{ $ciut ? 'Lebarkan menu' : 'Ciutkan menu' }}" title="{{ $ciut ? 'Lebarkan menu' : 'Ciutkan menu' }}">
                <x-ikon nama="chevron-left" :size="16" :tebal="2.75" />
            </button>
            <button type="button" class="fc-side-close" data-tutup-laci aria-label="Tutup menu">
                <x-ikon nama="x" :tebal="2.75" />
            </button>
            <svg class="fc-side-cloud" viewBox="0 0 240 28" preserveAspectRatio="none" aria-hidden="true">
                <path d="M0 28V14c10-8 22-9 32-2 8-10 24-12 34-3 9-9 25-9 33 1 10-8 25-7 33 3 9-7 23-7 31 2 10-9 26-9 35 0 8-6 18-7 26-1 7-4 13-4 16-2V28Z" />
            </svg>
            <span class="fc-spark fc-spark-1" aria-hidden="true"></span>
            <span class="fc-spark fc-spark-2" aria-hidden="true"></span>
        </div>

        <p class="fc-nav-label fc-nav-title">Mau ngapain hari ini?</p>
        <nav class="fc-nav">
            @foreach ($menus as [$rute, $label, $pendek, $ikon, $warna])
                @if (Route::has($rute))
                    <a href="{{ route($rute) }}" title="{{ $label }}"
                       class="fc-nav-item fc-ic-{{ $warna }} {{ $aktif($rute) ? 'is-active' : '' }}"
                       @if ($aktif($rute)) aria-current="page" @endif>
                        <span class="fc-nav-icon"><x-ikon :nama="$ikon" :tebal="2.4" /></span>
                        <span class="fc-nav-label fc-nav-text">{{ $label }}</span>
                        <span class="fc-nav-label fc-nav-go" aria-hidden="true"><x-ikon nama="chevron-right" :size="14" :tebal="3" /></span>
                    </a>
                @else
                    {{-- halaman belum dibuat di versi Laravel --}}
                    <span class="fc-nav-item fc-ic-{{ $warna }} is-soon" title="{{ $label }} (segera)">
                        <span class="fc-nav-icon"><x-ikon :nama="$ikon" :tebal="2.4" /></span>
                        <span class="fc-nav-label fc-nav-text">{{ $label }}</span>
                        <span class="fc-nav-label fc-soon">Segera</span>
                    </span>
                @endif
            @endforeach
        </nav>

        <div class="fc-side-foot">
            <a href="{{ route('akun') }}" class="fc-me {{ request()->routeIs('akun') ? 'is-active' : '' }}" title="Akun" @if (request()->routeIs('akun')) aria-current="page" @endif>
                <span class="fc-me-avatar"><x-avatar :id="$user->avatar" :size="40" /></span>
                <span class="fc-nav-label min-w-0">
                    <span class="fc-me-name">{{ $user->name }}</span>
                    <span class="fc-me-role">{{ $namaPeran }} · Akun</span>
                </span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="fc-logout" title="Keluar">
                    <x-ikon nama="log-out" :size="16" :tebal="2.2" /> <span class="fc-nav-label">Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="fc-main">
        @if ($user->isOrangtua())
            <div class="fc-parent-strip">
                <x-ikon nama="eye" :size="14" class="inline -mt-0.5" /> Mode pantau: data anak hanya bisa dilihat, tidak bisa diubah.
            </div>
        @endif

        <header class="fc-top">
            <a href="{{ $home }}" class="fc-top-logo" aria-label="ForeCash"><x-logo width="40" height="40" /></a>
            <div class="min-w-0">
                <p class="fc-top-date">{{ \App\Support\Format::tglPanjang(\App\Support\Format::hariIni()) }}</p>
                <h1 class="fc-top-title">@yield('judul_halaman', 'ForeCash')</h1>
            </div>
            <a href="{{ route('akun') }}" class="fc-top-avatar" aria-label="Akun"><x-avatar :id="$user->avatar" :size="40" /></a>
        </header>

        <main class="fc-content">
            @include('partials.flash')
            @yield('isi')
        </main>
    </div>

    {{-- ===== Tab bar bawah (HP): 4 menu utama + Menu ===== --}}
    <nav class="fc-tabbar" aria-label="Menu cepat">
        @foreach ($tabHp as [$rute, $label, $pendek, $ikon, $warna])
            @if (Route::has($rute))
                <a href="{{ route($rute) }}" class="fc-ic-{{ $warna }} {{ $aktif($rute) ? 'is-active' : '' }}" @if ($aktif($rute)) aria-current="page" @endif>
                    <span class="fc-tab-ic"><x-ikon :nama="$ikon" :size="19" :tebal="2.4" /></span>
                    <span>{{ $pendek }}</span>
                </a>
            @else
                <span class="fc-ic-{{ $warna }}" style="opacity:.5">
                    <span class="fc-tab-ic"><x-ikon :nama="$ikon" :size="19" :tebal="2.4" /></span>
                    <span>{{ $pendek }}</span>
                </span>
            @endif
        @endforeach
        <button type="button" data-buka-laci class="fc-ic-orange {{ $laciAktif ? 'is-active' : '' }}" aria-label="Semua menu" aria-expanded="false">
            <span class="fc-tab-ic"><x-ikon nama="grid" :size="19" :tebal="2.4" /></span>
            <span>Menu</span>
        </button>
    </nav>

    {{-- ForeCash Assistant (chatbot), khusus mahasiswa --}}
    @if ($user->isMahasiswa() && Route::has('chatbot'))
        @include('partials.chatbot')
    @endif
</div>
</body>
</html>
