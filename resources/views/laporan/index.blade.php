{{-- Laporan Bulanan (port dari app/(app)/laporan/page.tsx versi Next.js). --}}
@extends('layouts.app')

@section('judul', 'Laporan')
@section('judul_halaman', 'Laporan Bulanan')

@section('isi')
    <div class="flex flex-wrap justify-between items-center gap-3">
        <div>
            <p class="text-sm font-bold">{{ $namaBulan }}</p>
            <p class="text-xs text-slate-500">Transfer antar wallet tidak dihitung sebagai pemasukan maupun pengeluaran.</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <form method="GET" action="{{ route('laporan') }}" class="flex gap-1">
                <input type="month" name="bulan" value="{{ $bulan }}" aria-label="Pilih bulan" class="px-3 py-2 text-xs border">
                <button type="submit" class="hs-btn bg-hsYellow text-hsDark px-3 py-2 text-xs">Lihat</button>
            </form>
            <a href="{{ route('transaksi.export', ['bulan' => $bulan]) }}" class="hs-btn bg-hsGreen text-white px-4 py-2 text-xs">
                <x-ikon nama="download" :size="14" /> Ekspor Excel (.csv)
            </a>
        </div>
    </div>

    <x-laporan-bulanan :r="$r" />
@endsection
