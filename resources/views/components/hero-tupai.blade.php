{{-- Kartu utama Beranda: tupai berdiri di bukit, ekspresinya ikut kondisi keuangan.
     Mood: senang (aman), bangga (tantangan selesai), cemas (waspada), marah (lewat batas jajan), nangis (kritis).
     Contoh: <x-hero-tupai :f="$f" :mood="$mood" :jajan="$jajanHariIni" :batas="$batasHarian" /> --}}
@props(['f', 'mood' => 'senang', 'jajan' => 0, 'batas' => 0])
@use('App\Support\Format')
@php
    $ke = fn (string $rute, array $p = []) => Route::has($rute) ? route($rute, $p) : '#';

    $TAG = [
        'senang' => 'Status: aman', 'bangga' => 'Tantangan selesai', 'cemas' => 'Status: waspada',
        'marah' => 'Batas jajan terlewati', 'nangis' => 'Status: kritis',
    ];
    $KATA = [
        'senang' => 'Dompet kita sehat, lanjutkan!', 'bangga' => 'Keren banget! Aku bangga.',
        'cemas' => 'Eh, kok cepat menipis ya?', 'marah' => 'Hei! Kita sudah sepakat batasnya!',
        'nangis' => 'Huhu, uang kita hampir habis...',
    ];

    // bar "daya tahan dompet" (10 kotak): hari yang masih tertutup saldo
    // dibanding hari menuju pemasukan berikutnya
    if ($f['sisaHari']) {
        $hariAman = $f['runway'] === null ? $f['sisaHari'] : min($f['runway'], $f['sisaHari']);
        $isi = (int) round($hariAman / $f['sisaHari'] * 10);
        $ketHp = $hariAman . ' / ' . $f['sisaHari'] . ' hari';
    } else {
        $isi = $f['runway'] === null ? 10 : min(10, (int) round($f['runway'] / 3));
        $ketHp = $f['runway'] === null ? 'belum ada data' : $f['runway'] . ' hari';
    }

    // tombol utama ikut mood
    [$ctaTeks, $ctaUrl] = match ($mood) {
        'marah' => ['Atur batas jajan', $ke('transaksi.index') . '#batas'],
        'bangga' => ['Lihat pencapaian', $ke('pencapaian')],
        default => ['Catat transaksi', $ke('transaksi.index', ['jenis' => 'pengeluaran'])],
    };

    // pemandangan pohon uang: subur saat aman, layu saat kritis
    $SCENE = [
        'senang' => ['glow' => '#FFFBEA', 'sun' => '#FFF2B3', 'leaf' => ['#B5EBA0', '#5DBB72', '#2E7A47'], 'skala' => 1, 'gumpal' => 6, 'koin' => 6,
                     'daun' => [[300, 170, 1, 6]], 'kilau' => [], 'jatuh' => []],
        'bangga' => ['glow' => '#FFFFFF', 'sun' => '#FFE58A', 'leaf' => ['#C2F2A8', '#62C277', '#2E7A47'], 'skala' => 1.06, 'gumpal' => 6, 'koin' => 8,
                     'daun' => [], 'kilau' => [[268, 120, 8, 0], [392, 132, 7, .7], [340, 74, 9, 1.3], [282, 210, 6, .4]], 'jatuh' => []],
        'cemas'  => ['glow' => '#FFF4D6', 'sun' => '#FFB85C', 'leaf' => ['#F3E39A', '#C9B24E', '#7E6E22'], 'skala' => .86, 'gumpal' => 6, 'koin' => 3,
                     'daun' => [[290, 170, 0, 4.5], [360, 160, 1.5, 5], [330, 140, 3, 4.8]], 'kilau' => [], 'jatuh' => []],
        'marah'  => ['glow' => '#FFE2CF', 'sun' => '#FF8F5E', 'leaf' => ['#FFC08F', '#E0683C', '#9A3317'], 'skala' => .8, 'gumpal' => 5, 'koin' => 2,
                     'daun' => [[285, 160, 0, 3.2], [350, 150, .6, 3.4], [320, 130, 1.2, 3], [372, 170, 1.8, 3.6], [300, 180, 2.4, 3.1]], 'kilau' => [], 'jatuh' => [262, 396]],
        'nangis' => ['glow' => '#FCE3E1', 'sun' => null, 'leaf' => ['#D8BC8A', '#A3804E', '#5E4423'], 'skala' => 1, 'gumpal' => 0, 'koin' => 0,
                     'daun' => [[300, 236, .5, 5.5]], 'kilau' => [], 'jatuh' => [250, 404, 276]],
    ];
    $sc = $SCENE[$mood] ?? $SCENE['senang'];
    $id = 'fb-' . $mood; // awalan id gradasi, supaya tidak bentrok
    $GUMPAL = [[330, 150, 46], [292, 172, 34], [370, 170, 36], [305, 128, 30], [356, 124, 32], [330, 108, 28]];
    $tajuk = $mood === 'nangis'
        ? [[292, 240, 8], [368, 236, 7], [312, 172, 6]]                      // tinggal beberapa daun kering
        : array_map(fn ($g) => [$g[0], $g[1], round($g[2] * $sc['skala'])], array_slice($GUMPAL, 0, $sc['gumpal']));
    $KOIN = [[300, 160], [352, 150], [330, 126], [372, 184], [288, 188], [322, 178], [346, 104], [306, 112]];
    $koin = array_slice($KOIN, 0, $sc['koin']);
    $dahan = $mood === 'nangis'
        ? 'M330 222 C314 214 302 220 290 236 M330 216 C348 210 360 216 370 232 M330 206 C326 186 320 176 312 170'   // terkulai
        : 'M330 222 C316 206 304 196 292 184 M330 216 C346 202 358 192 370 180 M330 206 L330 150';
    $bintang = fn ($x, $y, $s) => "M$x " . ($y - $s) . ' L' . ($x + $s * .3) . ' ' . ($y - $s * .3) . ' L' . ($x + $s) . " $y L" . ($x + $s * .3) . ' ' . ($y + $s * .3)
        . " L$x " . ($y + $s) . ' L' . ($x - $s * .3) . ' ' . ($y + $s * .3) . ' L' . ($x - $s) . " $y L" . ($x - $s * .3) . ' ' . ($y - $s * .3) . ' Z';
