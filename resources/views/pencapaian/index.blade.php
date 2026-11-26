{{-- Pencapaian — desain baru (Nov 2026): header level dengan tupai, tantangan & "sedikit lagi" berwarna,
     koleksi badge bertingkat. Datanya sama seperti sebelumnya (App\Support\Gamify). --}}
@extends('layouts.app')
@use('App\Support\Format')
@use('App\Support\Gamify')

@section('judul', 'Pencapaian')
@section('judul_halaman', 'Pencapaian')

@php
    $pctLevel = $g['nextPoin'] ? (int) round(($g['poin'] - $g['prevPoin']) / ($g['nextPoin'] - $g['prevPoin']) * 100) : 100;
    $ketLevel = $g['nextPoin'] ? ($g['nextPoin'] - $g['poin']) . ' poin lagi menuju ' . $g['nextNama'] : 'Level tertinggi tercapai!';
    $totalTingkat = count($g['badges']) * 3;

    $t = $g['tantangan'];
    $pctT = (int) min(100, round($t['now'] / $t['max'] * 100));
    $angka = fn ($n) => $t['satuan'] === 'rp' ? Format::rupiah($n) : $n . ' hari';
    if ($t['jenis'] === 'maks') {
        $ketT = $t['gagal'] ? 'Lewat batas, coba lagi minggu depan' : 'sisa ' . Format::rupiah($t['max'] - $t['now']) . ', ' . $t['sisaHari'] . ' hari lagi';
    } else {
        $ketT = $t['selesai'] ? 'Selesai! Mantap.' : ($t['gagal'] ? 'Tidak terkejar minggu ini, coba lagi minggu depan' : ($t['max'] - $t['now']) . ' hari lagi, sisa ' . $t['sisaHari'] . ' hari');
    }
    $warnaT = $t['selesai'] ? '#14704F' : ($t['gagal'] ? '#F478B0' : ($t['jenis'] === 'maks' ? '#98C54E' : '#FFA934'));
@endphp

