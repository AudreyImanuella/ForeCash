{{-- Peta Petualangan sebagai satu SVG (port dari components/PetaPerjalanan.tsx versi Next.js).
     Jalan berliku dari pemasukan terakhir sampai pemasukan berikutnya, satu titik = satu hari.
     Contoh: <x-peta :hari="$j['hari']" :cols="7" :lebar="1000" :tinggi-baris="150" tema="aman" avatar="a1" :pets="['celeng']" dipakai="celeng" /> --}}
@props(['hari', 'cols', 'lebar', 'tinggiBaris', 'tema', 'avatar', 'pets' => [], 'dipakai' => null])
@use('App\Support\Format')
@php
    $W = $lebar;
    $rowH = $tinggiBaris;
    $BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $TEMA = [
        'aman' => ['ground' => '#CFEFC9', 'road' => '#92D6A6', 'line' => '#FFFFFF'],
        'waspada' => ['ground' => '#FFE6A8', 'road' => '#F2C46D', 'line' => '#FFF6DA'],
        'kritis' => ['ground' => '#E3ECF8', 'road' => '#FFFFFF', 'line' => '#B9C8E0'],
    ];
    $KET = [
        'baik' => 'hari baik', 'boros' => 'melewati batas harian', 'kosong' => 'tidak mencatat', 'hariini' => 'hari ini',
        'cukup' => 'saldo diperkirakan cukup', 'kurang' => 'saldo diperkirakan habis', 'gajian' => 'pemasukan datang',
    ];
    // [isi, garis, garis putus-putus, tanda, warna tanda]
    $GAYA = [
        'baik' => ['KOIN', '#E8860E', '', '✓', '#7A3E00'],
        'boros' => ['#FF8F8F', '#E25555', '', '!', '#fff'],
        'kosong' => ['#F2EDE4', '#CFC6B5', '6 5', '', ''],
        'cukup' => ['#FFFFFF', '#F5C04A', '', '', ''],
        'kurang' => ['#FFE5E5', '#FF5B5B', '6 5', '', ''],
    ];
    $MOOD = ['aman' => 'happy', 'waspada' => 'neutral', 'kritis' => 'worried'];

    $n = count($hari);
    $mx = $W * 0.1;
    $top = 90;
    $rows = (int) ceil($n / $cols);
    $H = $top + ($rows - 1) * $rowH + 110;
    $step = ($W - 2 * $mx) / max(1, $cols - 1);

    // posisi titik: baris ganjil berjalan dari kanan ke kiri (jalan berliku)
    $pos = [];
    foreach ($hari as $i => $_) {
        $r = intdiv($i, $cols);
        $c = $i % $cols;
        if ($r % 2) {
            $c = $cols - 1 - $c;
        }
        $pos[] = [$mx + $c * $step, $top + $r * $rowH];
    }

    // jalan: garis lurus per baris, belokan setengah lingkaran di ujung
    $d = 'M' . round($pos[0][0]) . ' ' . $pos[0][1];
    for ($i = 1; $i < $n; $i++) {
        [$x, $y] = $pos[$i];
        [$px, $py] = $pos[$i - 1];
        if ($y !== $py) {
            $sweep = intdiv($i - 1, $cols) % 2 ? 0 : 1;
            $d .= ' A' . ($rowH / 2) . ' ' . ($rowH / 2) . " 0 0 {$sweep} " . round($px) . ' ' . $y;
            if (round($px) !== round($x)) {
                $d .= ' L' . round($x) . ' ' . $y;
            }
        } else {
            $d .= ' L' . round($x) . ' ' . $y;
        }
    }
    $t = $TEMA[$tema];
    // id gradien harus unik: di halaman ada dua peta (versi lebar & versi HP)
    $idKoin = 'fcCoin' . $cols;
    $lainnya = array_values(array_filter($pets, fn ($p) => $p !== $dipakai));
    $gunung = ['aman' => ['#8FC9A0', '#6FB487'], 'waspada' => ['#E8B470', '#D99A4E'], 'kritis' => ['#B9C8E0', '#9FB2D3']][$tema];
