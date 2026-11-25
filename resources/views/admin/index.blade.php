{{-- Halaman admin (baru di versi Laravel, memakai gaya kartu yang sama dengan halaman lain). --}}
@extends('layouts.app')
@use('App\Support\Format')

@section('judul', 'Admin')
@section('judul_halaman', 'Kelola Pengguna')

@section('isi')
    <section class="fc-stats" aria-label="Ringkasan">
        @foreach ([['mahasiswa', 'Mahasiswa', 'orange', 'users'], ['orangtua', 'Orang tua', 'purple', 'eye'], ['admin', 'Admin', 'blue', 'shield']] as [$peran, $label, $warna, $ikon])
            <div class="hs-card fc-stat">
                <span class="fc-tile fc-tile-{{ $warna }}"><x-ikon :nama="$ikon" :size="20" /></span>
                <div class="min-w-0"><p class="fc-stat-label">{{ $label }}</p><p class="fc-stat-value">{{ $jumlahPeran[$peran] ?? 0 }}</p></div>
            </div>
        @endforeach
        <div class="hs-card fc-stat">
            <span class="fc-tile fc-tile-green"><x-ikon nama="receipt" :size="20" /></span>
            <div class="min-w-0"><p class="fc-stat-label">Total transaksi</p><p class="fc-stat-value">{{ number_format($jumlahTransaksi, 0, ',', '.') }}</p></div>
        </div>
    </section>

    <section class="hs-card p-5" aria-label="Daftar pengguna">
        <div class="fc-section-title" style="margin-bottom: 4px"><h3 style="font-size: 19px">Pengguna</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] text-left text-sm">
                <thead class="text-xs text-slate-500">
                    <tr><th class="py-2 pr-4">Nama</th><th class="py-2 pr-4">Email</th><th class="py-2 pr-4">Peran</th><th class="py-2">Terdaftar</th></tr>
                </thead>
                <tbody>
                    @foreach ($users as $u)
                        <tr class="border-t">
                            <td class="py-2.5 pr-4">
                                <span class="flex items-center gap-2 font-bold">
                                    <span class="fc-chip-av"><x-avatar :id="$u->avatar" :size="28" /></span> {{ $u->name }}
                                </span>
                            </td>
                            <td class="py-2.5 pr-4 text-slate-500">{{ $u->email }}</td>
                            <td class="py-2.5 pr-4"><span class="fc-chip">{{ ['mahasiswa' => 'Mahasiswa', 'orangtua' => 'Orang tua', 'admin' => 'Admin'][$u->role] ?? $u->role }}</span></td>
                            <td class="py-2.5 text-slate-500">{{ Format::tgl($u->created_at) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $users->links() }}</div>
    </section>
@endsection
