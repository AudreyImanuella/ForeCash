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
                    <div class="flex items-center gap-2">
                        <form method="GET" action="{{ route('transaksi.index') }}" class="flex items-center gap-1">
                            <input type="month" name="bulan" value="{{ $bulan }}" aria-label="Pilih bulan" class="px-3 py-1.5 text-xs border">
                            <button type="submit" class="hs-btn bg-hsYellow text-hsDark px-3 py-1.5 text-xs">Lihat</button>
                        </form>
                        <a href="{{ route('transaksi.export', ['bulan' => $bulan]) }}" class="hs-btn bg-slate-800 text-white px-3 py-1.5 text-xs"
                           title="Unduh CSV (bisa dibuka di Excel)"><x-ikon nama="download" :size="13" /> CSV</a>
                    </div>
                </div>

                @forelse ($transaksi as $t)
                    <div class="fc-tx">
                        <span class="fc-tx-icon {{ $KELAS_TX[$t->jenis] }}"><x-ikon :nama="Format::kategoriIkon($t->category?->nama, $t->jenis)" :size="20" /></span>
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
                            <form method="POST" action="{{ route('transaksi.destroy', $t) }}" data-konfirmasi="Hapus transaksi ini?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-8 h-8 rounded-full bg-red-50 text-hsRed flex items-center justify-center"
                                        title="Hapus" aria-label="Hapus transaksi"><x-ikon nama="trash" :size="13" /></button>
                            </form>
                        </div>
                    </div>
                @empty
                    <x-kosong judul="Belum ada transaksi di bulan ini">Tenang dulu, lalu catat pengeluaran pertamamu.</x-kosong>
                @endforelse
            </div>
        </div>

        {{-- kolom kanan: saldo wallet, batas harian, kategori saya --}}
        <div class="space-y-6">
            <div class="hs-card p-5 space-y-2">
                <div class="flex justify-between items-center">
                    <h4 class="text-sm font-black">Saldo Wallet</h4>
                    <a href="{{ route('wallet.index') }}" class="fc-link text-xs">Kelola</a>
                </div>
                @foreach ($wallets as $w)
                    <div class="flex justify-between text-sm">
                        <span>{{ $w->nama }}</span>
                        <b class="{{ $w->saldo_sekarang < 0 ? 'text-hsRed' : '' }}">{{ Format::rupiah($w->saldo_sekarang) }}</b>
                    </div>
                @endforeach
            </div>

            {{-- port dari BatasHarianForm.tsx --}}
            @php $batas = (float) $user->batas_harian; $lewat = $keluarHariIni > $batas; @endphp
            <form method="POST" action="{{ route('transaksi.batas') }}" class="hs-card p-5 space-y-2">
                @csrf
                @method('PUT')
                <h4 class="text-sm font-black">Batas Pengeluaran Harian</h4>
                <input name="batas_harian" type="text" inputmode="numeric" autocomplete="off" value="{{ Format::isianNominal($batas) }}"
                       aria-label="Batas pengeluaran harian (Rp)" class="w-full px-3 py-2 text-sm border" data-rupiah>
                <button type="submit" class="hs-btn bg-slate-800 text-white w-full py-2 text-xs">Simpan</button>
                @if ($batas > 0)
                    <p class="text-[11px] font-bold {{ $lewat ? 'text-hsRed' : 'text-hsGreen' }}">
                        Hari ini: {{ Format::rupiah($keluarHariIni) }} dari {{ Format::rupiah($batas) }} {{ $lewat ? '— melebihi batas!' : '— masih aman.' }}
                    </p>
                @else
                    <p class="text-[11px] text-slate-500">Isi 0 kalau tidak ingin dibatasi.</p>
                @endif
            </form>

            {{-- port dari KategoriSaya.tsx --}}
            <div class="hs-card p-5 space-y-3">
                <h4 class="text-sm font-black"><x-ikon nama="tags" :size="15" class="inline -mt-0.5 text-hsOrange" /> Kategori Saya</h4>
                @if ($errors->kategori->any())
                    <p class="text-xs font-bold text-hsRed">{{ $errors->kategori->first() }}</p>
                @endif
                <form method="POST" action="{{ route('kategori.store') }}" class="space-y-2">
                    @csrf
                    <input name="nama" maxlength="50" placeholder="Mis. Print/Fotokopi" aria-label="Nama kategori" class="w-full px-3 py-2 text-xs border">
                    <div class="flex gap-2">
                        <select name="jenis" aria-label="Jenis kategori" class="w-full px-3 py-2 text-xs border">
                            <option value="pengeluaran">Pengeluaran</option>
                            <option value="pemasukan">Pemasukan</option>
                        </select>
                        <button type="submit" class="hs-btn bg-hsOrange text-white px-3 py-2 text-xs">Tambah</button>
                    </div>
                </form>
                <div class="flex flex-wrap gap-1.5">
                    @forelse ($kategori->where('user_id', $user->id) as $k)
                        <form method="POST" action="{{ route('kategori.destroy', $k) }}" data-konfirmasi="Hapus kategori {{ $k->nama }}?"
                              class="inline-flex items-center gap-1 bg-slate-100 rounded-full pl-3 pr-1 py-1 text-[11px] font-bold">
                            @csrf
                            @method('DELETE')
                            {{ $k->nama }}
                            <button type="submit" class="w-5 h-5 rounded-full hover:bg-white inline-flex items-center justify-center"
                                    title="Hapus" aria-label="Hapus kategori {{ $k->nama }}"><x-ikon nama="x" :size="12" /></button>
                        </form>
                    @empty
                        <p class="text-xs text-slate-500">Kategori bawaan sudah tersedia. Tambahkan kategori khusus di sini.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection