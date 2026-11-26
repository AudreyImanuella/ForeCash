{{-- Rencana: anggaran per kategori, target tabungan, pengeluaran yang direncanakan
     (port dari app/(app)/rencana/page.tsx + components versi Next.js). --}}
@extends('layouts.app')
@use('App\Support\Format')

@section('judul', 'Rencana')
@section('judul_halaman', 'Rencana')

@php
    $WARNA = ['aman' => 'bg-hsGreen', 'hampir' => 'bg-hsYellow', 'lewat' => 'bg-hsRed'];
    $KET = ['aman' => 'text-slate-500', 'hampir' => 'text-amber-700', 'lewat' => 'text-hsRed'];
    $BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $errorBag = collect(['rencana', 'target'])->map(fn ($b) => $errors->$b->first())->filter()->first();
@endphp

@section('isi')
    <div><p class="text-xs text-slate-500">Atur anggaran, tabungan, dan pengeluaran besar sebelum terjadi. Rencana pengeluaran otomatis ikut dihitung di Prediksi.</p></div>

    @if ($errorBag)
        <p class="fc-flash is-error" role="alert">{{ $errorBag }}</p>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <div class="lg:col-span-2 space-y-6">
            {{-- Anggaran per kategori (port dari Anggaran.tsx) --}}
            <section class="hs-card p-6 space-y-2" aria-label="Anggaran per kategori">
                <div class="flex justify-between items-baseline gap-2 flex-wrap">
                    <h4 class="text-sm font-black">Anggaran {{ $namaBulan }}</h4>
                    @if (count($baris))
                        <span class="text-xs text-slate-500">{{ Format::rupiah($total['terpakai']) }} dari {{ Format::rupiah($total['batas']) }}</span>
                    @endif
                </div>
                @if (! count($baris))
                    <x-kosong judul="Belum ada anggaran">Tentukan batas belanja per kategori supaya tahu kapan harus mengerem.</x-kosong>
                @endif
                @foreach ($baris as $a)
                    @php
                        $ket = $a['status'] === 'lewat' ? 'Lewat ' . Format::rupiah(-$a['sisa'])
                            : ($a['status'] === 'hampir' ? 'Hampir habis · sisa ' . Format::rupiah($a['sisa']) : 'Sisa ' . Format::rupiah($a['sisa']));
                    @endphp
                    <div class="py-3 border-b last:border-0 space-y-1.5">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <p class="text-sm font-bold">{{ $a['nama'] }}</p>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-500">{{ Format::rupiah($a['terpakai']) }} / {{ Format::rupiah($a['batas']) }}</span>
                                <button type="button" data-buka="#ang{{ $a['category_id'] }}" class="w-8 h-8 rounded-full bg-amber-50 text-hsOrange inline-flex items-center justify-center"
                                        aria-label="Ubah anggaran {{ $a['nama'] }}" title="Ubah"><x-ikon nama="pencil" :size="14" /></button>
                            </div>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2.5" role="progressbar" aria-valuenow="{{ $a['persen'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Anggaran {{ $a['nama'] }} terpakai">
                            <div class="{{ $WARNA[$a['status']] }} h-2.5 rounded-full" style="width: {{ $a['persen'] }}%"></div>
                        </div>
                        <p class="text-[11px] font-bold {{ $KET[$a['status']] }}">{{ $ket }}</p>
                        <div id="ang{{ $a['category_id'] }}" hidden>
                            <form method="POST" action="{{ route('rencana.anggaran') }}" class="flex gap-2 items-center pt-1">
                                @csrf
                                <input type="hidden" name="category_id" value="{{ $a['category_id'] }}">
                                <input name="batas" type="text" inputmode="numeric" autocomplete="off" value="{{ Format::isianNominal($a['batas']) }}"
                                       aria-label="Batas baru {{ $a['nama'] }}" class="flex-1 px-3 py-2 text-xs border" data-rupiah>
                                <button type="submit" class="hs-btn bg-slate-800 text-white px-3 py-2 text-xs">Simpan</button>
                            </form>
                            <p class="text-[10px] text-slate-500">Isi 0 untuk menghapus anggaran kategori ini.</p>
                        </div>
                    </div>
                @endforeach

                @if ($kategoriKosong->isNotEmpty())
                    <form method="POST" action="{{ route('rencana.anggaran') }}" class="pt-3 space-y-2">
                        @csrf
                        <p class="text-xs font-bold">Tambah anggaran</p>
                        <div class="flex gap-2 flex-wrap">
                            <select name="category_id" aria-label="Kategori" class="flex-1 min-w-[140px] px-3 py-2 text-xs border">
                                @foreach ($kategoriKosong as $k) <option value="{{ $k->id }}">{{ $k->nama }}</option> @endforeach
                            </select>
                            <input name="batas" required type="text" inputmode="numeric" autocomplete="off" placeholder="Contoh: 500.000"
                                   aria-label="Batas per bulan" class="flex-1 min-w-[140px] px-3 py-2 text-xs border" data-rupiah>
                            <button type="submit" class="hs-btn bg-hsOrange text-white px-4 py-2 text-xs">Tambah</button>
                        </div>
                    </form>
                @endif
            </section>

            {{-- Pengeluaran yang direncanakan (port dari RencanaBayar.tsx) --}}
            <section class="hs-card p-6 space-y-2" aria-label="Pengeluaran yang direncanakan">
                <h4 class="text-sm font-black">Pengeluaran yang Direncanakan</h4>
                @if ($rencana->isEmpty())
                    <p class="text-xs text-slate-500">Bayar kos, UKT, kado teman... catat di sini supaya Prediksi tidak kaget.</p>
                @endif
                @foreach ($rencana as $r)
                    @php $telat = $r->tanggal->toDateString() < $hari; @endphp
                    <div class="py-3 border-b last:border-0 space-y-2">
                        <div class="flex items-center gap-3">
                            <div class="w-14 h-14 rounded-2xl flex flex-col items-center justify-center shrink-0 {{ $telat ? 'bg-red-100 text-hsRed' : 'bg-amber-100' }}">
                                <b class="text-lg leading-none">{{ $r->tanggal->day }}</b><span class="text-[10px] font-bold">{{ $BULAN[$r->tanggal->month - 1] }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold truncate">{{ $r->nama }}</p>
                                <p class="text-[11px] {{ $telat ? 'text-hsRed font-bold' : 'text-slate-500' }}">
                                    {{ $telat ? 'Lewat sejak ' . Format::tgl($r->tanggal) : ($r->category?->nama ?? 'Tanpa kategori') }}
                                    @if ($r->berulang)<span class="inline-flex items-center gap-0.5 ml-1"><x-ikon nama="repeat" :size="11" /> tiap bulan</span>@endif
                                </p>
                            </div>
                            <p class="font-black text-sm shrink-0">{{ Format::rupiah($r->nominal) }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('rencana.bayar', $r) }}" class="flex gap-2 flex-1">
                                @csrf
                                <select name="wallet_id" aria-label="Bayar dari wallet" title="Bayar dari wallet" class="flex-1 min-w-0 px-2 py-1.5 text-[11px] border">
                                    @foreach ($wallets as $w) <option value="{{ $w->id }}">{{ $w->nama }}</option> @endforeach
                                </select>
                                <button type="submit" class="hs-btn bg-hsGreen text-white px-3 py-1.5 text-[11px]">Sudah dibayar</button>
                            </form>
                            <form method="POST" action="{{ route('rencana.hapus', $r) }}" data-konfirmasi="Hapus rencana ini?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-8 h-8 rounded-full bg-red-50 text-hsRed flex items-center justify-center" title="Hapus" aria-label="Hapus rencana">
                                    <x-ikon nama="trash" :size="13" />
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
                @if ($f['rencanaDulu'] > 0)
                    <p class="text-[11px] font-bold rounded-2xl bg-emerald-50 text-emerald-800 px-3 py-2">
                        {{ Format::rupiah($f['rencanaDulu']) }} jatuh sebelum
                        {{ $f['next'] ? 'pemasukan berikutnya (' . Format::tgl($f['next']['tanggal_perkiraan']) . ')' : '30 hari ke depan' }}
                        dan sudah dihitung di Prediksi.
                    </p>
                @endif

                <form method="POST" action="{{ route('rencana.store') }}" class="pt-3 border-t space-y-2">
                    @csrf
                    <p class="text-xs font-bold">Tambah rencana</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label for="rn" class="text-xs font-bold">Nama</label>
                            <input id="rn" name="nama" required maxlength="100" value="{{ old('nama') }}" placeholder="Bayar kos Desember" class="w-full px-3 py-2 text-xs border">
                        </div>
                        <div>
                            <label for="rnom" class="text-xs font-bold">Nominal</label>
                            <input id="rnom" name="nominal" required type="text" inputmode="numeric" autocomplete="off" value="{{ old('nominal') }}"
                                   placeholder="Contoh: 1.200.000" class="w-full px-3 py-2 text-xs border" data-rupiah>
                        </div>
                        <div>
                            <label for="rt" class="text-xs font-bold">Tanggal</label>
                            <input id="rt" type="date" name="tanggal" required value="{{ old('tanggal') }}" class="w-full px-3 py-2 text-xs border">
                        </div>
                        <div>
                            <label for="rk" class="text-xs font-bold">Kategori</label>
                            <select id="rk" name="category_id" class="w-full px-3 py-2 text-xs border">
                                <option value="">Tanpa kategori</option>
                                @foreach ($kategori as $k) <option value="{{ $k->id }}" @selected((string) old('category_id') === (string) $k->id)>{{ $k->nama }}</option> @endforeach
                            </select>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-xs font-bold"><input type="checkbox" name="berulang" value="1" @checked(old('berulang'))> Berulang tiap bulan</label>
                    <button type="submit" class="hs-btn bg-hsOrange text-white w-full py-2.5 text-xs">Simpan Rencana</button>
                </form>
            </section>
        </div>

        {{-- Target tabungan (port dari Target.tsx) --}}
        <section class="hs-card p-6 space-y-3" aria-label="Target tabungan">
            <h4 class="text-sm font-black">Target Tabungan</h4>
            @if ($targets->isEmpty())
                <p class="text-xs text-slate-500">Mau beli apa? Laptop, tiket konser, dana darurat... buat targetnya di bawah.</p>
            @endif
            @foreach ($targets as $t)
                @php $i = $t->info; @endphp
                <div class="rounded-3xl bg-amber-50 p-4 space-y-2">
                    <div class="flex justify-between items-start gap-2">
                        <div class="min-w-0">
                            <p class="font-black truncate">{{ $t->nama }}</p>
                            <p class="text-xs text-slate-500">
                                {{ Format::rupiah($t->terkumpul) }} dari {{ Format::rupiah($t->target) }}@if ($t->tenggat) · tenggat {{ Format::tgl($t->tenggat) }}@endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <b class="text-sm">{{ $i['persen'] }}%</b>
                            <form method="POST" action="{{ route('rencana.target.hapus', $t) }}" data-konfirmasi="Hapus target &quot;{{ $t->nama }}&quot;?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-8 h-8 rounded-full bg-red-50 text-hsRed flex items-center justify-center" title="Hapus" aria-label="Hapus target {{ $t->nama }}">
                                    <x-ikon nama="trash" :size="13" />
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="w-full bg-white rounded-full h-3" role="progressbar" aria-valuenow="{{ $i['persen'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Kemajuan {{ $t->nama }}">
                        <div class="{{ $i['tercapai'] ? 'bg-hsGreen' : 'bg-hsOrange' }} h-3 rounded-full" style="width: {{ $i['persen'] }}%"></div>
                    </div>
                    @if ($i['tercapai'])
                        <p class="text-xs font-bold text-hsGreen">Tercapai!</p>
                    @else
                        <p class="text-[11px] font-bold">
                            @if ($i['perMinggu'])
                                Sisihkan {{ Format::rupiah($i['perMinggu']) }}/minggu supaya tercapai tepat waktu.
                            @elseif ($i['telat'])
                                Tenggat lewat, kurang {{ Format::rupiah($i['kurang']) }}.
                            @else
                                Kurang {{ Format::rupiah($i['kurang']) }} lagi.
                            @endif
                        </p>
                        <form method="POST" action="{{ route('rencana.setor', $t) }}" class="flex gap-2">
                            @csrf
                            <input name="nominal" required type="text" inputmode="numeric" autocomplete="off" placeholder="Contoh: 50.000"
                                   aria-label="Setoran untuk {{ $t->nama }}" class="flex-1 min-w-0 px-3 py-2 text-xs border" data-rupiah>
                            <button type="submit" class="hs-btn bg-hsOrange text-white px-3 py-2 text-xs">Setor</button>
                        </form>
                    @endif
                </div>
            @endforeach
            <p class="text-[10px] text-slate-500">Uang tabungan tetap tersimpan di wallet-mu. Kalau mau dipisah, buat wallet "Tabungan".</p>

            <form method="POST" action="{{ route('rencana.target') }}" class="pt-3 border-t space-y-2">
                @csrf
                <p class="text-xs font-bold">Target baru</p>
                <div>
                    <label for="tn" class="text-xs font-bold">Nama</label>
                    <input id="tn" name="nama" required maxlength="80" placeholder="Laptop baru" class="w-full px-3 py-2 text-xs border">
                </div>
                <div>
                    <label for="tt" class="text-xs font-bold">Jumlah target</label>
                    <input id="tt" name="target" required type="text" inputmode="numeric" autocomplete="off" placeholder="Contoh: 8.000.000"
                           class="w-full px-3 py-2 text-xs border" data-rupiah>
                </div>
                <div>
                    <label for="tg" class="text-xs font-bold">Tenggat (opsional)</label>
                    <input id="tg" type="date" name="tenggat" class="w-full px-3 py-2 text-xs border">
                </div>
                <button type="submit" class="hs-btn bg-hsOrange text-white w-full py-2.5 text-xs">Buat Target</button>
            </form>
        </section>
    </div>
@endsection
