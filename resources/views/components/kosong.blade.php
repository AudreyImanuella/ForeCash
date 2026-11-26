{{-- Tampilan kosong dengan maskot tupai bermeditasi.
     Contoh: <x-kosong judul="Belum ada transaksi">Teks penjelasan, boleh berisi link.</x-kosong> --}}
@props(['judul', 'pose' => 'meditasi'])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center text-center gap-1 py-4']) }}>
    <x-tupai :pose="$pose" class="w-24 h-24" />
    <p class="font-black text-sm">{{ $judul }}</p>
    @if (trim($slot))
        <p class="text-xs text-slate-500 max-w-xs">{{ $slot }}</p>
    @endif
</div>
