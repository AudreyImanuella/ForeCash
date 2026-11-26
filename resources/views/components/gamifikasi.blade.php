{{-- Potongan tampilan gamifikasi (port dari components/Gamifikasi.tsx): bilah level, tantangan, kartu badge.
     Contoh: <x-gamifikasi bagian="level" :g="$g" />, <x-gamifikasi bagian="tantangan" :t="$g['tantangan']" />,
             <x-gamifikasi bagian="badge" :b="$badge" /> --}}
@props(['bagian', 'g' => null, 't' => null, 'b' => null])
@use('App\Support\Format')
@use('App\Support\Gamify')

@if ($bagian === 'level')
    @php
        $pct = $g['nextPoin'] ? (int) round(($g['poin'] - $g['prevPoin']) / ($g['nextPoin'] - $g['prevPoin']) * 100) : 100;
        $ket = $g['nextPoin'] ? ($g['nextPoin'] - $g['poin']) . ' poin lagi menuju ' . $g['nextNama'] : 'Level tertinggi tercapai!';
    @endphp
    <div class="flex justify-between text-xs font-bold">
        <span>Level {{ $g['level'] }} · {{ $g['levelNama'] }}</span>
        <span>{{ $g['poin'] }} poin</span>
    </div>
    <div class="w-full bg-slate-100 rounded-full h-3 mt-1" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Kemajuan level">
        <div class="bg-hsPurple h-3 rounded-full" style="width: {{ $pct }}%"></div>
    </div>
    <p class="text-[10px] text-slate-500 mt-1">{{ $ket }}</p>

@elseif ($bagian === 'tantangan')
    @php
        $pct = (int) min(100, round($t['now'] / $t['max'] * 100));
        $angka = fn ($n) => $t['satuan'] === 'rp' ? Format::rupiah($n) : $n . ' hari';
        if ($t['jenis'] === 'maks') {
            $ket = $t['gagal'] ? 'Lewat batas, coba lagi minggu depan' : 'sisa ' . Format::rupiah($t['max'] - $t['now']) . ', ' . $t['sisaHari'] . ' hari lagi';
        } else {
            $ket = $t['selesai'] ? 'Selesai! Mantap.' : ($t['gagal'] ? 'Tidak terkejar minggu ini, coba lagi minggu depan' : ($t['max'] - $t['now']) . ' hari lagi, sisa ' . $t['sisaHari'] . ' hari');
        }
        $warna = $t['selesai'] ? 'bg-hsGreen' : ($t['gagal'] ? 'bg-hsRed' : ($t['jenis'] === 'maks' ? 'bg-hsGreen' : 'bg-hsOrange'));
    @endphp
    <p class="text-sm font-bold">{{ $t['judul'] }}</p>
    <div class="w-full bg-slate-100 rounded-full h-3 mt-2" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Kemajuan tantangan">
        <div class="{{ $warna }} h-3 rounded-full" style="width: {{ $pct }}%"></div>
    </div>
    <p class="text-[11px] mt-1 {{ $t['gagal'] ? 'text-hsRed font-bold' : ($t['selesai'] ? 'text-hsGreen font-bold' : 'text-slate-500') }}">
        {{ $angka($t['now']) }}{{ $t['jenis'] === 'maks' ? ' terpakai' : ' dari ' . $angka($t['max']) }} · {{ $ket }}
    </p>

@elseif ($bagian === 'badge')
    <div class="hs-card p-4 text-center space-y-2 {{ $b['tingkat'] === 0 ? 'opacity-75' : '' }}">
        <div class="flex justify-center"><x-badge-ikon :ikon="$b['key']" :tingkat="$b['tingkat']" :size="60" /></div>
        <p class="text-sm font-black">{{ $b['nama'] }}</p>
        <span class="fc-tier fc-tier-{{ $b['tingkat'] }}">{{ Gamify::TINGKAT[$b['tingkat']] }}</span>
        <p class="text-[11px] text-slate-500 min-h-[30px]">{{ $b['ket'] }}</p>
        @if ($b['berikut'] !== null)
            <div class="w-full bg-slate-100 rounded-full h-2" role="progressbar" aria-valuenow="{{ $b['persen'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Kemajuan {{ $b['nama'] }}">
                <div class="bg-hsOrange h-2 rounded-full" style="width: {{ $b['persen'] }}%"></div>
            </div>
            <p class="text-[10px] font-bold">{{ Gamify::sisaBadge($b) }} menuju {{ Gamify::TINGKAT[$b['tingkat'] + 1] }}</p>
        @else
            <p class="text-[10px] font-bold text-amber-700">Tingkat tertinggi!</p>
        @endif
    </div>
@endif
