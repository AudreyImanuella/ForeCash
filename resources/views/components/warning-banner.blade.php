{{-- Banner peringatan dini (port dari components/WarningBanner.tsx versi Next.js).
     Langitnya ikut status: cerah (aman), senja (waspada), mendung + hujan (kritis).
     Contoh: <x-warning-banner :f="$f" :avatar="$user->avatar" /> --}}
@props(['f', 'avatar' => 'a1'])
@use('App\Support\Format')
@use('App\Services\Forecast')
@php
    $st = $f['status'];
    $LABEL = ['aman' => 'Aman', 'waspada' => 'Waspada', 'kritis' => 'Kritis'];
    $DOT = ['aman' => '#2EBD74', 'waspada' => '#F0A000', 'kritis' => '#FF5B5B'];
    $KATA = ['aman' => 'Aman, lanjutkan!', 'waspada' => 'Pelan-pelan ya...', 'kritis' => 'Gawat, rem dulu!'];
    $BUKIT1 = ['aman' => '#7FD6A0', 'waspada' => '#F4B860', 'kritis' => '#A79FCB'];
    $BUKIT2 = ['aman' => '#4CC283', 'waspada' => '#E89A3C', 'kritis' => '#8C83B8'];
    $MOOD = ['aman' => 'happy', 'waspada' => 'neutral', 'kritis' => 'worried'];

    // hari-hari menuju pemasukan berikutnya: koin = hari yang masih tertutup saldo
    $hariIni = Format::hariIni();
    $hariTotal = $f['sisaHari'] ? min(45, $f['sisaHari']) : 0;
    $hariAman = $f['runway'] === null ? $hariTotal : min($hariTotal, $f['runway']);
