{{-- Pantau Anak, khusus orang tua (port dari app/(app)/pantau/page.tsx versi Next.js). --}}
@extends('layouts.app')
@use('App\Support\Format')

@section('judul', 'Pantau Anak')
@section('judul_halaman', 'Pantau Anak')

@section('isi')
    <div class="flex flex-wrap justify-between items-end gap-4">
        <div><p class="text-xs text-slate-500">Data hanya dapat dilihat. Perubahan dilakukan oleh mahasiswa sendiri.</p></div>
        <div class="flex flex-wrap gap-2">
            @foreach ($anakList as $a)
                <a href="{{ route('pantau', ['anak' => $a->id]) }}"
                   class="nav-tab {{ $anak && $a->id === $anak->id ? 'active' : 'bg-white' }} px-4 py-2 text-xs">{{ $a->name }}</a>
            @endforeach
        </div>
    </div>

    {{-- form hubungkan akun anak (port dari pantau/components/FormHubung.tsx) --}}
    <form method="POST" action="{{ route('pantau.hubungkan') }}" class="hs-card p-5 flex flex-wrap items-end gap-3">
        @csrf
        <div class="space-y-1 flex-1 min-w-[200px]">
            <label for="kode" class="text-xs font-bold uppercase tracking-wider">Hubungkan akun anak</label>
            <input id="kode" name="kode" value="{{ old('kode') }}" maxlength="8" placeholder="Kode hubung, contoh DEMO2026"
                   class="w-full px-4 py-3 border text-sm font-semibold uppercase tracking-widest">
            @error('kode') <p class="text-xs font-bold text-hsRed">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="hs-btn bg-hsOrange text-white px-6 py-3">Hubungkan</button>
    </form>

    @if (! $anak || ! $data)
        <p class="hs-card p-6 text-sm text-slate-500">
            Belum ada akun anak yang terhubung. Minta anak membuka menu <b>Akun</b> di ForeCash untuk melihat kode hubung.
        </p>
    @else
        {{-- banner memakai avatar anak, bukan avatar orang tua --}}
        <x-warning-banner :f="$data['f']" :avatar="$anak->avatar" />

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="hs-card p-5"><p class="text-xs font-bold text-slate-500">Total Saldo</p><p class="text-xl font-black">{{ Format::rupiah($data['f']['saldo']) }}</p></div>
            <div class="hs-card p-5"><p class="text-xs font-bold text-slate-500">Burn Rate / Hari</p><p class="text-xl font-black">{{ Format::rupiah($data['f']['burn']) }}</p></div>
            <div class="hs-card p-5"><p class="text-xs font-bold text-slate-500">Pemasukan Bulan Ini</p><p class="text-xl font-black text-hsGreen">{{ Format::rupiah($data['masuk']) }}</p></div>
            <div class="hs-card p-5"><p class="text-xs font-bold text-slate-500">Pengeluaran Bulan Ini</p><p class="text-xl font-black text-hsRed">{{ Format::rupiah($data['keluar']) }}</p></div>
        </div>

        <div class="hs-card p-5 space-y-2">
            <h4 class="text-sm font-black">Transaksi Terakhir</h4>
            @forelse ($data['transaksi'] as $t)
                <div class="flex justify-between gap-2 text-sm border-b last:border-0 py-2">
                    <span class="min-w-0"><b>{{ $t->keterangan }}</b> <span class="text-xs text-slate-500">· {{ Format::tgl($t->tanggal) }} · {{ $t->category?->nama ?? '-' }}</span></span>
                    <b class="shrink-0 {{ $t->jenis === 'pemasukan' ? 'text-hsGreen' : 'text-hsRed' }}">{{ $t->jenis === 'pemasukan' ? '+' : '−' }}{{ Format::rupiah($t->nominal) }}</b>
                </div>
            @empty
                <p class="text-xs text-slate-500">Belum ada transaksi.</p>
            @endforelse
        </div>

        <h3 class="text-sm font-black">Laporan {{ $data['namaBulan'] }}</h3>
        <x-laporan-bulanan :r="$data['laporan']" />
    @endif
@endsection