@endphp
<section class="fb-hero" data-mood="{{ $mood }}" aria-label="Peringatan dini">
    {{-- Langit: awan lembut melayang --}}
    <svg class="fb-sky" viewBox="0 0 1000 330" preserveAspectRatio="xMidYMin slice" aria-hidden="true">
        <defs>
            <radialGradient id="{{ $id }}-awanlangit" cx="40%" cy="25%" r="85%"><stop offset="0" stop-color="#FFFFFF" /><stop offset="1" stop-color="#FFFFFF" stop-opacity=".55" /></radialGradient>
            <filter id="{{ $id }}-kabut" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="5" /></filter>
        </defs>
        @foreach ([[120, 60, 1, 'fb-awan-a'], [470, 34, .8, 'fb-awan-b'], [700, 120, .65, 'fb-awan-a']] as [$ax, $ay, $as, $ak])
            <g class="{{ $ak }}" transform="translate({{ $ax }} {{ $ay }}) scale({{ $as }})">
                <g transform="translate(3 8)" fill="#7C8AA5" opacity=".14" filter="url(#{{ $id }}-kabut)"><ellipse cx="60" cy="22" rx="62" ry="18" /></g>
                <g fill="url(#{{ $id }}-awanlangit)"><ellipse cx="60" cy="22" rx="62" ry="18" /><ellipse cx="34" cy="12" rx="28" ry="18" /><ellipse cx="74" cy="6" rx="34" ry="22" /></g>
            </g>
        @endforeach
    </svg>

    {{-- Daratan: bukit jauh & bukit tengah, bergradasi --}}
    <svg class="fb-land" viewBox="0 0 1000 160" preserveAspectRatio="none" aria-hidden="true">
        <defs>
            <linearGradient id="{{ $id }}-jauh" x1="0" x2="0" y1="0" y2="1"><stop offset="0" style="stop-color: var(--far-a)" /><stop offset="1" style="stop-color: var(--far-b)" /></linearGradient>
            <linearGradient id="{{ $id }}-tengah" x1="0" x2="0" y1="0" y2="1"><stop offset="0" style="stop-color: var(--mid-a)" /><stop offset="1" style="stop-color: var(--mid-b)" /></linearGradient>
        </defs>
        <path d="M0 70 C120 30 230 40 340 62 C450 84 560 28 690 40 C800 50 900 30 1000 46 L1000 160 L0 160 Z" fill="url(#{{ $id }}-jauh)" />
        <path d="M0 110 C140 78 260 84 380 100 C520 118 620 76 760 82 C860 86 940 100 1000 94 L1000 160 L0 160 Z" fill="url(#{{ $id }}-tengah)" />
    </svg>

    {{-- Pohon uang & matahari (ikut mood) --}}
    <svg class="fb-scene" viewBox="0 0 560 330" preserveAspectRatio="xMaxYMax meet" aria-hidden="true">
        <defs>
            <radialGradient id="{{ $id }}-langit" cx="80%" cy="15%" r="70%"><stop offset="0" stop-color="{{ $sc['glow'] }}" stop-opacity=".9" /><stop offset="1" stop-color="{{ $sc['glow'] }}" stop-opacity="0" /></radialGradient>
            <radialGradient id="{{ $id }}-daun" cx="34%" cy="28%" r="78%"><stop offset="0" stop-color="{{ $sc['leaf'][0] }}" /><stop offset=".55" stop-color="{{ $sc['leaf'][1] }}" /><stop offset="1" stop-color="{{ $sc['leaf'][2] }}" /></radialGradient>
            <linearGradient id="{{ $id }}-batang" x1="0" x2="1"><stop offset="0" stop-color="#5E3A22" /><stop offset=".38" stop-color="#A9744C" /><stop offset=".62" stop-color="#8A5A3B" /><stop offset="1" stop-color="#4E3019" /></linearGradient>
            <radialGradient id="{{ $id }}-koin" cx="35%" cy="30%" r="75%"><stop offset="0" stop-color="#FFF3B0" /><stop offset=".45" stop-color="#F7C948" /><stop offset="1" stop-color="#C98410" /></radialGradient>
            <radialGradient id="{{ $id }}-awan" cx="40%" cy="30%" r="80%"><stop offset="0" stop-color="#FFFFFF" /><stop offset="1" stop-color="#D9DCE6" /></radialGradient>
            <linearGradient id="{{ $id }}-bukit" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#FFFFFF" stop-opacity=".38" /><stop offset="1" stop-color="#FFFFFF" stop-opacity=".08" /></linearGradient>
            @if ($sc['sun'])
                <radialGradient id="{{ $id }}-mentari" cx="38%" cy="35%" r="65%"><stop offset="0" stop-color="#FFFFFF" /><stop offset=".45" stop-color="{{ $sc['sun'] }}" /><stop offset="1" stop-color="{{ $sc['sun'] }}" stop-opacity=".6" /></radialGradient>
            @endif
            <filter id="{{ $id }}-lembut" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="6" /></filter>
            <filter id="{{ $id }}-kilap" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="2.5" /></filter>
        </defs>

        @if ($sc['sun'])
            <circle cx="478" cy="74" r="54" fill="{{ $sc['sun'] }}" opacity=".25" filter="url(#{{ $id }}-lembut)" />
            <circle cx="478" cy="74" r="34" fill="url(#{{ $id }}-mentari)" />
        @endif

        <g transform="translate(-110 0)">
            @if ($mood === 'nangis')
                {{-- awan mendung + hujan --}}
                <g transform="translate(4 8)" fill="#6B6F86" opacity=".25" filter="url(#{{ $id }}-lembut)"><ellipse cx="330" cy="58" rx="58" ry="20" /><ellipse cx="352" cy="42" rx="34" ry="26" /></g>
                <g fill="url(#{{ $id }}-awan)"><ellipse cx="330" cy="58" rx="58" ry="20" /><ellipse cx="300" cy="48" rx="30" ry="22" /><ellipse cx="352" cy="42" rx="34" ry="26" /></g>
                <ellipse cx="340" cy="34" rx="16" ry="7" fill="#FFFFFF" opacity=".9" filter="url(#{{ $id }}-kilap)" />
                <g stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" opacity=".85">
                    @foreach ([[300, 82, 0], [326, 84, .4], [352, 80, .8], [372, 78, .2]] as [$hx, $hy, $hd])
                        <line class="fb-hujan" x1="{{ $hx }}" y1="{{ $hy }}" x2="{{ $hx - 4 }}" y2="{{ $hy + 12 }}" style="animation-delay: {{ $hd }}s" />
                    @endforeach
                </g>
            @endif

            {{-- bayangan, batang, dahan --}}
            <ellipse cx="334" cy="298" rx="62" ry="11" fill="#262A3B" opacity=".22" filter="url(#{{ $id }}-lembut)" />
            <path d="M318 296 C322 258 314 232 328 204 C342 232 336 258 344 296 Z" fill="url(#{{ $id }}-batang)" />
            <path d="M324 290 C326 262 322 240 329 216" fill="none" stroke="#C99A6E" stroke-width="2" stroke-linecap="round" opacity=".5" />
            <path d="{{ $dahan }}" fill="none" stroke="#7A4E30" stroke-width="5.5" stroke-linecap="round" />
            <path d="{{ $dahan }}" fill="none" stroke="#B88A5E" stroke-width="2" stroke-linecap="round" opacity=".55" transform="translate(-1 -1)" />

            {{-- tajuk: bayangan dalam, gumpalan daun bergradasi, kilap, koin --}}
            <g class="fb-tajuk">
                <g transform="translate(5 7)" opacity=".35" filter="url(#{{ $id }}-lembut)">
                    @foreach ($tajuk as [$x, $y, $r])<circle cx="{{ $x }}" cy="{{ $y }}" r="{{ $r }}" fill="{{ $sc['leaf'][2] }}" />@endforeach
                </g>
                @foreach ($tajuk as [$x, $y, $r])<circle cx="{{ $x }}" cy="{{ $y }}" r="{{ $r }}" fill="url(#{{ $id }}-daun)" />@endforeach
                @foreach ($tajuk as [$x, $y, $r])
                    <ellipse cx="{{ $x - $r * .35 }}" cy="{{ $y - $r * .42 }}" rx="{{ $r * .42 }}" ry="{{ $r * .26 }}" fill="#FFFFFF" opacity=".32" filter="url(#{{ $id }}-kilap)" />
                @endforeach
                @foreach ($koin as [$x, $y])
                    <ellipse cx="{{ $x + 2 }}" cy="{{ $y + 4 }}" rx="9" ry="8" fill="#262A3B" opacity=".22" filter="url(#{{ $id }}-kilap)" />
                    <circle cx="{{ $x }}" cy="{{ $y }}" r="9.5" fill="url(#{{ $id }}-koin)" />
                    <circle cx="{{ $x }}" cy="{{ $y }}" r="5" fill="none" stroke="#B87A10" stroke-width=".8" opacity=".55" />
                    <ellipse cx="{{ $x - 3.2 }}" cy="{{ $y - 3.6 }}" rx="3.2" ry="2" fill="#FFFFFF" opacity=".85" />
                @endforeach
            </g>

            @foreach ($sc['kilau'] as [$x, $y, $s, $d])
                <path class="fb-kilau" d="{{ $bintang($x, $y, $s) }}" fill="#FFFFFF" style="animation-delay: {{ $d }}s" />
            @endforeach
            @foreach ($sc['daun'] as [$x, $y, $d, $dur])
                <ellipse class="fb-daun" cx="{{ $x }}" cy="{{ $y }}" rx="6" ry="3.5" fill="url(#{{ $id }}-daun)" style="animation-delay: {{ $d }}s; animation-duration: {{ $dur }}s" />
            @endforeach
            @foreach ($sc['jatuh'] as $x)
                <ellipse cx="{{ $x }}" cy="301" rx="10" ry="3.5" fill="#262A3B" opacity=".22" filter="url(#{{ $id }}-kilap)" />
                <circle cx="{{ $x }}" cy="293" r="8" fill="url(#{{ $id }}-koin)" />
                <ellipse cx="{{ $x - 2.6 }}" cy="290" rx="2.6" ry="1.6" fill="#FFFFFF" opacity=".85" />
            @endforeach
        </g>
    </svg>
    <div class="fb-hero-text">
        <span class="fb-tag">{{ $TAG[$mood] }}</span>

        <h2>
            @if ($mood === 'marah')
                Jajan hari ini lewat {{ Format::rupiah($jajan - $batas) }}
            @elseif ($mood === 'bangga')
                Tantangan minggu ini beres!
            @elseif ($f['burn'] <= 0)
                Belum ada pengeluaran yang tercatat
            @elseif ($f['saldo'] <= 0)
                Saldo sudah habis
            @else
                Uangmu cukup sampai {{ Format::tgl($f['habis']) }}
            @endif
        </h2>

        <p>
            @if ($mood === 'marah')
                Batas jajanmu {{ Format::rupiah($batas) }}, hari ini terpakai {{ Format::rupiah($jajan) }}. Besok coba lebih hemat ya.
            @elseif ($f['next'])
                {{ $f['next']['sumber'] }} datang {{ Format::tgl($f['next']['tanggal_perkiraan']) }}, {{ $f['sisaHari'] }} hari lagi.
                @if ($f['status'] !== 'aman' && $f['batasAman'] !== null)
                    Supaya cukup, belanja maksimal <strong>{{ Format::rupiah($f['batasAman']) }}</strong> per hari.
                @endif
            @else
                Belum ada jadwal pemasukan. Tambahkan di menu Prediksi supaya peringatannya lebih tepat.
            @endif
        </p>

        <div class="fb-hp">
            <div class="fb-hp-head"><span>Daya tahan dompet</span><span>{{ $ketHp }}</span></div>
            <div class="fb-hp-bar" role="img" aria-label="Daya tahan dompet {{ $ketHp }}">
                @for ($i = 0; $i < 10; $i++)
                    <i class="{{ $i < $isi ? 'is-on' : '' }}"></i>
                @endfor
            </div>
        </div>

        <div class="fb-btns">
            <a href="{{ $ctaUrl }}" class="fb-btn fb-btn-dark"><x-ikon nama="plus" :size="18" :tebal="2.6" /> {{ $ctaTeks }}</a>
            <a href="{{ $ke('prediksi') }}" class="fb-btn fb-btn-light">Lihat prediksi</a>
        </div>

        @if ($f['rencanaDulu'] > 0)
            <p class="fb-note">Sudah memperhitungkan rencana pengeluaran {{ Format::rupiah($f['rencanaDulu']) }} sebelum uang berikutnya datang.</p>
        @endif
    </div>

    <div class="fb-stage" aria-hidden="true">
        <div class="fb-bubble">{{ $KATA[$mood] }}</div>
        <div class="fb-shadow"></div>
        <x-tupai :pose="$mood" class="fb-tupai" loading="eager" />
    </div>

    <svg class="fb-hill" viewBox="0 0 240 28" preserveAspectRatio="none" aria-hidden="true">
        <path d="M0 28V14c10-8 22-9 32-2 8-10 24-12 34-3 9-9 25-9 33 1 10-8 25-7 33 3 9-7 23-7 31 2 10-9 26-9 35 0 8-6 18-7 26-1 7-4 13-4 16-2V28Z" />
    </svg>
</section>
