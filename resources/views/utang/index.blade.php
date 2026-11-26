{{-- Utang & Patungan (port dari app/(app)/utang/page.tsx + components versi Next.js). --}}
@extends('layouts.app')
@use('App\Support\Format')
@use('App\Http\Controllers\UtangController')

@section('judul', 'Utang & Patungan')
@section('judul_halaman', 'Utang & Patungan')

@php $hari = Format::hariIni(); @endphp

@section('isi')
    <div><p class="text-xs text-slate-500">Catat utang-piutang antarteman dan bagi tagihan bersama.</p></div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            {{-- teman menandai lunas: catat transaksinya ke wallet (port dari PelunasanTeman.tsx) --}}
            @if ($pelunasan->isNotEmpty())
                <section class="hs-card p-5 space-y-1" style="border-color: var(--sun)" aria-label="Pelunasan dari teman">
                    <h4 class="text-sm font-black"><x-ikon nama="bell" :size="15" class="inline -mt-0.5 text-hsOrange" /> Ditandai lunas oleh teman</h4>
                    @foreach ($pelunasan as $d)
                        @php $masuk = $d->arah === 'piutang'; @endphp
                        <div class="py-2 border-b last:border-0 space-y-2">
                            <p class="text-xs">
                                <b>{{ $d->nama_teman }}</b> menandai <b>{{ $d->keterangan }}</b> ({{ Format::rupiah($d->nominal) }}) sudah lunas.
                                {{ $masuk ? 'Uangnya sudah kamu terima?' : 'Sudah kamu bayar?' }}
                            </p>
                            <form method="POST" action="{{ route('utang.pelunasan', $d) }}" class="flex gap-2 flex-wrap">
                                @csrf
                                <select name="wallet_id" aria-label="{{ $masuk ? 'Masuk ke wallet' : 'Dibayar dari wallet' }}" class="flex-1 min-w-[120px] px-2 py-1.5 text-[11px] border">
                                    @foreach ($wallets as $w) <option value="{{ $w->id }}">{{ $w->nama }}</option> @endforeach
                                </select>
                                <button type="submit" class="hs-btn bg-hsGreen text-white px-3 py-1.5 text-[11px]">Catat {{ $masuk ? 'pemasukan' : 'pengeluaran' }}</button>
                                <button type="submit" name="abaikan" value="1" class="hs-btn bg-white text-hsDark px-3 py-1.5 text-[11px]" style="border: 2px solid var(--line)">Abaikan</button>
                            </form>
                        </div>
                    @endforeach
                </section>
            @endif

            {{-- kartu utang/piutang yang belum lunas (port dari KartuUtang.tsx) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @forelse ($belum as $d)
                    @php
                        $telat = $d->jatuh_tempo && $d->jatuh_tempo->toDateString() < $hari;
                        $piutang = $d->arah === 'piutang';
                    @endphp
                    <div class="hs-card p-5 space-y-3 {{ $telat ? 'border-red-300!' : '' }}">
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0">
                                <span class="text-[10px] font-black px-2 py-0.5 rounded-full {{ $piutang ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-700' }}">
                                    {{ $piutang ? 'PIUTANG · dia utang ke saya' : 'UTANG · saya utang ke dia' }}
                                </span>
                                <p class="font-black mt-2">{{ $d->nama_teman }}</p>
                                @if ($d->teman_id)
                                    <p class="text-[10px] font-bold text-hsBlue inline-flex items-center gap-1">
                                        <x-ikon nama="link" :size="11" /> {{ $piutang ? 'Tagihan terkirim ke akunnya' : 'Tagihan dari temanmu di ForeCash' }}
                                    </p>
                                @endif
                                <p class="text-xs text-slate-500">{{ $d->keterangan }}</p>
                            </div>
                            <p class="font-black shrink-0">{{ Format::rupiah($d->nominal) }}</p>
                        </div>
                        @if ($d->jatuh_tempo)
                            <p class="text-[11px] font-bold {{ $telat ? 'text-hsRed' : 'text-slate-500' }}">
                                Jatuh tempo {{ Format::tgl($d->jatuh_tempo) }}{{ $telat ? ' — terlewat' : '' }}
                            </p>
                        @endif
                        <div class="flex gap-2 items-center">
                            <form method="POST" action="{{ route('utang.lunas', $d) }}" class="flex gap-2 flex-1">
                                @csrf
                                <select name="wallet_id" aria-label="Wallet untuk pelunasan" title="Wallet untuk pelunasan" class="flex-1 min-w-0 px-2 py-1.5 text-[11px] border">
                                    @foreach ($wallets as $w) <option value="{{ $w->id }}">{{ $w->nama }}</option> @endforeach
                                </select>
                                <button type="submit" class="hs-btn bg-hsGreen text-white px-3 py-1.5 text-[11px]">Lunas</button>
                            </form>
                            <form method="POST" action="{{ route('utang.destroy', $d) }}" data-konfirmasi="Hapus catatan ini?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-8 h-8 rounded-full bg-red-50 text-hsRed flex items-center justify-center" title="Hapus" aria-label="Hapus catatan">
                                    <x-ikon nama="trash" :size="13" />
                                </button>
                            </form>
                        </div>
                        @if ($piutang && ! $d->teman_id)
                            <a href="{{ UtangController::linkPengingatWA($d->nama_teman, $d->keterangan, (float) $d->nominal) }}" target="_blank" rel="noopener"
                               class="text-[11px] font-bold text-hsGreen inline-flex items-center gap-1">
                                <x-ikon nama="message-circle" :size="13" /> Kirim pengingat via WhatsApp
                            </a>
                        @endif
                    </div>
                @empty
                    <p class="hs-card p-5 text-xs text-slate-500 sm:col-span-2">Tidak ada utang atau piutang yang belum lunas.</p>
                @endforelse
            </div>

            @if ($riwayat->isNotEmpty())
                <div class="hs-card p-5">
                    <h4 class="text-sm font-black mb-2">Riwayat Lunas</h4>
                    @foreach ($riwayat as $d)
                        <p class="text-xs py-1 border-b last:border-0">
                            <span class="font-bold">{{ $d->nama_teman }}</span> · {{ $d->keterangan }} · {{ Format::rupiah($d->nominal) }} <span class="text-hsGreen font-bold">✓</span>
                        </p>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="space-y-6">
            {{-- Teman ForeCash (port dari TemanForeCash.tsx) --}}
            <section class="hs-card p-5 space-y-3" aria-label="Teman ForeCash">
                <h4 class="text-sm font-black"><x-ikon nama="user-plus" :size="15" class="inline -mt-0.5 text-hsBlue" /> Teman ForeCash</h4>
                <p class="text-[11px] text-slate-500">Berteman di ForeCash supaya tagihan split bill langsung masuk ke akun temanmu, dan status lunasnya otomatis sama di kedua akun.</p>
                <div class="flex items-center gap-2 rounded-2xl bg-sky-50 px-3 py-2">
                    <div class="flex-1">
                        <p class="text-[10px] font-bold text-slate-500">Kode temanmu</p>
                        <p class="font-black text-lg tracking-[.2em]">{{ $user->kode_teman ?? '-' }}</p>
                    </div>
                    @if ($user->kode_teman)
                        <button type="button" data-salin="{{ $user->kode_teman }}" class="hs-btn bg-white text-hsDark px-3 py-2 text-xs inline-flex items-center gap-1" style="border: 2px solid var(--line)">
                            <x-ikon nama="copy" :size="14" /> <span>Salin</span>
                        </button>
                    @endif
                </div>
                <form method="POST" action="{{ route('teman.store') }}" class="flex gap-2">
                    @csrf
                    <input name="kode" required maxlength="8" placeholder="Kode teman, mis. ANDI26" aria-label="Kode teman" autocomplete="off"
                           class="flex-1 min-w-0 px-3 py-2 text-xs border uppercase">
                    <button type="submit" class="hs-btn bg-hsBlue text-white px-4 py-2 text-xs">Tambah</button>
                </form>
                @if ($errors->teman->any())
                    <p class="text-xs font-bold text-hsRed">{{ $errors->teman->first() }}</p>
                @endif
                @if ($teman->isNotEmpty())
                    <ul class="space-y-1">
                        @foreach ($teman as $t)
                            <li class="flex items-center gap-2 py-1">
                                <span class="fc-chip-av" style="width: 34px; height: 34px"><x-avatar :id="$t->avatar" :size="32" /></span>
                                <span class="flex-1 text-sm font-bold truncate">{{ $t->name }}</span>
                                <form method="POST" action="{{ route('teman.destroy', $t) }}" data-konfirmasi="Putus pertemanan dengan {{ $t->name }}?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-8 h-8 rounded-full bg-red-50 text-hsRed inline-flex items-center justify-center"
                                            aria-label="Putus pertemanan dengan {{ $t->name }}" title="Putus pertemanan"><x-ikon nama="user-minus" :size="14" /></button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Split Bill (port dari SplitBillForm di FormUtang.tsx) --}}
            <form method="POST" action="{{ route('utang.split') }}" id="split" class="hs-card p-5 space-y-3" data-split>
                @csrf
                <h4 class="text-sm font-black"><x-ikon nama="calculator" :size="15" class="inline -mt-0.5 text-hsPurple" /> Split Bill</h4>
                @if ($errors->split->any())
                    <p class="text-xs font-bold text-hsRed">{{ $errors->split->first() }}</p>
                @endif
                <div>
                    <label for="sj" class="text-xs font-bold">Judul</label>
                    <input id="sj" name="judul" maxlength="150" value="{{ old('judul') }}" placeholder="Makan malam angkatan" class="w-full px-3 py-2 text-xs border">
                </div>
                <div>
                    <label for="st" class="text-xs font-bold">Total tagihan (Rp)</label>
                    <input id="st" name="total" required type="text" inputmode="numeric" autocomplete="off" value="{{ old('total') }}"
                           placeholder="Contoh: 150.000" class="w-full px-3 py-2 text-xs border" data-rupiah>
                </div>
                @if ($teman->isNotEmpty())
                    <fieldset class="space-y-1.5">
                        <legend class="text-xs font-bold">Teman ForeCash (tagihan langsung masuk ke akunnya)</legend>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($teman as $t)
                                <label class="fc-chip-teman">
                                    <input type="checkbox" name="teman_fc[]" value="{{ $t->id }}" class="sr-only" @checked(in_array($t->id, old('teman_fc', [])))>
                                    <span class="fc-chip-av"><x-avatar :id="$t->avatar" :size="26" /></span>
                                    {{ \Illuminate\Support\Str::before($t->name, ' ') ?: $t->name }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif
                <div>
                    <label for="sn" class="text-xs font-bold">{{ $teman->isNotEmpty() ? 'Teman lain (pisahkan dengan koma)' : 'Nama teman (pisahkan dengan koma)' }}</label>
                    <input id="sn" name="teman" value="{{ old('teman') }}" placeholder="Budi, Siti, Bima" class="w-full px-3 py-2 text-xs border">
                </div>
                <label class="flex items-center gap-2 text-xs font-bold">
                    <input type="checkbox" name="termasuk_saya" value="1" @checked(old('termasuk_saya', '1'))> Saya ikut menanggung bagian
                </label>
                <p class="text-xs font-bold text-hsPurple" data-split-hasil hidden></p>
                <div>
                    <label for="sd" class="text-xs font-bold">Jatuh tempo (opsional)</label>
                    <input id="sd" type="date" name="jatuh_tempo" value="{{ old('jatuh_tempo') }}" class="w-full px-3 py-2 text-xs border">
                </div>
                <button type="submit" class="hs-btn bg-hsPurple text-white w-full py-2.5 text-xs">Bagi &amp; Catat Piutang</button>
            </form>

            {{-- Catat Utang / Piutang (port dari CatatUtangForm di FormUtang.tsx) --}}
            <form method="POST" action="{{ route('utang.store') }}" class="hs-card p-5 space-y-3" data-catat-utang>
                @csrf
                <h4 class="text-sm font-black"><x-ikon nama="plus" :size="15" class="inline -mt-0.5 text-hsGreen" /> Catat Utang / Piutang</h4>
                @if ($errors->catat->any())
                    <p class="text-xs font-bold text-hsRed">{{ $errors->catat->first() }}</p>
                @endif
                @if ($teman->isNotEmpty())
                    <div>
                        <label for="dfc" class="text-xs font-bold">Teman ForeCash (opsional)</label>
                        <select id="dfc" name="teman_fc" class="w-full px-3 py-2 text-xs border" data-pilih-teman>
                            <option value="">Bukan pengguna ForeCash</option>
                            @foreach ($teman as $t)
                                <option value="{{ $t->id }}" @selected((string) old('teman_fc') === (string) $t->id)>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div data-nama-teman>
                    <label for="dn" class="text-xs font-bold">Nama teman</label>
                    <input id="dn" name="nama_teman" maxlength="100" value="{{ old('nama_teman') }}" class="w-full px-3 py-2 text-xs border">
                </div>
                <div>
                    <label for="dk" class="text-xs font-bold">Keterangan</label>
                    <input id="dk" name="keterangan" required maxlength="150" value="{{ old('keterangan') }}" placeholder="Pinjam untuk ongkos" class="w-full px-3 py-2 text-xs border">
                </div>
                <div>
                    <label for="dnom" class="text-xs font-bold">Nominal (Rp)</label>
                    <input id="dnom" name="nominal" required type="text" inputmode="numeric" autocomplete="off" value="{{ old('nominal') }}"
                           placeholder="Contoh: 20.000" class="w-full px-3 py-2 text-xs border" data-rupiah>
                </div>
                <div>
                    <label for="da" class="text-xs font-bold">Posisi</label>
                    <select id="da" name="arah" class="w-full px-3 py-2 text-xs border">
                        <option value="piutang" @selected(old('arah') === 'piutang')>Dia utang ke saya (piutang)</option>
                        <option value="utang" @selected(old('arah') === 'utang') data-bukan-teman>Saya utang ke dia (utang)</option>
                    </select>
                    <p class="text-[10px] text-slate-500 mt-1" data-info-teman hidden>Tagihan akan muncul di akun temanmu.</p>
                </div>
                <div>
                    <label for="dt" class="text-xs font-bold">Jatuh tempo (opsional)</label>
                    <input id="dt" type="date" name="jatuh_tempo" value="{{ old('jatuh_tempo') }}" class="w-full px-3 py-2 text-xs border">
                </div>
                <button type="submit" class="hs-btn bg-hsGreen text-white w-full py-2.5 text-xs">Simpan</button>
            </form>
        </div>
    </div>
@endsection
