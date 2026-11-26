{{-- Halaman Transaksi mahasiswa (port dari app/(app)/transaksi/page.tsx versi Next.js). --}}
@extends('layouts.app')
@use('App\Support\Format')

@section('judul', 'Transaksi')
@section('judul_halaman', 'Transaksi')

@php
    $KELAS_TX = ['pemasukan' => 'is-in', 'pengeluaran' => 'is-out', 'transfer' => 'is-move'];
    $WARNA_TX = ['pemasukan' => 'text-hsGreen', 'pengeluaran' => 'text-hsRed', 'transfer' => 'text-hsBlue'];
    $TANDA_TX = ['pemasukan' => '+', 'pengeluaran' => '−', 'transfer' => ''];
@endphp

@section('isi')
    <div><p class="text-xs text-slate-500">Catat pemasukan, pengeluaran, dan transfer antar wallet.</p></div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            @include('transaksi._form', ['aksi' => route('transaksi.store'), 't' => null])

            <div class="hs-card p-5 space-y-3">
                <div class="flex flex-wrap justify-between items-center gap-2">
                    <h4 class="text-sm font-black">Riwayat Transaksi</h4>
                    {{-- ganti bulan: halaman dimuat ulang dengan ?bulan=YYYY-MM --}}
                    <form method="GET" action="{{ route('transaksi.index') }}" class="flex items-center gap-1">
                        <input type="month" name="bulan" value="{{ $bulan }}" aria-label="Pilih bulan" class="px-3 py-1.5 text-xs border">
                        <button type="submit" class="hs-btn bg-hsYellow text-hsDark px-3 py-1.5 text-xs">Lihat</button>
                    </form>
                </div>

                @forelse ($transaksi as $t)
                    <div class="fc-tx">
                        <span class="fc-tx-icon {{ $KELAS_TX[$t->jenis] }}">{{ Format::kategoriEmoji($t->category?->nama, $t->jenis) }}</span>
                        <div class="text-sm min-w-0 flex-1">
                            <p class="font-bold truncate">{{ $t->keterangan }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                <span class="fc-chip">{{ $t->jenis === 'transfer' ? $t->wallet?->nama . ' ke ' . $t->walletTujuan?->nama : ($t->category?->nama ?? 'Lainnya') }}</span>
                                {{ Format::tgl($t->tanggal) }}@if ($t->jenis !== 'transfer') · {{ $t->wallet?->nama }}@endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <p class="font-display font-extrabold text-base {{ $WARNA_TX[$t->jenis] }}">{{ $TANDA_TX[$t->jenis] }}{{ Format::rupiah($t->nominal) }}</p>
                            @if ($t->bukti)
                                <a href="{{ route('transaksi.struk', $t) }}" target="_blank" rel="noopener"
                                   class="w-8 h-8 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center"
                                   title="Lihat foto struk" aria-label="Lihat foto struk"><x-ikon nama="receipt-text" :size="14" /></a>
                            @endif
                            <a href="{{ route('transaksi.edit', $t) }}"
                               class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center"
                               title="Ubah" aria-label="Ubah transaksi"><x-ikon nama="pencil" :size="13" /></a>
                            <form method="POST" action="{{ route('transaksi.destroy', $t) }}" onsubmit="return confirm('Hapus transaksi ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-8 h-8 rounded-full bg-red-50 text-hsRed flex items-center justify-center"
                                        title="Hapus" aria-label="Hapus transaksi"><x-ikon nama="trash" :size="13" /></button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-500">Belum ada transaksi di bulan ini.</p>
                @endforelse
            </div>
        </div>

        {{-- kolom kanan: saldo tiap wallet (Batas harian & Kategori saya menyusul di tahap lain) --}}
        <div class="space-y-6">
            <div class="hs-card p-5 space-y-2">
                <h4 class="text-sm font-black">Saldo Wallet</h4>
                @foreach ($wallets as $w)
                    <div class="flex justify-between text-sm">
                        <span>{{ $w->nama }}</span>
                        <b class="{{ $w->saldo_sekarang < 0 ? 'text-hsRed' : '' }}">{{ Format::rupiah($w->saldo_sekarang) }}</b>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection