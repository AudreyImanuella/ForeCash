{{-- Bagian <head> yang sama untuk semua halaman --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
{{-- isi @section('judul', '...') sudah di-escape oleh Blade, jadi di sini dicetak apa adanya supaya "&" tidak jadi "&amp;amp;" --}}
<title>{!! trim($__env->yieldContent('judul')) ? trim($__env->yieldContent('judul')) . ' — ' : '' !!}ForeCash</title>
<meta name="description" content="ForeCash: aplikasi web peringatan dini keuangan untuk mahasiswa.">
<meta name="theme-color" content="#FF8A1F">
@stack('meta')
{{-- font Satoshi (Fontshare, ITF Free Font License), sama dengan versi Next.js & PHP --}}
<link rel="preconnect" href="https://api.fontshare.com">
<link href="https://api.fontshare.com/v2/css?f[]=satoshi@300,400,500,700,900&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/app.js'])
