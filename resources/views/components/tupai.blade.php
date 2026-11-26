{{-- Maskot tupai ForeCash dalam berbagai pose (gambar di public/images/maskot/<pose>.webp).
     Pose: senang, cemas, nangis, marah, melambai, koin, bangga, meditasi, diam.
     Contoh: <x-tupai pose="senang" class="w-24 h-24" /> --}}
@props(['pose' => 'senang', 'alt' => '', 'loading' => 'lazy'])
<img src="{{ asset('images/maskot/' . $pose . '.webp') }}" alt="{{ $alt }}" width="320" height="320"
     loading="{{ $loading }}" decoding="async" draggable="false" {{ $attributes->merge(['class' => 'object-contain select-none']) }}>
