{{-- Prediksi & jadwal pemasukan — desain baru (Nov 2026): hero tupai sesuai status, ringkasan berwarna,
     grafik proyeksi + simulasi (digambar oleh resources/js/app.js bagian "Prediksi"), jadwal pemasukan. --}}
@extends('layouts.app')
@use('App\Support\Format')

@section('judul', 'Prediksi')
@section('judul_halaman', 'Prediksi & Notifikasi')

@php
    $st = $f['status'];
    $mood = ['aman' => 'senang', 'waspada' => 'cemas', 'kritis' => 'nangis'][$st] ?? 'senang';
    $TAG = ['aman' => 'Status: aman', 'waspada' => 'Status: waspada', 'kritis' => 'Status: kritis'];
    $KATA = ['senang' => 'Dompet kita sehat, lanjutkan!', 'cemas' => 'Eh, kok cepat menipis ya?', 'nangis' => 'Huhu, uang kita hampir habis...'];
    $next = $f['next'];
    $cukup = $f['runway'] === null || ($next && $f['runway'] >= $f['sisaHari']);

    if ($f['runway'] === null) {
        $judul = 'Belum ada pengeluaran tercatat';
    } elseif ($next && $cukup) {
        $judul = 'Uangmu cukup sampai ' . Format::tgl($next['tanggal_perkiraan']);
    } elseif ($f['runway'] === 0) {
        $judul = 'Saldo bebasmu sudah habis';
    } else {
        $judul = 'Uangmu cukup sampai ' . Format::tgl($f['habis']);
    }

    if ($next) {
        $sub = $next['sumber'] . ' datang ' . Format::tgl($next['tanggal_perkiraan'])
            . ($f['sisaHari'] > 0 ? ', ' . $f['sisaHari'] . ' hari lagi.' : ', hari ini.');
    } else {
        $sub = 'Belum ada jadwal pemasukan. Tambahkan di bawah supaya peringatannya akurat.';
    }
    $terlambat = count($f['terlambat']);
    $BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
@endphp

