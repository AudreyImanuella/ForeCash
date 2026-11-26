{{-- Logo ForeCash: kepala Chip (maskot), tanpa lingkaran — mengganti logo koin lama (Nov 2026).
     Gambar: public/images/maskot/kepala.webp (dipotong dari pose "diam"). Logo koin lama ada di backup.
     Contoh: <x-logo width="40" height="40" />  (lebar dipakai, tinggi mengikuti proporsi gambar) --}}
@props(['width' => 40, 'height' => 40])
<img src="{{ asset('images/maskot/kepala.webp') }}" alt="Logo ForeCash" width="242" height="175"
     {{ $attributes->except(['width', 'height'])->merge(['class' => 'fc-logo-chip']) }}
     style="width: {{ round($width * 1.15) }}px; height: auto" loading="eager">
