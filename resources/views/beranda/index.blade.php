{{-- Beranda mahasiswa (port dari app/(app)/beranda/page.tsx versi Next.js).
     Bagian gamifikasi (streak, level, badge, tantangan) menyusul saat fitur Pencapaian dibuat. --}}
@extends('layouts.app')
@use('App\Support\Format')

@section('judul', 'Dashboard')
@section('judul_halaman', Format::sapaan() . ', ' . $user->namaDepan() . '!')

@php
    // tautan ke halaman yang belum dibuat di versi Laravel diarahkan ke '#'
    $ke = fn (string $rute, array $param = []) => Route::has($rute) ? route($rute, $param) : '#';
    $IKON_WALLET = ['tunai' => 'banknote', 'bank' => 'landmark', 'ewallet' => 'smartphone'];
    $KELAS_TX = ['pemasukan' => 'is-in', 'pengeluaran' => 'is-out', 'transfer' => 'is-move'];
    $WARNA_TX = ['pemasukan' => 'text-hsGreen', 'pengeluaran' => 'text-hsRed', 'transfer' => 'text-hsBlue'];
    $TANDA_TX = ['pemasukan' => '+', 'pengeluaran' => '−', 'transfer' => ''];
@endphp

@section('isi')
    @if ($tagihanTeman > 0)
        <a href="{{ $ke('utang.index') }}" class="fc-flash" style="background: #E8F1FF; color: #1E4FA8; border-color: #C7DBFF">
            <x-ikon nama="bell" /> Ada {{ $tagihanTeman }} tagihan dari teman ForeCash yang belum lunas. Lihat di Utang &amp; Patungan.
        </a>
    @endif

    <x-warning-banner :f="$f" :avatar="$user->avatar" />

    {{-- Ringkasan angka --}}
    <section class="fc-stats" aria-label="Ringkasan">
        <div class="hs-card fc-stat">
            <span class="fc-tile fc-tile-orange"><x-ikon nama="coins" :size="20" /></span>
            <div class="min-w-0"><p class="fc-stat-label">Total saldo</p><p class="fc-stat-value">{{ Format::rupiah($f['saldo']) }}</p></div>
        </div>
        <div class="hs-card fc-stat">
            <span class="fc-tile fc-tile-red"><x-ikon nama="flame" :size="20" /></span>
            <div class="min-w-0">
                <p class="fc-stat-label">Pengeluaran per hari</p>
                <p class="fc-stat-value">{{ Format::rupiah($f['burn']) }}</p>
                <p class="fc-stat-sub">rata-rata {{ $f['hari'] }} hari terakhir</p>
            </div>
        </div>
        <a href="{{ $ke('utang.index') }}" class="hs-card fc-stat">
            <span class="fc-tile fc-tile-green"><x-ikon nama="hand-coins" :size="20" /></span>
            <div class="min-w-0"><p class="fc-stat-label">Teman utang ke kamu</p><p class="fc-stat-value text-hsGreen">{{ Format::rupiah($piutang) }}</p></div>
        </a>
        <a href="{{ $ke('utang.index') }}" class="hs-card fc-stat">
            <span class="fc-tile fc-tile-purple"><x-ikon nama="user-minus" :size="20" /></span>
            <div class="min-w-0"><p class="fc-stat-label">Kamu utang ke teman</p><p class="fc-stat-value text-hsRed">{{ Format::rupiah($utang) }}</p></div>
        </a>
    </section>

    {{-- Rencana terdekat & target tabungan --}}
    @if ($rencanaDekat || $targetAktif)
        <section class="fc-stats" aria-label="Rencana" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr))">
            @if ($rencanaDekat)
                <a href="{{ $ke('rencana.index') }}" class="hs-card fc-stat">
                    <span class="fc-tile fc-tile-teal"><x-ikon nama="clipboard" :size="20" /></span>
                    <div class="min-w-0">
                        <p class="fc-stat-label">Rencana terdekat · {{ Format::tgl($rencanaDekat->tanggal) }}</p>
                        <p class="fc-stat-value" style="font-size: 18px">{{ $rencanaDekat->nama }} {{ Format::rupiah($rencanaDekat->nominal) }}</p>
                        <p class="fc-stat-sub">{{ $f['rencanaDulu'] > 0 ? 'Sudah dihitung di prediksi' : 'Jatuh setelah pemasukan berikutnya' }}</p>
                    </div>
                </a>
            @endif
            @if ($targetAktif)
                <a href="{{ $ke('rencana.index') }}" class="hs-card fc-stat">
                    <span class="fc-tile fc-tile-pink"><x-ikon nama="target" :size="20" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="fc-stat-label">Target tabungan · {{ $targetAktif->nama }}</p>
                        <p class="fc-stat-value" style="font-size: 18px">{{ $targetAktif->persen() }}% · {{ Format::rupiah($targetAktif->terkumpul) }}</p>
                        <div class="w-full bg-slate-100 rounded-full h-2 mt-1"><div class="bg-pink-400 h-2 rounded-full" style="width: {{ $targetAktif->persen() }}%"></div></div>
                    </div>
                </a>
            @endif
        </section>
    @endif

    {{-- Aksi cepat --}}
    <section aria-label="Catat cepat">
        <div class="fc-section-title"><h3>Mau catat apa?</h3></div>
        <div class="fc-actions">
            <a href="{{ $ke('transaksi.index', ['jenis' => 'pengeluaran']) }}" class="fc-action fc-action-out"><span class="fc-action-emoji">🍜</span><span><b>Pengeluaran</b><small>Jajan, ongkos, kos</small></span></a>
            <a href="{{ $ke('transaksi.index', ['jenis' => 'pemasukan']) }}" class="fc-action fc-action-in"><span class="fc-action-emoji">💰</span><span><b>Pemasukan</b><small>Uang saku, gaji part-time</small></span></a>
            <a href="{{ $ke('transaksi.index', ['jenis' => 'transfer']) }}" class="fc-action fc-action-move"><span class="fc-action-emoji">🔁</span><span><b>Transfer</b><small>Isi e-wallet dari rekening</small></span></a>
            <a href="{{ $ke('utang.index') }}" class="fc-action fc-action-split"><span class="fc-action-emoji">🍕</span><span><b>Split bill</b><small>Bagi tagihan bareng teman</small></span></a>
        </div>
    </section>

    {{-- Saldo per wallet --}}
    <section aria-label="Saldo per wallet">
        <div class="fc-section-title">
            <div><h3>Dompetmu</h3><p>Semua transaksi otomatis masuk ke wallet "Utama" kalau tidak dipilih.</p></div>
            @if (Route::has('wallet.index'))<a href="{{ route('wallet.index') }}" class="fc-link">Kelola wallet</a>@endif
        </div>
        <div class="fc-wallets">
            @foreach ($wallets as $i => $w)
                <div class="wallet-card fc-w{{ $i % 5 }}">
                    <p class="flex items-center gap-2 text-sm font-bold">
                        <span class="fc-wallet-icon"><x-ikon :nama="$IKON_WALLET[$w->tipe] ?? 'banknote'" :size="17" /></span> {{ $w->nama }}
                    </p>
                    <p class="fc-stat-value">{{ Format::rupiah($w->saldo_sekarang) }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Transaksi terakhir --}}
    <section class="hs-card p-5" aria-label="Transaksi terakhir">
        <div class="fc-section-title" style="margin-bottom: 4px">
            <h3 style="font-size: 19px">Transaksi terakhir</h3>
            @if (Route::has('transaksi.index'))<a href="{{ route('transaksi.index') }}" class="fc-link">Lihat semua</a>@endif
        </div>
        @forelse ($transaksi as $t)
            <div class="fc-tx">
                <span class="fc-tx-icon {{ $KELAS_TX[$t->jenis] }}">{{ Format::kategoriEmoji($t->category?->nama, $t->jenis) }}</span>
                <div class="min-w-0 flex-1">
                    <p class="font-bold text-sm truncate">{{ $t->keterangan }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">
                        <span class="fc-chip">{{ $t->jenis === 'transfer' ? $t->wallet?->nama . ' ke ' . $t->walletTujuan?->nama : ($t->category?->nama ?? 'Lainnya') }}</span>
                        {{ Format::tgl($t->tanggal) }}
                    </p>
                </div>
                <p class="font-display font-extrabold text-base {{ $WARNA_TX[$t->jenis] }}">{{ $TANDA_TX[$t->jenis] }}{{ Format::rupiah($t->nominal) }}</p>
            </div>
        @empty
            <p class="text-sm text-slate-500 py-3">Belum ada transaksi. Mulai dari tombol "Mau catat apa?" di atas.</p>
        @endforelse
    </section>
@endsection
