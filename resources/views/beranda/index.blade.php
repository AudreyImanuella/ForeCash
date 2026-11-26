{{-- Beranda mahasiswa — desain baru (Nov 2026): kartu tanpa outline, tupai di bukit, gamifikasi di atas. --}}
@extends('layouts.app')
@use('App\Support\Format')

@section('judul', 'Dashboard')
@section('judul_halaman', Format::sapaan() . ', ' . $user->namaDepan() . '!')

@php
    // tautan ke halaman yang belum ada diarahkan ke '#'
    $ke = fn (string $rute, array $param = []) => Route::has($rute) ? route($rute, $param) : '#';
    $IKON_WALLET = ['tunai' => 'banknote', 'bank' => 'landmark', 'ewallet' => 'smartphone'];
    $KELAS_TX = ['pemasukan' => 'is-in', 'pengeluaran' => 'is-out', 'transfer' => 'is-move'];
    $WARNA_TX = ['pemasukan' => 'fb-in', 'pengeluaran' => 'fb-out', 'transfer' => 'fb-move'];
    $TANDA_TX = ['pemasukan' => '+', 'pengeluaran' => '−', 'transfer' => ''];
    $WARNA_WALLET = ['#F7BF45', '#9ED9BE', '#C9CDF6', '#F7A27A', '#FFD98A'];

    // persen bar level (sama dengan rumus di komponen gamifikasi)
    $pctLevel = $g['nextPoin'] ? (int) round(($g['poin'] - $g['prevPoin']) / ($g['nextPoin'] - $g['prevPoin']) * 100) : 100;
@endphp