@endphp
<section class="fc-hero" data-status="{{ $st }}" aria-label="Peringatan dini">
    <svg class="fc-hero-scene" viewBox="0 0 1200 320" preserveAspectRatio="xMidYMax slice" aria-hidden="true">
        @if ($st === 'aman')
            <circle cx="840" cy="70" r="120" fill="#FFF3B0" opacity=".55" />
            <x-maskot x="770" y="0" width="140" height="140" />
            <g fill="#fff" opacity=".9"><ellipse cx="640" cy="70" rx="60" ry="20" /><ellipse cx="680" cy="55" rx="40" ry="24" /><ellipse cx="1000" cy="40" rx="46" ry="14" /></g>
        @elseif ($st === 'waspada')
            <circle cx="830" cy="210" r="150" fill="#FFB347" opacity=".35" />
            <x-maskot x="765" y="130" width="130" height="130" />
            <g fill="#FFF4DB" opacity=".9"><ellipse cx="650" cy="80" rx="70" ry="18" /><ellipse cx="1010" cy="50" rx="50" ry="14" /></g>
        @else
            <x-maskot x="790" y="60" width="110" height="110" opacity=".55" />
            <g fill="#8E86B5"><ellipse cx="830" cy="80" rx="110" ry="40" /><ellipse cx="890" cy="60" rx="70" ry="42" /><ellipse cx="760" cy="70" rx="60" ry="34" /></g>
            <g stroke="#7FA4E6" stroke-width="5" stroke-linecap="round" class="fc-rain">
                <line x1="770" y1="130" x2="762" y2="150" /><line x1="815" y1="140" x2="807" y2="160" /><line x1="860" y1="128" x2="852" y2="148" />
                <line x1="905" y1="138" x2="897" y2="158" /><line x1="790" y1="175" x2="782" y2="195" /><line x1="880" y1="178" x2="872" y2="198" />
            </g>
        @endif
        {{-- bukit --}}
        <path d="M640 320 C760 210 880 200 1000 250 C1080 280 1150 240 1200 220 L1200 320 Z" fill="{{ $BUKIT1[$st] }}" />
        <path d="M760 320 C860 260 980 250 1090 290 C1140 305 1180 300 1200 296 L1200 320 Z" fill="{{ $BUKIT2[$st] }}" />
    </svg>

    <div class="fc-hero-text">
        <span class="fc-hero-badge">
            <span class="fc-hero-dot" style="background: {{ $DOT[$st] }}">
                @if ($st === 'aman')
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                @elseif ($st === 'waspada')
                    <b>!</b>
                @else
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15.914 4a1.5 1.5 0 00-2.474-1.561l-9 9A1.5 1.5 0 005.5 14h4.002a.5.5 0 01.471.666L8.086 20a1.5 1.5 0 002.475 1.56l9-9A1.5 1.5 0 0018.5 10h-3.997a.5.5 0 01-.472-.667z"/></svg>
                @endif
            </span>
            Status {{ $LABEL[$st] }}
        </span>

        <h2>
            @if ($f['burn'] <= 0)
                Belum ada pengeluaran yang tercatat.
            @elseif ($f['saldo'] <= 0)
                Saldo sudah habis.
            @else
                Saldo cukup sampai <span class="fc-hero-date">{{ Format::tgl($f['habis']) }}</span>
            @endif
        </h2>

        <p>
            @if ($f['next'])
                {{ $f['next']['sumber'] }} datang {{ Format::tgl($f['next']['tanggal_perkiraan']) }}, {{ $f['sisaHari'] }} hari lagi.
                @if ($st !== 'aman' && $f['batasAman'] !== null)
                    Supaya cukup, belanja maksimal <strong>{{ Format::rupiah($f['batasAman']) }}</strong> per hari.
                @endif
            @else
                Belum ada jadwal pemasukan. Tambahkan di menu Prediksi supaya peringatannya lebih tepat.
            @endif
        </p>

        @if ($f['rencanaDulu'] > 0)
            <p class="fc-hero-plan">Sudah memperhitungkan rencana pengeluaran {{ Format::rupiah($f['rencanaDulu']) }} sebelum uang berikutnya datang.</p>
        @endif

        @foreach ($f['terlambat'] as $t)
            <p style="color: #C22C2C">{{ $t['sumber'] }} ({{ Format::rupiah($t['nominal']) }}) belum datang sejak {{ Format::tgl($t['tanggal_perkiraan']) }}.</p>
        @endforeach

        @if ($hariTotal > 0)
            <div class="fc-days">
                <div class="fc-days-head">
                    <span>Hari menuju pemasukan</span>
                    <span>{{ $hariAman }} dari {{ $hariTotal }} hari tertutup saldo</span>
                </div>
                <div class="fc-days-row">
                    @for ($i = 1; $i <= $hariTotal; $i++)
                        <span class="fc-day {{ $i === $hariTotal ? 'is-pay' : ($i <= $hariAman ? 'is-ok' : 'is-short') }}"
                              title="{{ Format::tgl(Forecast::tambahHari($hariIni, $i)) }}"></span>
                    @endfor
                </div>
                <div class="fc-days-legend">
                    <span><i class="fc-day is-ok"></i> Saldo cukup</span>
                    @if ($hariAman < $hariTotal - 1)
                        <span><i class="fc-day is-short"></i> Saldo kurang</span>
                    @endif
                    <span><i class="fc-day is-pay"></i> Pemasukan</span>
                </div>
            </div>
        @elseif ($f['runway'] !== null)
            <div class="fc-days"><div class="fc-days-head"><span>Daya tahan saldo</span><span>{{ $f['runway'] }} hari lagi</span></div></div>
        @endif
    </div>

    {{-- maskot bereaksi: ekspresi avatar mengikuti status keuangan --}}
    <div class="fc-hero-mascot">
        <div class="fc-bubble">{{ $KATA[$st] }}</div>
        <div class="fc-hero-avatar character-bounce"><x-avatar :id="$avatar" :mood="$MOOD[$st]" :size="136" /></div>
    </div>
</section>
