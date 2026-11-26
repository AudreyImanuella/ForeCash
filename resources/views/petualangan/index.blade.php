{{-- Petualangan (port dari app/(app)/petualangan/page.tsx + KoleksiPet.tsx versi Next.js):
     peta perjalanan menuju uang saku, telur yang menetas setelah 7 hari baik, koleksi 12 teman hemat. --}}
@extends('layouts.app')
@use('App\Support\Format')
@use('App\Support\Pets')

@section('judul', 'Petualangan')
@section('judul_halaman', 'Petualangan')

@php $petSaya = array_keys($owned); @endphp

@section('isi')
    <div><p class="text-xs text-slate-500">Setiap titik adalah satu hari menuju uang saku berikutnya. Hari baik membuat telur menetas jadi teman baru.</p></div>

    {{-- ringkasan perjalanan --}}
    <section class="fc-journey-bar">
        <span class="fc-pill"><span class="fc-tile fc-tile-orange">🚶</span><span><b>Hari ke-{{ $hariKe ?? '-' }}</b> dari {{ count($j['hari']) }}</span></span>
        <span class="fc-pill"><span class="fc-tile fc-tile-yellow">🪙</span><span><b>{{ $jumlahBaik }}</b> hari baik</span></span>
        <span class="fc-pill"><span class="fc-tile fc-tile-red">⚠️</span><span><b>{{ $jumlahBoros }}</b> hari boros</span></span>
        <span class="fc-pill"><span class="fc-tile fc-tile-purple">🐾</span><span><b>{{ count($petSaya) }}/{{ count(Pets::PETS) }}</b> teman</span></span>
    </section>

    {{-- peta: versi lebar (7 titik per baris) dan versi HP (4 titik per baris) --}}
    <section class="fc-map" data-tema="{{ $f['status'] }}">
        <div class="fc-map-wide">
            <x-peta :hari="$j['hari']" :cols="7" :lebar="1000" :tinggi-baris="150" :tema="$f['status']" :avatar="$user->avatar" :pets="$petSaya" :dipakai="$dipakai" />
        </div>
        <div class="fc-map-narrow">
            <x-peta :hari="$j['hari']" :cols="4" :lebar="420" :tinggi-baris="140" :tema="$f['status']" :avatar="$user->avatar" :pets="$petSaya" :dipakai="$dipakai" />
        </div>
        @if (! $j['adaJadwal'])
            <p class="fc-map-note">
                Belum ada jadwal pemasukan, jadi ujung peta belum ada garis finisnya. <a href="{{ route('prediksi') }}" class="fc-link">Tambah jadwal</a>
            </p>
        @endif
        <div class="fc-map-legend">
            <span><i class="fc-lg fc-lg-baik"></i> Hari baik</span>
            <span><i class="fc-lg fc-lg-boros"></i> Melewati batas</span>
            <span><i class="fc-lg fc-lg-kosong"></i> Tidak mencatat</span>
            <span><i class="fc-lg fc-lg-cukup"></i> Saldo cukup</span>
            <span><i class="fc-lg fc-lg-kurang"></i> Saldo habis</span>
        </div>
    </section>

    <div class="fc-pet-area">
        {{-- telur --}}
        @php $isi = min($progress, Pets::TELUR_TARGET); @endphp
        <section class="hs-card fc-egg-card">
            <div class="fc-egg {{ $progress >= Pets::TELUR_TARGET - 2 ? 'is-shaking' : '' }}"><x-telur :progress="$progress" :size="120" /></div>
            <h3>Telur sedang dierami</h3>
            <p class="text-sm text-slate-500">Menetas setelah {{ Pets::TELUR_TARGET }} hari baik: mencatat transaksi dan belanja tidak melewati batas harian.</p>
            <div class="fc-egg-bar"><span style="width: {{ round($isi / Pets::TELUR_TARGET * 100) }}%"></span></div>
            <p class="fc-egg-count">{{ $isi }}/{{ Pets::TELUR_TARGET }} hari baik</p>
            @if ((float) $user->batas_harian <= 0)
                <p class="text-xs text-slate-500">
                    Batas harian belum diatur, jadi setiap hari yang dicatat dihitung hari baik. <a href="{{ route('transaksi.index') }}" class="fc-link">Atur batas</a>
                </p>
            @endif
        </section>

        {{-- koleksi teman (port dari KoleksiPet.tsx) --}}
        <section class="hs-card p-5">
            <div class="fc-section-title">
                <h3 style="font-size: 20px">Koleksi teman</h3>
                <p>{{ count($owned) }} dari {{ count(Pets::PETS) }} sudah menetas</p>
            </div>
            <div class="fc-pet-grid">
                @foreach (Pets::PETS as $id => $p)
                    @if (isset($owned[$id]))
                        <button type="button" class="fc-pet-tile {{ $dipakai === $id ? 'is-on' : '' }}" data-buka-dialog="#pet-{{ $id }}" aria-label="Lihat {{ $p['nama'] }}">
                            <x-pet :id="$id" :size="64" />
                            <span>{{ $p['nama'] }}</span>
                            @if ($dipakai === $id)
                                <i class="fc-pet-check"><x-ikon nama="check" :size="12" :tebal="3" /></i>
                            @endif
                        </button>

                        <dialog id="pet-{{ $id }}" class="fc-dialog" @if ($baru === $id) data-langsung-buka @endif>
                            @if ($baru === $id)
                                <p class="fc-dialog-new">🎉 Telurmu menetas!</p>
                            @endif
                            <div class="fc-dialog-pet"><x-pet :id="$id" :size="140" /></div>
                            <p class="fc-dialog-tag">{{ $p['sifat'] }}</p>
                            <h3>{{ $p['nama'] }}</h3>
                            <p class="fc-dialog-story">{{ $p['cerita'] }}</p>
                            <p class="fc-dialog-date">Menetas {{ Format::tgl($owned[$id]) }}</p>
                            <div class="fc-dialog-actions">
                                <button type="button" class="hs-btn bg-slate-100 px-5 py-2.5" data-tutup-dialog>Tutup</button>
                                @if ($dipakai === $id)
                                    <span class="fc-chip" style="padding: 8px 14px">Sedang menemanimu</span>
                                @else
                                    <form method="POST" action="{{ route('petualangan.pakai') }}">
                                        @csrf
                                        <input type="hidden" name="pet" value="{{ $id }}">
                                        <button type="submit" class="hs-btn bg-hsOrange text-white px-5 py-2.5">Ajak jalan-jalan</button>
                                    </form>
                                @endif
                            </div>
                        </dialog>
                    @else
                        <div class="fc-pet-tile is-locked" title="Belum menetas">
                            <x-pet :id="$id" :size="64" class="fc-silhouette" />
                            <span>???</span>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    </div>
@endsection
