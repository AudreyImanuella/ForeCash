{{-- Pantau Anak, khusus orang tua — desain baru (Nov 2026): pilih anak (pil avatar), hero Chip sesuai status
     keuangan anak, ringkasan berwarna, transaksi terakhir, pengeluaran per kategori, form hubungkan akun.
     Datanya sama seperti sebelumnya (PantauController). Hanya-baca: perubahan dilakukan anak sendiri. --}}
@extends('layouts.app')
@use('App\Support\Format')

@section('judul', 'Pantau Anak')
@section('judul_halaman', 'Pantau Anak')

@php
    $IKON_BG = ['#FFF2A0', '#C7ECF1', '#E3F2CF', '#FDDDEB', '#FFE3B8'];
    if ($anak && $data) {
        $f = $data['f'];
        $st = $f['status'];
        $mood = ['aman' => 'senang', 'waspada' => 'cemas', 'kritis' => 'nangis'][$st] ?? 'senang';
        $TAG = ['aman' => 'Status: aman', 'waspada' => 'Status: waspada', 'kritis' => 'Status: kritis'];
        $KATA = ['senang' => 'Aman, tenang aja!', 'cemas' => 'Mulai menipis nih...', 'nangis' => 'Perlu dibantu ngerem!'];
        $nama = \Illuminate\Support\Str::of($anak->name)->explode(' ')->first();
        $next = $f['next'];
        if ($f['runway'] === null) {
            $judul = $nama . ' belum mencatat pengeluaran';
        } elseif ($next && $f['runway'] >= $f['sisaHari']) {
            $judul = 'Uang ' . $nama . ' cukup sampai ' . Format::tgl($next['tanggal_perkiraan']);
        } elseif ($f['runway'] === 0) {
            $judul = 'Saldo bebas ' . $nama . ' sudah habis';
        } else {
            $judul = 'Uang ' . $nama . ' cukup sampai ' . Format::tgl($f['habis']);
        }
        $sub = $next
            ? $next['sumber'] . ' berikutnya ' . Format::tgl($next['tanggal_perkiraan']) . ($f['sisaHari'] > 0 ? ', ' . $f['sisaHari'] . ' hari lagi.' : ', hari ini.')
            : 'Belum ada jadwal pemasukan di akun ' . $nama . '.';
        $r = $data['laporan'];
        $maks = collect($r['perKategori'])->max('total') ?: 1;
        $persen = fn ($t) => $r['keluar'] > 0 ? (int) round($t / $r['keluar'] * 100) : 0;
    }
@endphp

