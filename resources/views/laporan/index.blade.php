{{-- Laporan Bulanan — desain baru (Nov 2026): header rapor dengan tupai, ringkasan berwarna,
     pengeluaran per kategori. Datanya sama seperti sebelumnya (App\Support\Laporan). --}}
@extends('layouts.app')
@use('App\Support\Format')

@section('judul', 'Laporan')
@section('judul_halaman', 'Laporan Bulanan')

@php
    $bulanIni = substr(Format::hariIni(), 0, 7);
    $bulanLalu = substr(Format::tambahBulan($bulan . '-01', -1), 0, 7);
    $bulanDepan = substr(Format::tambahBulan($bulan . '-01', 1), 0, 7);
    $rasio = $r['rasio'];
    $selisih = $r['masuk'] - $r['keluar'];

    if ($rasio === null) {
        [$pose, $judul] = [$r['keluar'] > 0 ? 'cemas' : 'senang', $r['keluar'] > 0 ? 'Ada pengeluaran, tapi belum ada pemasukan' : 'Belum ada transaksi di bulan ini'];
    } elseif ($rasio < 0) {
        [$pose, $judul] = ['cemas', 'Pengeluaranmu lebih besar dari pemasukan'];
    } else {
        [$pose, $judul] = [$rasio >= 20 ? 'bangga' : 'senang', 'Kamu menyisihkan ' . $rasio . '% pemasukan'];
    }

    $maks = collect($r['perKategori'])->max('total') ?: 1;
    $persen = fn ($t) => $r['keluar'] > 0 ? (int) round($t / $r['keluar'] * 100) : 0;
    $IKON_BG = ['#FFF2A0', '#C7ECF1', '#E3F2CF', '#FDDDEB', '#FFE3B8'];
@endphp

@section('isi')
<div class="fb fx lx">
    {{-- Header rapor --}}
    <section class="lx-hero" aria-label="Laporan {{ $namaBulan }}">
        <div class="lx-hero-teks">
            <span class="fb-tag">Laporan {{ $namaBulan }}</span>
            <h2>{{ $judul }}</h2>
            <p>Masuk <b>{{ Format::rupiah($r['masuk']) }}</b>, keluar <b>{{ Format::rupiah($r['keluar']) }}</b>.
                Transfer antar wallet tidak dihitung.</p>
            <div class="lx-hero-aksi">
                <nav class="fx-month" aria-label="Pilih bulan">
                    <a href="{{ route('laporan', ['bulan' => $bulanLalu]) }}" class="fx-month-btn" aria-label="Bulan sebelumnya"><x-ikon nama="chevron-left" :size="16" :tebal="3" /></a>
                    <span class="fb-d">{{ $namaBulan }}</span>
                    @if ($bulan < $bulanIni)
                        <a href="{{ route('laporan', ['bulan' => $bulanDepan]) }}" class="fx-month-btn" aria-label="Bulan berikutnya"><x-ikon nama="chevron-right" :size="16" :tebal="3" /></a>
                    @else
                        <span class="fx-month-btn is-off" aria-hidden="true"><x-ikon nama="chevron-right" :size="16" :tebal="3" /></span>
                    @endif
                </nav>
                <a href="{{ route('transaksi.export', ['bulan' => $bulan]) }}" class="fb-btn fb-btn-dark" title="Unduh CSV (bisa dibuka di Excel)">
                    <x-ikon nama="download" :size="16" /> Unduh Excel (.csv)
                </a>
            </div>
        </div>
        <x-tupai :pose="$pose" alt="" class="lx-tupai" loading="eager" />
    </section>

    {{-- Ringkasan berwarna --}}
    <section class="fx-sum" aria-label="Ringkasan bulan">
        <div class="fx-sum-card" style="--c: #E3F2CF">
            <span class="fx-sum-ic"><x-ikon nama="banknote" :size="20" /></span>
            <p class="fx-sum-label">Total pemasukan</p>
            <p class="fx-sum-value fx-in">{{ Format::rupiah($r['masuk']) }}</p>
        </div>
        <div class="fx-sum-card" style="--c: #FDDDEB">
            <span class="fx-sum-ic"><x-ikon nama="receipt" :size="20" /></span>
            <p class="fx-sum-label">Total pengeluaran</p>
            <p class="fx-sum-value">{{ Format::rupiah($r['keluar']) }}</p>
        </div>
        <div class="fx-sum-card" style="--c: #C7ECF1">
            <span class="fx-sum-ic"><x-ikon nama="piggy-bank" :size="20" /></span>
            <p class="fx-sum-label">Rasio tabungan</p>
            <p class="fx-sum-value">{{ $rasio === null ? '-' : $rasio . '%' }}</p>
            <p class="fx-sum-note">(pemasukan − pengeluaran) ÷ pemasukan</p>
        </div>
    </section>

    {{-- Pengeluaran per kategori: satu warna, urut dari terbesar --}}
    <section class="fb-card" aria-label="Pengeluaran per kategori">
        <div class="fb-head">
            <h3>Pengeluaran per kategori</h3>
            @if ($r['keluar'] > 0)<span class="fx-meta"><b>total {{ Format::rupiah($r['keluar']) }}</b></span>@endif
        </div>
        @if (empty($r['perKategori']))
            <div class="ux-kosong">
                <x-tupai pose="senang" alt="" class="ux-kosong-tupai" />
                <p>Belum ada pengeluaran di bulan ini.</p>
            </div>
        @else
            <div class="lx-kat">
                @foreach ($r['perKategori'] as $i => $k)
                    <div class="lx-kat-baris">
                        <span class="rx-ic" style="background: {{ $IKON_BG[$i % 5] }}"><x-ikon :nama="Format::kategoriIkon($k['kategori'])" :size="18" /></span>
                        <div class="lx-kat-isi">
                            <div class="lx-kat-atas">
                                <b class="lx-kat-nama">{{ $k['kategori'] }}</b>
                                <b class="fb-d">{{ Format::rupiah($k['total']) }}</b>
                                <span class="lx-kat-pct">{{ $persen($k['total']) }}%</span>
                            </div>
                            <div class="rx-bar" role="img" aria-label="{{ $k['kategori'] }}: {{ Format::rupiah($k['total']) }}, {{ $persen($k['total']) }}% dari total pengeluaran">
                                <i style="width: {{ max(2, round($k['total'] / $maks * 100, 1)) }}%; background: #FFA934"></i>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