@endphp
<svg class="fc-map-svg" viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="Peta perjalanan menuju pemasukan berikutnya">
    <defs>
        <radialGradient id="{{ $idKoin }}" cx=".35" cy=".3" r=".8">
            <stop offset="0" stop-color="#FFE7A0" /><stop offset=".55" stop-color="#FFB23E" /><stop offset="1" stop-color="#E8860E" />
        </radialGradient>
    </defs>
    <rect width="{{ $W }}" height="{{ $H }}" fill="{{ $t['ground'] }}" />

    {{-- dekorasi: gunung, pohon, danau / daun / salju sesuai tema --}}
    <path d="M{{ $W * 0.55 }} 60 L{{ $W * 0.66 }} 8 L{{ $W * 0.76 }} 60 Z" fill="{{ $gunung[0] }}" />
    <path d="M{{ $W * 0.68 }} 60 L{{ $W * 0.8 }} 0 L{{ $W * 0.92 }} 60 Z" fill="{{ $gunung[1] }}" />
    @if ($tema === 'kritis')
        <path d="M{{ $W * 0.77 }} 15 L{{ $W * 0.8 }} 0 L{{ $W * 0.83 }} 15 Z" fill="#fff" />
    @endif
    @for ($r = 0; $r < $rows; $r++)
        @php $yy = $top + $r * $rowH + $rowH / 2; @endphp
        @break($yy > $H - 40)
        @foreach ([0.18, 0.47, 0.8] as $k => $fx)
            @continue(($r + $k) % 2)
            <g transform="translate({{ $W * $fx + ($r % 2 ? 24 : -18) }} {{ $yy + 8 }}) scale({{ $W > 600 ? 1 : 0.8 }})">
                @if ($tema === 'aman')
                    <rect x="-4" y="0" width="8" height="22" rx="3" fill="#A0714F" /><circle cy="-8" r="20" fill="#4CC283" /><circle cx="-8" cy="-14" r="8" fill="#7FDBA0" />
                @elseif ($tema === 'waspada')
                    <rect x="-4" y="0" width="8" height="22" rx="3" fill="#8C5A35" /><circle cy="-8" r="20" fill="#F2913A" /><circle cx="10" cy="-16" r="10" fill="#E8642B" /><circle cx="-8" cy="-14" r="7" fill="#FFC266" />
                @else
                    <rect x="-4" y="6" width="8" height="16" rx="3" fill="#8C6A4F" /><path d="M0 -34 L22 10 L-22 10 Z" fill="#3E9E7E" /><path d="M0 -34 L10 -14 L-10 -14 Z" fill="#fff" /><path d="M-22 10 L22 10 L18 4 L-18 4Z" fill="#fff" opacity=".8" />
                @endif
            </g>
        @endforeach
    @endfor
    @if ($tema === 'aman')
        <ellipse cx="{{ $W * 0.12 }}" cy="{{ $H - 34 }}" rx="{{ $W * 0.09 }}" ry="20" fill="#7CC8F2" />
    @elseif ($tema === 'waspada')
        
    @else
        @for ($i = 0; $i < 18; $i++)
            <circle cx="{{ ($i * 137) % $W }}" cy="{{ ($i * 89) % $H }}" r="3" fill="#fff" class="fc-snow" style="animation-delay: {{ $i % 6 }}s" />
        @endfor
        
    @endif

    <path d="{{ $d }}" stroke="{{ $t['road'] }}" stroke-width="{{ $cols > 5 ? 40 : 34 }}" fill="none" stroke-linecap="round" stroke-linejoin="round" />
    <path d="{{ $d }}" stroke="{{ $t['line'] }}" stroke-width="4" fill="none" stroke-dasharray="14 14" stroke-linecap="round" />

    @foreach ($hari as $i => $h)
        @php
            $x = round($pos[$i][0]);
            $y = $pos[$i][1];
            [, $m, $dd] = array_map('intval', explode('-', $h['tanggal']));
            $label = $dd . ($i === 0 || $dd === 1 ? ' ' . $BULAN[$m - 1] : '');
            $info = Format::tgl($h['tanggal']) . ' — ' . $KET[$h['status']] . ($h['keluar'] ? ', keluar ' . Format::rupiah($h['keluar']) : '');
        @endphp
        <g class="fc-node fc-node-{{ $h['status'] }}" transform="translate({{ $x }} {{ $y }})">
            <title>{{ $info }}</title>
            @if ($h['status'] === 'gajian')
                <circle r="30" fill="#2EBD74" stroke="#fff" stroke-width="5" />
                <path d="M-7 13V-13M-7-13H10L5-6l5 7H-7" stroke="#fff" stroke-width="3" fill="#fff" stroke-linejoin="round" />
                <rect x="-46" y="38" width="92" height="24" rx="12" fill="#fff" />
                <text y="55" text-anchor="middle" class="fc-node-label" fill="#1F9A5C">Uang saku</text>
            @elseif ($h['status'] === 'hariini')
                <circle r="28" fill="#fff" stroke="#FF8A1F" stroke-width="5" />
                <circle r="36" fill="none" stroke="#FF8A1F" stroke-width="3" opacity=".35" class="fc-pulse" />
                <g class="fc-map-me" transform="translate(-34 -92)"><x-avatar :id="$avatar" :mood="$MOOD[$tema]" :size="68" /></g>
                @if ($dipakai)
                    <g class="fc-map-buddy"><x-pet :id="$dipakai" :size="40" :x="22" :y="-64" /></g>
                @endif
                <text y="7" text-anchor="middle" class="fc-node-num" fill="#E06A00">{{ $dd }}</text>
                <rect x="-36" y="34" width="72" height="22" rx="11" fill="#FF8A1F" />
                <text y="50" text-anchor="middle" class="fc-node-label" fill="#fff">Hari ini</text>
            @else
                @php $g = $GAYA[$h['status']]; @endphp
                <circle r="20" fill="{{ $g[0] === 'KOIN' ? 'url(#' . $idKoin . ')' : $g[0] }}" stroke="{{ $g[1] }}" stroke-width="4" @if ($g[2]) stroke-dasharray="{{ $g[2] }}" @endif />
                @if ($g[3])
                    <text y="7" text-anchor="middle" class="fc-node-mark" fill="{{ $g[4] }}">{{ $g[3] }}</text>
                @endif
                <text y="40" text-anchor="middle" class="fc-node-label" fill="#5B4A36">{{ $label }}</text>
            @endif
        </g>
    @endforeach

    {{-- teman hemat lain jalan-jalan di sekitar jalan --}}
    @foreach ($lainnya as $k => $pid)
        @php
            $i = min($n - 1, (int) round((($k * 2 + 1) * $n) / (2 * max(1, count($pets)))));
            [$x, $y] = $pos[$i];
            $off = $y + $rowH / 2 > $top + ($rows - 1) * $rowH ? -$rowH / 2 : $rowH / 2;
        @endphp
        <g class="fc-map-pet" style="animation-delay: {{ ($k + 1) * 0.4 }}s">
            <x-pet :id="$pid" :size="46" :x="round($x - 23 + (($k + 1) % 2 ? 34 : -34))" :y="round($y + $off - 26)" />
        </g>
    @endforeach
</svg>