@section('isi')
<div class="fb fx px">
    <div class="fx-top">
        <p class="fx-sub">Data hanya dapat dilihat. Perubahan dilakukan oleh mahasiswa sendiri.</p>
        @if ($anakList->count() > 1)
            <nav class="pa-anak" aria-label="Pilih anak">
                @foreach ($anakList as $a)
                    <a href="{{ route('pantau', ['anak' => $a->id]) }}" class="{{ $anak && $a->id === $anak->id ? 'is-on' : '' }}"
                       @if ($anak && $a->id === $anak->id) aria-current="page" @endif>
                        <span class="pa-anak-av"><x-avatar :id="$a->avatar" :size="28" /></span>{{ $a->name }}
                    </a>
                @endforeach
            </nav>
        @endif
    </div>

    @if (! $anak || ! $data)
        {{-- belum ada anak terhubung --}}
        <section class="lx-hero" aria-label="Hubungkan akun anak">
            <div class="lx-hero-teks">
                <span class="fb-tag">Pantau anak</span>
                <h2>Hubungkan akun anak dulu, yuk</h2>
                <p>Minta anak membuka menu <b>Akun</b> di ForeCash untuk melihat <b>kode hubung</b>, lalu masukkan di bawah.</p>
                <form method="POST" action="{{ route('pantau.hubungkan') }}" class="rx-baris-form pa-hubung">
                    @csrf
                    <input name="kode" value="{{ old('kode') }}" maxlength="8" placeholder="Kode hubung, mis. DEMO2026" aria-label="Kode hubung" class="fx-input" autocomplete="off">
                    <button type="submit" class="fb-btn fb-btn-dark">Hubungkan</button>
                </form>
                @error('kode') <p class="fx-error">{{ $message }}</p> @enderror
            </div>
            <x-tupai pose="melambai" alt="" class="lx-tupai" loading="eager" />
        </section>
    @else
        {{-- Hero: Chip ikut status keuangan anak --}}
        <section class="fb-hero px-hero" data-mood="{{ $mood }}" aria-label="Kondisi keuangan {{ $anak->name }}">
            <svg class="fb-land" viewBox="0 0 1000 160" preserveAspectRatio="none" aria-hidden="true">
                <defs>
                    <linearGradient id="pa-jauh" x1="0" x2="0" y1="0" y2="1"><stop offset="0" style="stop-color: var(--far-a)" /><stop offset="1" style="stop-color: var(--far-b)" /></linearGradient>
                    <linearGradient id="pa-tengah" x1="0" x2="0" y1="0" y2="1"><stop offset="0" style="stop-color: var(--mid-a)" /><stop offset="1" style="stop-color: var(--mid-b)" /></linearGradient>
                </defs>
                <path d="M0 70 C120 30 230 40 340 62 C450 84 560 28 690 40 C800 50 900 30 1000 46 L1000 160 L0 160 Z" fill="url(#pa-jauh)" />
                <path d="M0 110 C140 78 260 84 380 100 C520 118 620 76 760 82 C860 86 940 100 1000 94 L1000 160 L0 160 Z" fill="url(#pa-tengah)" />
            </svg>
            <div class="fb-hero-text">
                <div class="pa-hero-anak">
                    <span class="pa-hero-av"><x-avatar :id="$anak->avatar" :size="40" /></span>
                    <span class="fb-tag">{{ $TAG[$st] }}</span>
                </div>
                <h2>{{ $judul }}</h2>
                <p>{{ $sub }}
                    @if ($f['batasAman'] !== null && $f['runway'] !== null)
                        Batas aman belanja <b>{{ Format::rupiah($f['batasAman']) }}</b> per hari.
                    @endif
                </p>
            </div>
            <div class="fb-stage" aria-hidden="true">
                <div class="fb-bubble">{{ $KATA[$mood] }}</div>
                <div class="fb-shadow"></div>
                <x-tupai :pose="$mood" class="fb-tupai" loading="eager" />
            </div>
        </section>

        {{-- Ringkasan berwarna --}}
        <section class="fx-sum" aria-label="Ringkasan {{ $anak->name }}">
            <div class="fx-sum-card" style="--c: #FFF2A0">
                <span class="fx-sum-ic"><x-ikon nama="wallet" :size="20" /></span>
                <p class="fx-sum-label">Total saldo</p>
                <p class="fx-sum-value">{{ Format::rupiah($f['saldo']) }}</p>
            </div>
            <div class="fx-sum-card" style="--c: #FFE3B8">
                <span class="fx-sum-ic"><x-ikon nama="flame" :size="20" /></span>
                <p class="fx-sum-label">Pengeluaran per hari</p>
                <p class="fx-sum-value">{{ Format::rupiah($f['burn']) }}</p>
            </div>
            <div class="fx-sum-card" style="--c: #E3F2CF">
                <span class="fx-sum-ic"><x-ikon nama="banknote" :size="20" /></span>
                <p class="fx-sum-label">Pemasukan {{ $data['namaBulan'] }}</p>
                <p class="fx-sum-value fx-in">{{ Format::rupiah($data['masuk']) }}</p>
            </div>
            <div class="fx-sum-card" style="--c: #FDDDEB">
                <span class="fx-sum-ic"><x-ikon nama="receipt" :size="20" /></span>
                <p class="fx-sum-label">Pengeluaran {{ $data['namaBulan'] }}</p>
                <p class="fx-sum-value">{{ Format::rupiah($data['keluar']) }}</p>
            </div>
        </section>

        <div class="fb-grid">
            <div class="fb-main">
                {{-- Transaksi terakhir --}}
                <section class="fb-card" aria-label="Transaksi terakhir">
                    <div class="fb-head"><h3>Transaksi terakhir</h3></div>
                    <div class="rx-list">
                        @forelse ($data['transaksi'] as $i => $t)
                            @php $masuk = $t->jenis === 'pemasukan'; @endphp
                            <div class="rx-item pa-tx">
                                <span class="rx-ic" style="background: {{ $masuk ? '#E3F2CF' : $IKON_BG[$i % 5] }}"><x-ikon :nama="Format::kategoriIkon($t->category?->nama, $t->jenis)" :size="18" /></span>
                                <div class="rx-info">
                                    <p class="rx-nama">{{ $t->keterangan ?: ($t->category?->nama ?? 'Transaksi') }}</p>
                                    <p class="fx-meta">{{ Format::tgl($t->tanggal) }} · {{ $t->category?->nama ?? 'Tanpa kategori' }}</p>
                                </div>
                                <p class="rx-nominal {{ $masuk ? 'fx-in' : '' }}">{{ $masuk ? '+' : '−' }}{{ Format::rupiah($t->nominal) }}</p>
                            </div>
                        @empty
                            <div class="ux-kosong">
                                <x-tupai pose="senang" alt="" class="ux-kosong-tupai" />
                                <p>Belum ada transaksi.</p>
                            </div>
                        @endforelse
                    </div>
                </section>

                {{-- Pengeluaran per kategori bulan ini --}}
                <section class="fb-card" aria-label="Pengeluaran per kategori">
                    <div class="fb-head">
                        <h3>Laporan {{ $data['namaBulan'] }}</h3>
                        <span class="fx-meta"><b>Rasio tabungan {{ $r['rasio'] === null ? '-' : $r['rasio'] . '%' }}</b></span>
                    </div>
                    @if (empty($r['perKategori']))
                        <p class="fx-meta">Belum ada pengeluaran di bulan ini.</p>
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
                        <p class="fx-meta" style="font-size: 13px; margin-top: 12px">Rasio tabungan = (pemasukan − pengeluaran) ÷ pemasukan.</p>
                    @endif
                </section>
            </div>

            <aside class="fb-side">
                <form method="POST" action="{{ route('pantau.hubungkan') }}" class="fx-form" style="background: #C7ECF1">
                    @csrf
                    <h2 class="fx-form-title" style="padding-right: 0">Hubungkan anak lain</h2>
                    <p class="fx-meta">Kode hubung ada di menu <b>Akun</b> milik anak.</p>
                    <input id="kode" name="kode" value="{{ old('kode') }}" maxlength="8" placeholder="Kode hubung, mis. DEMO2026" aria-label="Kode hubung" class="fx-input" autocomplete="off" style="text-transform: uppercase">
                    @error('kode') <p class="fx-error">{{ $message }}</p> @enderror
                    <button type="submit" class="fb-btn fb-btn-dark fx-submit">Hubungkan</button>
                </form>
            </aside>
        </div>
    @endif
</div>
@endsection
