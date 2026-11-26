{{-- Ikon badge + warna tingkat perunggu/perak/emas (port dari components/BadgeIkon.tsx).
     Contoh: <x-badge-ikon ikon="hemat" :tingkat="2" :size="56" label="Hari Hemat Perak" /> --}}
@props(['ikon', 'tingkat' => 0, 'size' => 56, 'label' => null])
@php
    $IKON = [
        'pencatat' => 'pen-line', 'rajin' => 'flame', 'hemat' => 'piggy-bank', 'anggaran' => 'wallet', 'penabung' => 'coins',
        'rutin' => 'calendar-check', 'impian' => 'star', 'perencana' => 'clipboard', 'patungan' => 'pizza',
        'lingkaran' => 'users', 'bebas' => 'badge-check', 'penagih' => 'hand-coins', 'pemindai' => 'scan-line',
    ];
@endphp
<span class="fc-medal fc-medal-{{ $tingkat }}" style="width: {{ $size }}px; height: {{ $size }}px"
      @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif>
    <x-ikon :nama="$IKON[$ikon] ?? 'star'" :size="round($size * 0.46)" :tebal="2.4" />
</span>
