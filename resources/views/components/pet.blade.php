{{-- Gambar teman hemat (port dari PetSvg di components/PetSvg.tsx versi Next.js).
     Contoh: <x-pet id="celeng" :size="64" />  atau di dalam SVG lain: <x-pet id="mochi" :size="40" :x="22" :y="-64" /> --}}
@props(['id' => 'celeng', 'size' => 64, 'x' => null, 'y' => null])
@php
    $p = \App\Support\Pets::PETS[$id] ?? \App\Support\Pets::PETS['celeng'];
    ['warna' => $c, 'gelap' => $d, 'tipe' => $tipe] = $p;
    $INK = '#2D2216';
@endphp
<svg viewBox="0 0 100 100" width="{{ $size }}" height="{{ $size }}" @if ($x !== null) x="{{ $x }}" @endif @if ($y !== null) y="{{ $y }}" @endif
     role="img" aria-label="{{ $p['nama'] }}" {{ $attributes }}>
    <ellipse cx="50" cy="94" rx="30" ry="5" fill="#000" opacity=".12" />
    @switch($tipe)
        @case('pig')
            <path d="M26 30 L22 14 L38 24 Z M74 30 L78 14 L62 24 Z" fill="{{ $d }}" />
            @break
        @case('bunny')
            <ellipse cx="36" cy="16" rx="8" ry="20" fill="{{ $c }}" stroke="{{ $d }}" stroke-width="3" />
            <ellipse cx="64" cy="16" rx="8" ry="20" fill="{{ $c }}" stroke="{{ $d }}" stroke-width="3" />
            <ellipse cx="36" cy="18" rx="3.5" ry="12" fill="#FFC4D6" />
            <ellipse cx="64" cy="18" rx="3.5" ry="12" fill="#FFC4D6" />
            @break
        @case('bear')
            <circle cx="26" cy="30" r="11" fill="{{ $d }}" /><circle cx="74" cy="30" r="11" fill="{{ $d }}" />
            <circle cx="26" cy="30" r="5" fill="{{ $c }}" /><circle cx="74" cy="30" r="5" fill="{{ $c }}" />
            @break
        @case('leaf')
            <path d="M50 26 C50 18 52 12 56 8" stroke="#2E8B57" stroke-width="4" fill="none" stroke-linecap="round" />
            <path d="M55 12 C64 2 78 6 80 12 C70 20 60 18 55 12Z" fill="#4CC283" />
            @break
        @case('cat')
            <path d="M22 36 L24 12 L42 26 Z M78 36 L76 12 L58 26 Z" fill="{{ $c }}" stroke="{{ $d }}" stroke-width="3" stroke-linejoin="round" />
            @break
        @case('frog')
            <circle cx="32" cy="26" r="12" fill="{{ $c }}" stroke="{{ $d }}" stroke-width="3" />
            <circle cx="68" cy="26" r="12" fill="{{ $c }}" stroke="{{ $d }}" stroke-width="3" />
            @break
        @case('cloud')
            <circle cx="30" cy="38" r="14" fill="{{ $c }}" /><circle cx="50" cy="28" r="17" fill="{{ $c }}" /><circle cx="70" cy="38" r="14" fill="{{ $c }}" />
            @break
        @case('tuft')
            <path d="M44 24 C44 14 52 12 54 18 C56 10 64 12 60 22" stroke="{{ $d }}" stroke-width="4" fill="none" stroke-linecap="round" />
            @break
        @case('cow')
            <path d="M20 34 C10 28 10 22 18 22 C24 22 28 28 30 32 Z M80 34 C90 28 90 22 82 22 C76 22 72 28 70 32 Z" fill="{{ $d }}" />
            <path d="M34 24 C32 16 36 14 38 18 M66 24 C68 16 64 14 62 18" stroke="#C9A36A" stroke-width="4" fill="none" stroke-linecap="round" />
            @break
        @case('fox')
            <path d="M22 38 L20 10 L44 28 Z M78 38 L80 10 L56 28 Z" fill="{{ $c }}" stroke="{{ $d }}" stroke-width="3" stroke-linejoin="round" />
            <path d="M24 32 L23 18 L36 28 Z M76 32 L77 18 L64 28 Z" fill="#FFF1E5" />
            @break
    @endswitch
    <ellipse cx="50" cy="60" rx="38" ry="33" fill="{{ $c }}" />
    <ellipse cx="50" cy="60" rx="38" ry="33" fill="none" stroke="{{ $d }}" stroke-width="3" />
    <ellipse cx="38" cy="44" rx="12" ry="6" fill="#fff" opacity=".5" transform="rotate(-20 38 44)" />
    @if ($tipe === 'cow')
        <ellipse cx="32" cy="66" rx="9" ry="7" fill="#5A4636" opacity=".85" />
        <ellipse cx="72" cy="52" rx="7" ry="6" fill="#5A4636" opacity=".85" />
    @endif
    @if ($tipe === 'fox' || $tipe === 'bear')
        <ellipse cx="50" cy="76" rx="18" ry="12" fill="#FFF6E8" opacity=".8" />
    @endif
    <circle cx="37" cy="58" r="5.5" fill="{{ $INK }}" class="hs-eye" />
    <circle cx="63" cy="58" r="5.5" fill="{{ $INK }}" class="hs-eye" />
    <circle cx="35.5" cy="56" r="2" fill="#fff" />
    <circle cx="61.5" cy="56" r="2" fill="#fff" />
    <ellipse cx="27" cy="68" rx="6" ry="3.5" fill="#FF7FA0" opacity=".45" />
    <ellipse cx="73" cy="68" rx="6" ry="3.5" fill="#FF7FA0" opacity=".45" />
    @if ($tipe === 'pig')
        <ellipse cx="50" cy="70" rx="10" ry="7" fill="{{ $d }}" />
        <circle cx="46" cy="70" r="2" fill="{{ $INK }}" /><circle cx="54" cy="70" r="2" fill="{{ $INK }}" />
    @else
        <path d="M45 70 Q50 75 55 70" stroke="{{ $INK }}" stroke-width="3" fill="none" stroke-linecap="round" />
    @endif
</svg>
