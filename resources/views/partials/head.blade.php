{{-- Bagian <head> yang sama untuk semua halaman --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
{{-- isi @section('judul', '...') sudah di-escape oleh Blade, jadi di sini dicetak apa adanya supaya "&" tidak jadi "&amp;amp;" --}}
<title>{!! trim($__env->yieldContent('judul')) ? trim($__env->yieldContent('judul')) . ' — ' : '' !!}ForeCash</title>
<meta name="description" content="ForeCash: aplikasi web peringatan dini keuangan untuk mahasiswa.">
<meta name="theme-color" content="#FF8A1F">
@stack('meta')
{{-- font lokal: Clash Grotesk untuk judul & angka (Fontshare FFL), Spline Sans untuk teks (OFL). Lisensi ada di public/fonts --}}
<link rel="preload" href="{{ asset('fonts/ClashGrotesk-Variable.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('fonts/SplineSans-Variable.woff2') }}" as="font" type="font/woff2" crossorigin>
@vite(['resources/css/app.css', 'resources/js/app.js'])
