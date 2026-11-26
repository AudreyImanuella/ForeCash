{{-- Pencapaian: level, streak, tantangan mingguan, dan koleksi badge bertingkat
     (port dari app/(app)/pencapaian/page.tsx versi Next.js). --}}
@extends('layouts.app')
@use('App\Support\Gamify')

@section('judul', 'Pencapaian')
@section('judul_halaman', 'Pencapaian')

@section('isi')
    <x-badge-baru :uid="$user->id" :badges="$g['badges']" />
    <div><p class="text-xs text-slate-500">Badge didapat dari kebiasaan baik: hemat, patuh anggaran, menabung, patungan, dan melunasi utang. Setiap badge punya 3 tingkat.</p></div>

    <section class="hs-card p-6 flex flex-wrap items-center gap-6">
        <div class="w-24 h-24 bg-hsYellow/40 rounded-full flex items-end justify-center overflow-hidden shrink-0"><x-avatar :id="$user->avatar" :size="88" /></div>
        <div class="flex-1 min-w-[200px]">
            <p class="text-xl font-black">{{ $user->name }}</p>
            <div class="mt-2"><x-gamifikasi bagian="level" :g="$g" /></div>
        </div>
        <div class="flex gap-6">
            <div class="text-center">
                <x-ikon nama="flame" :size="30" class="mx-auto text-hsOrange" />
                <p class="text-xl font-black">{{ $g['streak'] }} hari</p>
                <p class="text-[11px] text-slate-500">streak · rekor {{ $g['streakTerpanjang'] }}</p>
            </div>
            <div class="text-center">
                <x-ikon nama="sparkles" :size="30" class="mx-auto text-hsPurple" />
                <p class="text-xl font-black">{{ $koleksi }}/{{ count($g['badges']) * 3 }}</p>
                <p class="text-[11px] text-slate-500">tingkat terkumpul</p>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <section class="hs-card p-6 space-y-2">
            <h4 class="text-sm font-black"><x-ikon nama="flag" :size="15" class="inline -mt-0.5 text-hsOrange" /> Tantangan Minggu Ini</h4>
            <x-gamifikasi bagian="tantangan" :t="$g['tantangan']" />
            <p class="text-[10px] text-slate-500">Tantangan berganti setiap Senin.</p>
        </section>
        @if ($g['hampir'])
            @php $h = $g['hampir']; @endphp
            <section class="hs-card p-6 flex items-center gap-4">
                <x-badge-ikon :ikon="$h['key']" :tingkat="$h['tingkat'] + 1" :size="64" />
                <div>
                    <p class="text-[11px] font-black text-hsOrange tracking-wide">SEDIKIT LAGI!</p>
                    <p class="font-black">{{ $h['nama'] }} · {{ Gamify::TINGKAT[$h['tingkat'] + 1] }}</p>
                    <p class="text-xs text-slate-500">{{ Gamify::sisaBadge($h) }} · {{ mb_strtolower($h['ket']) }}</p>
                </div>
            </section>
        @endif
    </div>

    <section class="space-y-3">
        <h4 class="text-sm font-black">Koleksi Badge</h4>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($urut as $b)
                <x-gamifikasi bagian="badge" :b="$b" />
            @endforeach
        </div>
    </section>

    <section class="hs-card p-5 text-xs text-slate-600 space-y-1">
        <p class="font-bold text-hsDark">Cara mendapat poin</p>
        <p>Perunggu {{ Gamify::POIN_TINGKAT[1] }} poin, Perak {{ Gamify::POIN_TINGKAT[2] }} poin, Emas {{ Gamify::POIN_TINGKAT[3] }} poin per badge. Setiap hari streak bernilai 10 poin (maks. 30 hari).</p>
    </section>
@endsection
