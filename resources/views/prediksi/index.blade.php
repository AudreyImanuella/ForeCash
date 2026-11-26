{{-- Prediksi & jadwal pemasukan (port dari app/(app)/prediksi/page.tsx + components versi Next.js).
     Grafik proyeksi digambar sebagai SVG oleh JavaScript (resources/js/app.js, bagian "Prediksi"),
     supaya simulasi burn rate bisa langsung mengubah grafiknya. --}}
@extends('layouts.app')
@use('App\Support\Format')

@section('judul', 'Prediksi')
@section('judul_halaman', 'Prediksi & Notifikasi')

@section('isi')
    <div>
        <p class="text-xs text-slate-500">Dihitung otomatis dari transaksi: burn rate = total pengeluaran {{ $f['hari'] }} hari terakhir ÷ {{ $f['hari'] }}.</p>
    </div>

    <x-warning-banner :f="$f" :avatar="$user->avatar" :pet="$petDipakai" />

    {{-- Hasil perhitungan + simulasi + grafik (port dari ProyeksiPanel.tsx) --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" data-proyeksi
         data-hari="{{ $hari }}" data-saldo="{{ $f['saldo'] }}" data-burn="{{ $f['burn'] }}"
         data-rencana-dulu="{{ $f['rencanaDulu'] }}" data-sisa-hari="{{ $f['sisaHari'] ?? '' }}"
         data-rencana='@json($rencana)'>
        <div class="hs-card p-6 space-y-4">
            <h4 class="text-sm font-black border-b pb-2">Hasil Perhitungan</h4>
            <div class="text-sm space-y-2">
                <p class="flex justify-between gap-2"><span class="text-slate-500">Total saldo</span><b>{{ Format::rupiah($f['saldo']) }}</b></p>
                @if ($f['rencanaDulu'] > 0)
                    <p class="flex justify-between gap-2"><span class="text-slate-500">Rencana sebelum pemasukan</span>
                        <a href="{{ route('rencana.index') }}" class="font-bold text-hsOrange">−{{ Format::rupiah($f['rencanaDulu']) }}</a></p>
                @endif
                <p class="flex justify-between gap-2"><span class="text-slate-500">Burn rate</span><b>{{ Format::rupiah($f['burn']) }}/hari</b></p>
                <p class="flex justify-between gap-2"><span class="text-slate-500">Runway</span><b>{{ $f['runway'] !== null ? $f['runway'] . ' hari' : '-' }}</b></p>
                <p class="flex justify-between gap-2"><span class="text-slate-500">Perkiraan saldo habis</span><b>{{ $f['habis'] ? Format::tgl($f['habis']) : '-' }}</b></p>
                <p class="flex justify-between gap-2"><span class="text-slate-500">Pemasukan berikutnya</span><b>{{ $f['next'] ? Format::tgl($f['next']['tanggal_perkiraan']) : '-' }}</b></p>
            </div>
            <div class="pt-3 border-t space-y-2">
                <label for="simBurn" class="text-xs font-bold">Simulasi: jika pengeluaran per hari menjadi</label>
                <input id="simBurn" type="text" inputmode="numeric" autocomplete="off" value="{{ Format::isianNominal($f['burn']) }}"
                       class="w-full px-3 py-2 text-sm border" data-rupiah data-sim-burn>
                <p class="text-xs font-bold text-hsBlue" aria-live="polite" data-sim-hasil></p>
            </div>
        </div>

        <div class="lg:col-span-2 hs-card p-6 space-y-3">
            <h4 class="text-sm font-black">Proyeksi Saldo 30 Hari ke Depan</h4>
            <div class="h-64" role="img" data-grafik
                 aria-label="Grafik proyeksi saldo: dari {{ Format::rupiah($f['saldo']) }} hari ini, berkurang {{ Format::rupiah($f['burn']) }} per hari."></div>
            @if ($f['sisaHari'] !== null && $f['sisaHari'] <= 30)
                <p class="text-[11px] text-slate-500">Garis kuning menandai tanggal pemasukan berikutnya.</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Jadwal pemasukan (port dari Jadwal.tsx) --}}
        <div class="lg:col-span-2 hs-card p-6 space-y-3">
            <h4 class="text-sm font-black">Jadwal Pemasukan</h4>
            @forelse ($jadwal as $s)
                @php $telat = $s->tanggal_perkiraan->toDateString() < $hari; @endphp
                <div class="border-b last:border-0 py-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex-1 min-w-[160px]">
                            <p class="text-sm font-bold">{{ $s->sumber }} · {{ Format::rupiah($s->nominal) }}</p>
                            <p class="text-xs {{ $telat ? 'text-hsRed font-bold' : 'text-slate-500' }}">{{ Format::tgl($s->tanggal_perkiraan) }}{{ $telat ? ' — TERLAMBAT' : '' }}</p>
                        </div>
                        <form method="POST" action="{{ route('prediksi.terima', $s) }}" class="flex items-center gap-2">
                            @csrf
                            <select name="wallet_id" aria-label="Masuk ke wallet" title="Masuk ke wallet" class="px-2 py-1.5 text-[11px] border">
                                @foreach ($wallets as $w) <option value="{{ $w->id }}">{{ $w->nama }}</option> @endforeach
                            </select>
                            <button type="submit" class="hs-btn bg-hsGreen text-white px-3 py-1.5 text-[11px]">Sudah diterima</button>
                        </form>
                        <form method="POST" action="{{ route('prediksi.hapus', $s) }}" data-konfirmasi="Hapus jadwal ini?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-8 h-8 rounded-full bg-red-50 text-hsRed flex items-center justify-center" title="Hapus" aria-label="Hapus jadwal">
                                <x-ikon nama="trash" :size="13" />
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <x-kosong judul="Belum ada jadwal pemasukan">Tambahkan uang saku, honor, atau beasiswa agar peringatan bisa dihitung.</x-kosong>
            @endforelse
        </div>

        <form method="POST" action="{{ route('prediksi.store') }}" class="hs-card p-6 space-y-3">
            @csrf
            <h4 class="text-sm font-black">Tambah Jadwal Pemasukan</h4>
            @if ($errors->jadwal->any())
                <p class="text-xs font-bold text-hsRed">{{ $errors->jadwal->first() }}</p>
            @endif
            <div>
                <label for="js" class="text-xs font-bold">Sumber</label>
                <input id="js" name="sumber" required maxlength="50" value="{{ old('sumber') }}" placeholder="Uang saku dari orang tua" class="w-full px-3 py-2 text-xs border">
            </div>
            <div>
                <label for="jn" class="text-xs font-bold">Perkiraan nominal (Rp)</label>
                <input id="jn" name="nominal" required type="text" inputmode="numeric" autocomplete="off" value="{{ old('nominal') }}"
                       placeholder="Contoh: 1.500.000" class="w-full px-3 py-2 text-xs border" data-rupiah>
            </div>
            <div>
                <label for="jt" class="text-xs font-bold">Tanggal perkiraan</label>
                <input id="jt" type="date" name="tanggal" required value="{{ old('tanggal') }}" class="w-full px-3 py-2 text-xs border">
            </div>
            <button type="submit" class="hs-btn bg-hsOrange text-white w-full py-2.5 text-xs">Simpan Jadwal</button>
        </form>
    </div>
@endsection
