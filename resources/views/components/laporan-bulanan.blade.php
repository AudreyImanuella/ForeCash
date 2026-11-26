{{-- Kartu laporan bulanan: grafik per kategori + ringkasan (port dari components/LaporanBulanan.tsx).
     Grafik batang dibuat dengan HTML/CSS biasa: satu warna, diurutkan dari yang terbesar.
     Dipakai halaman Laporan (mahasiswa) dan Pantau Anak (orang tua). Contoh: <x-laporan-bulanan :r="$laporan" /> --}}
@props(['r'])
@use('App\Support\Format')
@php
    $maks = collect($r['perKategori'])->max('total') ?: 1;
    $persen = fn ($t) => $r['keluar'] > 0 ? (int) round($t / $r['keluar'] * 100) : 0;
@endphp
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="hs-card p-6 space-y-3">
        <h4 class="text-sm font-black">Pengeluaran per Kategori</h4>
        @if (empty($r['perKategori']))
            <p class="text-xs text-slate-500">Belum ada pengeluaran di bulan ini.</p>
        @else
            <div class="space-y-2.5" role="img"
                 aria-label="Pengeluaran per kategori: {{ collect($r['perKategori'])->map(fn ($k) => $k['kategori'] . ' ' . Format::rupiah($k['total']))->join(', ') }}">
                @foreach ($r['perKategori'] as $k)
                    <div class="grid grid-cols-[120px_1fr] sm:grid-cols-[140px_1fr] items-center gap-2" title="{{ $k['kategori'] }}: {{ Format::rupiah($k['total']) }} ({{ $persen($k['total']) }}% dari total pengeluaran)">
                        <span class="text-xs font-semibold truncate">{{ $k['kategori'] }}</span>
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="h-7 rounded-r-md bg-hsOrange shrink-0" style="width: max(4px, {{ round($k['total'] / $maks * 70, 1) }}%)"></div>
                            <span class="text-xs font-bold whitespace-nowrap">{{ Format::rupiah($k['total']) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    <div class="hs-card p-6 space-y-3 text-sm">
        <h4 class="text-sm font-black">Ringkasan</h4>
        <p class="flex justify-between border-b py-2"><span class="text-slate-500">Total pemasukan</span><b class="text-hsGreen">{{ Format::rupiah($r['masuk']) }}</b></p>
        <p class="flex justify-between border-b py-2"><span class="text-slate-500">Total pengeluaran</span><b class="text-hsRed">{{ Format::rupiah($r['keluar']) }}</b></p>
        <p class="flex justify-between border-b py-2">
            <span class="text-slate-500">Rasio tabungan</span>
            <b class="text-hsBlue">{{ $r['rasio'] === null ? '-' : $r['rasio'] . '%' }}</b>
        </p>
        {{-- tabel per kategori: angka yang sama dengan grafik, untuk dibaca tanpa grafik --}}
        @foreach ($r['perKategori'] as $k)
            <p class="flex justify-between text-xs gap-2">
                <span>{{ $k['kategori'] }}</span>
                <span>{{ Format::rupiah($k['total']) }} <span class="text-slate-500">({{ $persen($k['total']) }}%)</span></span>
            </p>
        @endforeach
        <p class="text-[11px] text-slate-500 pt-1">Rasio tabungan = (pemasukan − pengeluaran) ÷ pemasukan.</p>
    </div>
</div>
