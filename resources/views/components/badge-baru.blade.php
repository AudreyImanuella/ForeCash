{{-- Notifikasi "Badge baru!" saat ada badge yang naik tingkat sejak kunjungan terakhir (port dari BadgeBaru.tsx).
     Tingkat terakhir disimpan di browser (localStorage) per akun; kunjungan pertama hanya mencatat.
     Logikanya di resources/js/app.js (bagian "Badge baru"). Contoh: <x-badge-baru :uid="$user->id" :badges="$g['badges']" /> --}}
@props(['uid', 'badges'])
@use('App\Support\Gamify')
<div class="fc-badge-toast" role="status" aria-live="polite" hidden
     data-badge-baru="fc_badge_{{ $uid }}"
     data-badges='@json(collect($badges)->mapWithKeys(fn ($b) => [$b['key'] => $b['tingkat']]))'>
    @foreach ($badges as $b)
        {{-- satu template per badge; JavaScript menampilkan yang naik tingkat --}}
        <template data-badge="{{ $b['key'] }}">
            <x-badge-ikon :ikon="$b['key']" :tingkat="$b['tingkat']" :size="56" />
            <div class="min-w-0 flex-1">
                <p class="text-[11px] font-black text-hsOrange tracking-wide">BADGE BARU!</p>
                <p class="font-black text-sm">{{ $b['nama'] }} · {{ Gamify::TINGKAT[$b['tingkat']] }}</p>
                <p class="text-[11px] text-slate-500"><span data-lain></span><a href="{{ route('pencapaian') }}" class="font-bold text-hsOrange">Lihat koleksi</a></p>
            </div>
            <button type="button" data-tutup-toast class="w-8 h-8 rounded-full inline-flex items-center justify-center text-slate-500" aria-label="Tutup">
                <x-ikon nama="x" :size="16" />
            </button>
        </template>
    @endforeach
</div>
