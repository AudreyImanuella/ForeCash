{{-- Kerangka halaman login / registrasi (port dari components/AuthLayout.tsx versi Next.js). --}}
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body class="antialiased">
<div id="loginPage" class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full space-y-6 text-center">
        {{-- tupai melambai (WebP animasi hasil render 3D); pengguna yang mematikan animasi di sistemnya mendapat gambar diam --}}
        <picture>
            <source srcset="{{ asset('images/maskot/melambai.webp') }}" media="(prefers-reduced-motion: reduce)">
            <x-tupai pose="melambai-animasi" alt="Tupai ForeCash melambai" class="w-40 h-40 mx-auto" loading="eager" />
        </picture>
        <div class="space-y-2">
            <h1 class="text-4xl font-black text-hsDark">@yield('judul_auth', 'ForeCash')</h1>
            <p class="text-sm font-medium text-slate-600 px-4">@yield('sub_auth')</p>
        </div>
        @yield('isi')
    </div>
</div>
</body>
</html>
