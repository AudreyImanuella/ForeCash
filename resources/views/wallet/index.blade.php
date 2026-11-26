{{-- Kelola Wallet (port dari app/(app)/wallet/page.tsx + components/WalletForms.tsx versi Next.js). --}}
@extends('layouts.app')
@use('App\Support\Format')
@use('App\Models\Wallet')

@section('judul', 'Kelola Wallet')
@section('judul_halaman', 'Kelola Wallet')

@section('isi')
    <div>
        <p class="text-xs text-slate-500"><a href="{{ route('beranda') }}" class="font-bold">Dashboard</a> › Kelola Wallet</p>
        <p class="text-xs text-slate-500">Pisahkan saldo tunai, bank, dan e-wallet. Pindah saldo antar wallet dicatat lewat menu Transaksi → Transfer.</p>
    </div>

    @if ($errors->any())
        <p class="fc-flash is-error" role="alert">{{ $errors->first() }}</p>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 hs-card p-5 space-y-3">
            @foreach ($wallets as $w)
                <div class="border-b last:border-0 pb-3 space-y-2">
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-end">
                        <form method="POST" action="{{ route('wallet.update', $w) }}" class="sm:col-span-10 grid grid-cols-1 sm:grid-cols-10 gap-2 items-end">
                            @csrf
                            @method('PUT')
                            <div class="sm:col-span-4">
                                <label for="n{{ $w->id }}" class="text-[11px] font-bold">Nama</label>
                                <input id="n{{ $w->id }}" name="nama" value="{{ $w->nama }}" required maxlength="50" class="w-full px-3 py-2 text-xs border">
                            </div>
                            <div class="sm:col-span-3">
                                <label for="t{{ $w->id }}" class="text-[11px] font-bold">Tipe</label>
                                <select id="t{{ $w->id }}" name="tipe" class="w-full px-3 py-2 text-xs border">
                                    @foreach (Wallet::TIPE as $k => $label)
                                        <option value="{{ $k }}" @selected($w->tipe === $k)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label for="s{{ $w->id }}" class="text-[11px] font-bold">Saldo awal</label>
                                <input id="s{{ $w->id }}" name="saldo_awal" type="text" inputmode="numeric" autocomplete="off"
                                       value="{{ Format::isianNominal($w->saldo_awal) }}" class="w-full px-3 py-2 text-xs border" data-rupiah>
                            </div>
                            <div class="sm:col-span-1">
                                <button type="submit" class="hs-btn bg-hsBlue text-white px-3 py-2 text-xs w-full" title="Simpan" aria-label="Simpan wallet {{ $w->nama }}">
                                    <x-ikon nama="save" :size="14" />
                                </button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('wallet.destroy', $w) }}" class="sm:col-span-2" data-konfirmasi="Hapus wallet {{ $w->nama }}?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="hs-btn bg-hsRed text-white px-3 py-2 text-xs w-full" title="Hapus" aria-label="Hapus wallet {{ $w->nama }}">
                                <x-ikon nama="trash" :size="14" />
                            </button>
                        </form>
                    </div>
                    <p class="text-xs text-slate-500">Saldo saat ini: <strong>{{ Format::rupiah($w->saldo_sekarang) }}</strong></p>
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('wallet.store') }}" class="hs-card p-5 space-y-3 h-fit">
            @csrf
            <h4 class="text-sm font-black">Tambah Wallet</h4>
            <div>
                <label for="wn" class="text-xs font-bold">Nama</label>
                <input id="wn" name="nama" value="{{ old('nama') }}" required maxlength="50" placeholder="BCA, GoPay, Tunai" class="w-full px-3 py-2 text-xs border">
            </div>
            <div>
                <label for="wt" class="text-xs font-bold">Tipe</label>
                <select id="wt" name="tipe" class="w-full px-3 py-2 text-xs border">
                    @foreach (Wallet::TIPE as $k => $label)
                        <option value="{{ $k }}" @selected(old('tipe') === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="ws" class="text-xs font-bold">Saldo awal (Rp)</label>
                <input id="ws" name="saldo_awal" type="text" inputmode="numeric" autocomplete="off" value="{{ old('saldo_awal', '0') }}"
                       class="w-full px-3 py-2 text-xs border" data-rupiah>
            </div>
            <button type="submit" class="hs-btn bg-hsOrange text-white w-full py-2.5 text-xs">Simpan Wallet</button>
        </form>
    </div>
@endsection
