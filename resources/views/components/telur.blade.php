{{-- Telur yang sedang dierami; retak muncul seiring progres (port dari EggSvg di PetSvg.tsx).
     Contoh: <x-telur :progress="4" :size="120" /> --}}
@props(['progress' => 0, 'size' => 120])
<svg viewBox="0 0 100 110" width="{{ $size }}" height="{{ round($size * 1.1) }}" aria-hidden="true">
    <ellipse cx="50" cy="104" rx="30" ry="5" fill="#000" opacity=".12" />
    <path d="M50 6 C74 6 88 44 88 66 C88 90 70 102 50 102 C30 102 12 90 12 66 C12 44 26 6 50 6Z" fill="#FFF3D6" stroke="#E9D29A" stroke-width="3" />
    <circle cx="34" cy="40" r="7" fill="#FFC83D" /><circle cx="64" cy="30" r="5" fill="#FF8A1F" />
    <circle cx="70" cy="72" r="8" fill="#8B6CF0" opacity=".7" /><circle cx="30" cy="80" r="5" fill="#2EBD74" opacity=".7" />
    <ellipse cx="36" cy="28" rx="8" ry="14" fill="#fff" opacity=".7" transform="rotate(25 36 28)" />
    @if ($progress >= 3)
        <path d="M38 58 L46 64 L42 70 L50 74" stroke="#8C6A3F" stroke-width="3" fill="none" stroke-linecap="round" />
    @endif
    @if ($progress >= 5)
        <path d="M62 46 L56 52 L62 58 L56 62" stroke="#8C6A3F" stroke-width="3" fill="none" stroke-linecap="round" />
    @endif
</svg>