@section('isi')
<x-badge-baru :uid="$user->id" :badges="$g['badges']" />
<div class="fb fx cx">
    {{-- Header: profil, level, streak --}}
    <section class="cx-hero" aria-label="Level kamu">
        <div class="cx-avatar"><x-avatar :id="$user->avatar" :size="96" /></div>
        <div class="cx-hero-teks">
            <span class="fb-tag">Level {{ $g['level'] }}</span>
            <h2>{{ $g['levelNama'] }}</h2>
            <p class="cx-nama">{{ $user->name }} · <b>{{ $g['poin'] }} poin</b></p>
            <div class="cx-lv-bar" role="progressbar" aria-valuenow="{{ $pctLevel }}" aria-valuemin="0" aria-valuemax="100" aria-label="Kemajuan level">
                <i style="width: {{ $pctLevel }}%"></i>
            </div>
            <p class="cx-lv-ket">{{ $ketLevel }}</p>
            <div class="cx-chips">
                <span class="cx-chip" style="background: #FFE3B8"><x-ikon nama="flame" :size="18" /> <b>{{ $g['streak'] }} hari</b> streak · rekor {{ $g['streakTerpanjang'] }}</span>
                <span class="cx-chip" style="background: #C7ECF1"><x-ikon nama="sparkles" :size="18" /> <b>{{ $koleksi }}/{{ $totalTingkat }}</b> tingkat badge</span>
            </div>
        </div>
        <x-tupai pose="bangga" alt="" class="lx-tupai cx-tupai" loading="eager" />
    </section>

    <p class="fx-sub">Badge didapat dari kebiasaan baik: hemat, patuh anggaran, menabung, patungan, dan melunasi utang. Setiap badge punya 3 tingkat.</p>

    <div class="cx-duo">
        {{-- Tantangan minggu ini --}}
        <section class="cx-kotak" style="background: #E3F2CF" aria-label="Tantangan minggu ini">
            <div class="cx-kotak-head"><span class="rx-ic" style="background: #fff"><x-ikon nama="flag" :size="18" /></span><h3 class="fb-d">Tantangan minggu ini</h3></div>
            <p class="rx-nama" style="white-space: normal">{{ $t['judul'] }}</p>
            <div class="rx-bar is-besar" style="background: #fff" role="progressbar" aria-valuenow="{{ $pctT }}" aria-valuemin="0" aria-valuemax="100" aria-label="Kemajuan tantangan">
                <i style="width: {{ $pctT }}%; background: {{ $warnaT }}"></i>
            </div>
            <p class="cx-ket">{{ $angka($t['now']) }}{{ $t['jenis'] === 'maks' ? ' terpakai' : ' dari ' . $angka($t['max']) }} · <b>{{ $ketT }}</b></p>
            <p class="fx-meta" style="font-size: 13px">Tantangan berganti setiap Senin.</p>
        </section>

        {{-- Sedikit lagi --}}
        @if ($g['hampir'])
            @php $h = $g['hampir']; @endphp
            <section class="cx-kotak cx-hampir" style="background: #FDDDEB" aria-label="Badge yang hampir didapat">
                <div class="cx-hampir-ic"><x-badge-ikon :ikon="$h['key']" :tingkat="$h['tingkat'] + 1" :size="72" /></div>
                <div>
                    <span class="cx-label">Sedikit lagi!</span>
                    <p class="cx-hampir-nama fb-d">{{ $h['nama'] }} · {{ Gamify::TINGKAT[$h['tingkat'] + 1] }}</p>
                    <p class="cx-ket">{{ Gamify::sisaBadge($h) }} · {{ mb_strtolower($h['ket']) }}</p>
                </div>
            </section>
        @endif
    </div>

    {{-- Koleksi badge --}}
    <section aria-label="Koleksi badge">
        <div class="fb-head"><h3>Koleksi badge</h3><span class="fx-meta"><b>{{ $koleksi }}/{{ $totalTingkat }} tingkat</b></span></div>
        <div class="cx-grid">
            @foreach ($urut as $b)
                <article class="cx-badge {{ $b['tingkat'] === 0 ? 'is-kunci' : '' }}">
                    <x-badge-ikon :ikon="$b['key']" :tingkat="$b['tingkat']" :size="64" />
                    <p class="cx-badge-nama">{{ $b['nama'] }}</p>
                    <span class="fc-tier fc-tier-{{ $b['tingkat'] }}">{{ Gamify::TINGKAT[$b['tingkat']] }}</span>
                    <p class="cx-badge-ket">{{ $b['ket'] }}</p>
                    @if ($b['berikut'] !== null)
                        <div class="rx-bar" style="width: 100%; margin-top: 0" role="progressbar" aria-valuenow="{{ $b['persen'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Kemajuan {{ $b['nama'] }}">
                            <i style="width: {{ $b['persen'] }}%; background: #FFA934"></i>
                        </div>
                        <p class="cx-badge-sisa">{{ Gamify::sisaBadge($b) }} menuju {{ Gamify::TINGKAT[$b['tingkat'] + 1] }}</p>
                    @else
                        <p class="cx-badge-sisa" style="color: #14704F">Tingkat tertinggi!</p>
                    @endif
                </article>
            @endforeach
        </div>
    </section>

    <section class="cx-info" aria-label="Cara mendapat poin">
        <span class="rx-ic" style="background: #FFF2A0"><x-ikon nama="star" :size="18" /></span>
        <p><b>Cara mendapat poin:</b> Perunggu {{ Gamify::POIN_TINGKAT[1] }} poin, Perak {{ Gamify::POIN_TINGKAT[2] }} poin, Emas {{ Gamify::POIN_TINGKAT[3] }} poin per badge.
            Setiap hari streak bernilai 10 poin (maks. 30 hari).</p>
    </section>
</div>
@endsection