@section('isi')
<div class="fb fx px">
    <p class="fx-sub">Dihitung otomatis dari transaksi {{ $f['hari'] }} hari terakhir.</p>

    {{-- Hero: tupai ikut status --}}
    <section class="fb-hero px-hero" data-mood="{{ $mood }}" aria-label="Peringatan dini">
        <svg class="fb-land" viewBox="0 0 1000 160" preserveAspectRatio="none" aria-hidden="true">
            <defs>
                <linearGradient id="px-jauh" x1="0" x2="0" y1="0" y2="1"><stop offset="0" style="stop-color: var(--far-a)" /><stop offset="1" style="stop-color: var(--far-b)" /></linearGradient>
                <linearGradient id="px-tengah" x1="0" x2="0" y1="0" y2="1"><stop offset="0" style="stop-color: var(--mid-a)" /><stop offset="1" style="stop-color: var(--mid-b)" /></linearGradient>
            </defs>
            <path d="M0 70 C120 30 230 40 340 62 C450 84 560 28 690 40 C800 50 900 30 1000 46 L1000 160 L0 160 Z" fill="url(#px-jauh)" />
            <path d="M0 110 C140 78 260 84 380 100 C520 118 620 76 760 82 C860 86 940 100 1000 94 L1000 160 L0 160 Z" fill="url(#px-tengah)" />
        </svg>
        <div class="fb-hero-text">
            <span class="fb-tag">{{ $TAG[$st] }}</span>
            <h2>{{ $judul }}</h2>
            <p>{{ $sub }}
                @if ($f['batasAman'] !== null && $f['runway'] !== null)
                    Supaya cukup, belanja maksimal <b>{{ Format::rupiah($f['batasAman']) }}</b> per hari.
                @endif
            </p>
            @if ($f['rencanaDulu'] > 0)
                <p class="fb-note">Sudah dipotong rencana pengeluaran {{ Format::rupiah($f['rencanaDulu']) }}.
                    <a href="{{ route('rencana.index') }}" class="px-link">Lihat rencana</a></p>
            @endif
            @if ($terlambat)
                <p class="fb-note"><b>{{ $terlambat }} jadwal pemasukan sudah lewat tanggalnya.</b> Tandai kalau uangnya sudah diterima.</p>
            @endif
        </div>
        <div class="fb-stage" aria-hidden="true">
            <div class="fb-bubble">{{ $KATA[$mood] }}</div>
            <div class="fb-shadow"></div>
            <x-tupai :pose="$mood" class="fb-tupai" loading="eager" />
        </div>
    </section>

    {{-- Ringkasan berwarna --}}
    <section class="fx-sum" aria-label="Ringkasan prediksi">
        <div class="fx-sum-card" style="--c: #FFF2A0">
            <span class="fx-sum-ic"><x-ikon nama="wallet" :size="20" /></span>
            <p class="fx-sum-label">Total saldo</p>
            <p class="fx-sum-value">{{ Format::rupiah($f['saldo']) }}</p>
            <p class="fx-sum-note">{{ $wallets->count() }} wallet</p>
        </div>
        <div class="fx-sum-card" style="--c: #FDDDEB">
            <span class="fx-sum-ic"><x-ikon nama="flame" :size="20" /></span>
            <p class="fx-sum-label">Pengeluaran per hari</p>
            <p class="fx-sum-value">{{ Format::rupiah($f['burn']) }}</p>
            <p class="fx-sum-note">rata-rata {{ $f['hari'] }} hari terakhir</p>
        </div>
        <div class="fx-sum-card" style="--c: #C7ECF1">
            <span class="fx-sum-ic"><x-ikon nama="calendar-check" :size="20" /></span>
            <p class="fx-sum-label">Daya tahan dompet</p>
            <p class="fx-sum-value">{{ $f['runway'] !== null ? $f['runway'] . ' hari' : '-' }}</p>
            <p class="fx-sum-note">{{ $f['sisaHari'] !== null ? 'pemasukan berikutnya ' . ($f['sisaHari'] ? $f['sisaHari'] . ' hari lagi' : 'hari ini') : 'belum ada jadwal pemasukan' }}</p>
        </div>
    </section>

    {{-- Grafik + simulasi --}}
    <div class="fb-grid" data-proyeksi
         data-hari="{{ $hari }}" data-saldo="{{ $f['saldo'] }}" data-burn="{{ $f['burn'] }}"
         data-rencana-dulu="{{ $f['rencanaDulu'] }}" data-sisa-hari="{{ $f['sisaHari'] ?? '' }}"
         data-rencana='@json($rencana)'>
        <section class="fb-card px-grafik-kartu fb-main" aria-label="Proyeksi saldo">
            <div class="fb-head">
                <h3>Proyeksi saldo 30 hari</h3>
                <span class="px-leg"><i style="background: #00A0B5"></i>Saldo</span>
                <span class="px-leg"><i class="is-putus"></i>Simulasi</span>
            </div>
            <div class="px-grafik" role="img" data-grafik
                 aria-label="Grafik proyeksi saldo: dari {{ Format::rupiah($f['saldo']) }} hari ini, berkurang {{ Format::rupiah($f['burn']) }} per hari."></div>
            <p class="fx-meta">Area pink = saldo diperkirakan habis. Garis hijau putus-putus = tanggal pemasukan berikutnya.</p>
        </section>

        <section class="fx-form px-sim fb-side" aria-label="Simulasi">
            <h2 class="fx-form-title" style="padding-right: 0">Coba simulasi</h2>
            <div>
                <label for="simBurn" class="fx-label">Kalau jajan per hari jadi</label>
                <div class="fx-rp"><span aria-hidden="true">Rp</span>
                    <input id="simBurn" type="text" inputmode="numeric" autocomplete="off" value="{{ Format::isianNominal($f['batasAman'] ?? $f['burn']) }}"
                           class="fx-input fx-input-big" data-rupiah data-sim-burn></div>
            </div>
            <div class="px-sim-hasil" aria-live="polite" data-sim-hasil></div>
            <p class="fx-meta">Garis oranye putus-putus di grafik ikut berubah saat angkanya diganti.</p>
        </section>
    </div>

    {{-- Jadwal pemasukan --}}
    <div class="fb-grid">
        <section class="fb-card fb-main" aria-label="Jadwal pemasukan">
            <div class="fb-head"><h3>Jadwal pemasukan</h3></div>
            <div class="rx-list">
                @forelse ($jadwal as $s)
                    @php $telat = $s->tanggal_perkiraan->toDateString() < $hari; @endphp
                    <div class="rx-item">
                        <div class="rx-tgl" style="background: {{ $telat ? '#FDDDEB' : '#E3F2CF' }}">
                            <b class="fb-d">{{ $s->tanggal_perkiraan->day }}</b><span>{{ $BULAN[$s->tanggal_perkiraan->month - 1] }}</span>
                        </div>
                        <div class="rx-info">
                            <p class="rx-nama">{{ $s->sumber }}</p>
                            <p class="fx-meta">
                                @if ($telat)
                                    <b>Lewat dari {{ Format::tgl($s->tanggal_perkiraan) }}, sudah diterima?</b>
                                @else
                                    Perkiraan {{ Format::tgl($s->tanggal_perkiraan) }}
                                @endif
                            </p>
                        </div>
                        <p class="rx-nominal fx-in">+{{ Format::rupiah($s->nominal) }}</p>
                        <div class="rx-aksi">
                            <form method="POST" action="{{ route('prediksi.terima', $s) }}" class="rx-bayar">
                                @csrf
                                <select name="wallet_id" aria-label="Masuk ke wallet" class="fx-input">
                                    @foreach ($wallets as $w) <option value="{{ $w->id }}">{{ $w->nama }}</option> @endforeach
                                </select>
                                <button type="submit" class="fb-btn px-btn-hijau">Sudah diterima</button>
                            </form>
                            <form method="POST" action="{{ route('prediksi.hapus', $s) }}" data-konfirmasi="Hapus jadwal ini?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="fx-act is-hapus" title="Hapus" aria-label="Hapus jadwal {{ $s->sumber }}"><x-ikon nama="trash" :size="15" /></button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="ux-kosong">
                        <x-tupai pose="senang" alt="" class="ux-kosong-tupai" />
                        <p>Belum ada jadwal pemasukan. Tambahkan uang saku, honor, atau beasiswa agar peringatan bisa dihitung.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <form method="POST" action="{{ route('prediksi.store') }}" class="fx-form fb-side" style="background: #C7ECF1">
            @csrf
            <h2 class="fx-form-title" style="padding-right: 0">Tambah jadwal</h2>
            @if ($errors->jadwal->any())
                <p class="fx-error">{{ $errors->jadwal->first() }}</p>
            @endif
            <div>
                <label for="js" class="fx-label">Sumber</label>
                <input id="js" name="sumber" required maxlength="50" value="{{ old('sumber') }}" placeholder="Uang saku dari orang tua" class="fx-input">
            </div>
            <div>
                <label for="jn" class="fx-label">Perkiraan nominal</label>
                <div class="fx-rp"><span aria-hidden="true">Rp</span>
                    <input id="jn" name="nominal" required type="text" inputmode="numeric" autocomplete="off" value="{{ old('nominal') }}"
                           placeholder="1.500.000" class="fx-input fx-input-big" data-rupiah></div>
            </div>
            <div>
                <label for="jt" class="fx-label">Tanggal perkiraan</label>
                <input id="jt" type="date" name="tanggal" required value="{{ old('tanggal') }}" class="fx-input">
            </div>
            <button type="submit" class="fb-btn fb-btn-dark fx-submit">Simpan jadwal</button>
        </form>
    </div>
</div>
@endsection