@section('isi')
<div class="fb">
    @if ($tagihanTeman > 0)
        <a href="{{ $ke('utang.index') }}" class="fc-flash" style="background: #E8F1FF; color: #1E4FA8; border-color: #C7DBFF">
            <x-ikon nama="bell" /> Ada {{ $tagihanTeman }} tagihan dari teman ForeCash yang belum lunas. Lihat di Utang &amp; Patungan.
        </a>
    @endif

    <x-badge-baru :uid="$user->id" :badges="$g['badges']" />

    {{-- 1. Bar gamifikasi: level + poin, streak --}}
    <div class="fb-chips">
        <a href="{{ $ke('pencapaian') }}" class="fb-chip fb-chip-level" aria-label="Level {{ $g['level'] }}, {{ $g['poin'] }} poin">
            <span class="fb-lv">{{ $g['level'] }}</span>
            <span class="fb-lv-info">
                <span class="fb-lv-row"><span>{{ $g['levelNama'] }}</span><small>{{ $g['poin'] }}{{ $g['nextPoin'] ? '/' . $g['nextPoin'] : '' }} poin</small></span>
                <span class="fb-bar"><span style="width: {{ $pctLevel }}%"></span></span>
            </span>
        </a>
        <span class="fb-chip">
            <span class="fb-flame"><x-ikon nama="flame" :size="20" /></span>
            <span class="fb-d">{{ $g['streak'] }} hari</span> beruntun
        </span>
    </div>

    <div class="fb-grid">
        {{-- ===== Kolom kiri ===== --}}
        <div class="fb-main">
            {{-- 2. Kartu utama dengan tupai --}}
            <x-hero-tupai :f="$f" :mood="$mood" :jajan="$jajanHariIni" :batas="$batasHarian" />

            {{-- 3. Ringkasan angka --}}
            <section class="fb-stats" aria-label="Ringkasan">
                <div class="fb-stat" style="--c: #F7BF45">
                    <span class="fb-stat-ic"><x-ikon nama="wallet" :size="20" /></span>
                    <p class="fb-stat-label">Total saldo</p>
                    <p class="fb-stat-value">{{ Format::rupiah($f['saldo']) }}</p>
                    <p class="fb-stat-sub">{{ $wallets->count() }} wallet</p>
                </div>
                <div class="fb-stat" style="--c: #F7A27A">
                    <span class="fb-stat-ic"><x-ikon nama="flame" :size="20" /></span>
                    <p class="fb-stat-label">Pengeluaran per hari</p>
                    <p class="fb-stat-value">{{ Format::rupiah($f['burn']) }}</p>
                    <p class="fb-stat-sub">rata-rata {{ $f['hari'] }} hari terakhir</p>
                </div>
                <a href="{{ $ke('utang.index') }}" class="fb-stat" style="--c: #9ED9BE">
                    <span class="fb-stat-ic"><x-ikon nama="hand-coins" :size="20" /></span>
                    <p class="fb-stat-label">Teman utang ke kamu</p>
                    <p class="fb-stat-value fb-in">{{ Format::rupiah($piutang) }}</p>
                    <p class="fb-stat-sub">belum lunas</p>
                </a>
                <a href="{{ $ke('utang.index') }}" class="fb-stat" style="--c: #C9CDF6">
                    <span class="fb-stat-ic"><x-ikon nama="user-minus" :size="20" /></span>
                    <p class="fb-stat-label">Kamu utang ke teman</p>
                    <p class="fb-stat-value fb-out">{{ Format::rupiah($utang) }}</p>
                    <p class="fb-stat-sub">belum lunas</p>
                </a>
            </section>

            {{-- 4. Transaksi terakhir --}}
            <section class="fb-card" aria-label="Transaksi terakhir">
                <div class="fb-head">
                    <h3>Transaksi terakhir</h3>
                    @if (Route::has('transaksi.index'))<a href="{{ route('transaksi.index') }}" class="fb-link">Lihat semua</a>@endif
                </div>
                @forelse ($transaksi as $tx)
                    <div class="fb-tx">
                        <span class="fb-tx-ic {{ $KELAS_TX[$tx->jenis] }}"><x-ikon :nama="Format::kategoriIkon($tx->category?->nama, $tx->jenis)" :size="20" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="fb-tx-name">{{ $tx->keterangan }}</p>
                            <p class="fb-tx-meta">
                                @if ($tx->jenis === 'transfer')
                                    {{ $tx->wallet?->nama }} ke {{ $tx->walletTujuan?->nama }}
                                @else
                                    {{ $tx->category?->nama ?? 'Lainnya' }} · {{ $tx->wallet?->nama }}
                                @endif
                                · {{ Format::tgl($tx->tanggal) }}
                            </p>
                        </div>
                        <p class="fb-tx-amt {{ $WARNA_TX[$tx->jenis] }}">{{ $TANDA_TX[$tx->jenis] }}{{ Format::rupiah($tx->nominal) }}</p>
                        @if (Route::has('transaksi.edit'))
                            <a href="{{ route('transaksi.edit', $tx) }}" class="fb-icon-btn" aria-label="Ubah {{ $tx->keterangan }}"><x-ikon nama="pencil" :size="16" /></a>
                        @endif
                    </div>
                @empty
                    <x-kosong judul="Belum ada transaksi">Mulai dari tombol "Catat transaksi" di atas.</x-kosong>
                @endforelse
            </section>
        </div>

        {{-- ===== Kolom kanan ===== --}}
        <aside class="fb-side">
            {{-- 5. Tantangan minggu ini (data asli dari Gamify) --}}
            <a href="{{ $ke('pencapaian') }}" class="fb-card">
                <div class="fb-head" style="margin-bottom: 8px"><h3>Tantangan minggu ini</h3></div>
                <x-gamifikasi bagian="tantangan" :t="$g['tantangan']" />
            </a>

            {{-- 6. Target tabungan --}}
            @if ($targetAktif)
                <a href="{{ $ke('rencana.index') }}" class="fb-target">
                    <x-tupai pose="koin" alt="" />
                    <p class="fb-kicker">Target tabungan</p>
                    <h3 class="fb-target-name">{{ $targetAktif->nama }}</h3>
                    <p class="fb-target-sub">{{ Format::rupiah($targetAktif->terkumpul) }} dari {{ Format::rupiah($targetAktif->target) }}</p>
                    <div class="fb-track"><span style="width: {{ $targetAktif->persen() }}%"></span></div>
                    <p class="fb-d" style="font-size: 20px">{{ $targetAktif->persen() }}%</p>
                </a>
            @endif

            {{-- 7. Rencana terdekat --}}
            @if ($rencanaDekat)
                <a href="{{ $ke('rencana.index') }}" class="fb-card">
                    <p class="fb-kicker" style="color: var(--fb-muted)">Rencana terdekat · {{ Format::tgl($rencanaDekat->tanggal) }}</p>
                    <p class="fb-d" style="font-size: 20px; margin-top: 6px">{{ $rencanaDekat->nama }}</p>
                    <p class="fb-stat-sub">{{ Format::rupiah($rencanaDekat->nominal) }} · {{ $f['rencanaDulu'] > 0 ? 'sudah dihitung di prediksi' : 'jatuh setelah pemasukan berikutnya' }}</p>
                </a>
            @endif

            {{-- 8. Dompet --}}
            <section class="fb-card" aria-label="Saldo per wallet">
                <div class="fb-head">
                    <h3>Dompetmu</h3>
                    @if (Route::has('wallet.index'))<a href="{{ route('wallet.index') }}" class="fb-link">Kelola</a>@endif
                </div>
                @foreach ($wallets as $i => $w)
                    <div class="fb-wallet">
                        <span class="fb-tx-ic" style="background: {{ $WARNA_WALLET[$i % 5] }}"><x-ikon :nama="$IKON_WALLET[$w->tipe] ?? 'banknote'" :size="18" /></span>
                        <span class="flex-1 min-w-0 font-bold truncate">{{ $w->nama }}</span>
                        <span class="fb-d">{{ Format::rupiah($w->saldo_sekarang) }}</span>
                    </div>
                @endforeach
            </section>

        </aside>
    </div>
</div>
@endsection
