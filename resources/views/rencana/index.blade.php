{{-- Rencana — desain baru (Nov 2026): ringkasan berwarna, anggaran per kategori dengan label status,
     pengeluaran yang direncanakan (kotak tanggal), target tabungan (kartu Banana + tupai), form rencana. --}}
@extends('layouts.app')
@use('App\Support\Format')

@section('judul', 'Rencana')
@section('judul_halaman', 'Rencana')

@php
    $BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    // status anggaran: [warna label, warna bar]
    $ST = ['aman' => ['#E3F2CF', '#98C54E'], 'hampir' => ['#FFF2A0', '#FFA934'], 'lewat' => ['#FDDDEB', '#F478B0']];
    $IKON_BG = ['#FFF2A0', '#C7ECF1', '#E3F2CF', '#FDDDEB', '#FFE3B8'];
    $errorBag = collect(['rencana', 'target'])->map(fn ($b) => $errors->$b->first())->filter()->first();

    $persenTotal = $total['batas'] > 0 ? (int) round($total['terpakai'] / $total['batas'] * 100) : null;
    $sebulan = date('Y-m-d', strtotime($hari . ' +30 day'));
    $rencana30 = $rencana->filter(fn ($r) => $r->tanggal->toDateString() <= $sebulan);
    $tabungan = (float) $targets->sum('terkumpul');
    $targetAktif = $targets->filter(fn ($t) => ! $t->info['tercapai'])->count();
@endphp

@section('isi')
<div class="fb fx rx">
    <p class="fx-sub">Atur anggaran, tabungan, dan pengeluaran besar sebelum terjadi. Rencana pengeluaran otomatis ikut dihitung di Prediksi.</p>

    @if ($errorBag)
        <p class="fc-flash is-error" role="alert">{{ $errorBag }}</p>
    @endif

    {{-- Ringkasan berwarna --}}
    <section class="fx-sum" aria-label="Ringkasan rencana">
        <div class="fx-sum-card" style="--c: #FFF2A0">
            <span class="fx-sum-ic"><x-ikon nama="chart-pie" :size="20" /></span>
            <p class="fx-sum-label">Anggaran terpakai</p>
            <p class="fx-sum-value">{{ $persenTotal !== null ? $persenTotal . '%' : '-' }}</p>
            <p class="fx-sum-note">{{ $persenTotal !== null ? Format::rupiah($total['terpakai']) . ' dari ' . Format::rupiah($total['batas']) : 'belum ada anggaran' }}</p>
        </div>
        <div class="fx-sum-card" style="--c: #C7ECF1">
            <span class="fx-sum-ic"><x-ikon nama="clipboard" :size="20" /></span>
            <p class="fx-sum-label">Rencana 30 hari</p>
            <p class="fx-sum-value">{{ Format::rupiah($rencana30->sum('nominal')) }}</p>
            <p class="fx-sum-note">{{ $rencana30->count() }} pengeluaran, sudah masuk prediksi</p>
        </div>
        <div class="fx-sum-card" style="--c: #E3F2CF">
            <span class="fx-sum-ic"><x-ikon nama="piggy-bank" :size="20" /></span>
            <p class="fx-sum-label">Total tabungan</p>
            <p class="fx-sum-value fx-in">{{ Format::rupiah($tabungan) }}</p>
            <p class="fx-sum-note">{{ $targetAktif }} target aktif</p>
        </div>
    </section>

    <div class="fb-grid">
        {{-- ===== Kolom kiri ===== --}}
        <div class="fb-main">
            {{-- Anggaran per kategori --}}
            <section class="fb-card" aria-label="Anggaran per kategori">
                <div class="fb-head">
                    <h3>Anggaran {{ $namaBulan }}</h3>
                    <span class="fx-meta"><b>reset tiap tanggal 1</b></span>
                </div>
                <div class="rx-list">
                    @forelse ($baris as $i => $a)
                        @php
                            $ket = $a['status'] === 'lewat' ? 'Lewat ' . Format::rupiah(-$a['sisa'])
                                : ($a['status'] === 'hampir' ? 'Hampir habis · sisa ' . Format::rupiah($a['sisa']) : 'Sisa ' . Format::rupiah($a['sisa']));
                            [$chip, $bar] = $ST[$a['status']];
                        @endphp
                        <div class="rx-ang">
                            <div class="rx-ang-atas">
                                <span class="rx-ic" style="background: {{ $IKON_BG[$i % 5] }}"><x-ikon :nama="Format::kategoriIkon($a['nama'])" :size="20" /></span>
                                <div class="rx-info">
                                    <p class="rx-nama">{{ $a['nama'] }}</p>
                                    <p class="fx-meta">{{ Format::rupiah($a['terpakai']) }} dari {{ Format::rupiah($a['batas']) }}</p>
                                </div>
                                <span class="rx-chip" style="background: {{ $chip }}">{{ $ket }}</span>
                                <button type="button" data-buka="#ang{{ $a['category_id'] }}" class="fx-act" aria-label="Ubah anggaran {{ $a['nama'] }}" title="Ubah"><x-ikon nama="pencil" :size="15" /></button>
                            </div>
                            <div class="rx-bar" role="progressbar" aria-valuenow="{{ $a['persen'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Anggaran {{ $a['nama'] }} terpakai">
                                <i style="width: {{ $a['persen'] }}%; background: {{ $bar }}"></i>
                            </div>
                            <div id="ang{{ $a['category_id'] }}" hidden>
                                <form method="POST" action="{{ route('rencana.anggaran') }}" class="rx-baris-form">
                                    @csrf
                                    <input type="hidden" name="category_id" value="{{ $a['category_id'] }}">
                                    <input name="batas" type="text" inputmode="numeric" autocomplete="off" value="{{ Format::isianNominal($a['batas']) }}"
                                           aria-label="Batas baru {{ $a['nama'] }}" class="fx-input" data-rupiah>
                                    <button type="submit" class="fb-btn fb-btn-dark">Simpan</button>
                                </form>
                                <p class="fx-meta" style="font-size: 13px; margin-top: 4px">Isi 0 untuk menghapus anggaran kategori ini.</p>
                            </div>
                        </div>
                    @empty
                        <div class="ux-kosong">
                            <x-tupai pose="senang" alt="" class="ux-kosong-tupai" />
                            <p>Belum ada anggaran. Tentukan batas belanja per kategori supaya tahu kapan harus mengerem.</p>
                        </div>
                    @endforelse
                </div>

                @if ($kategoriKosong->isNotEmpty())
                    <form method="POST" action="{{ route('rencana.anggaran') }}" class="rx-baris-form rx-tambah">
                        @csrf
                        <select name="category_id" aria-label="Kategori" class="fx-input fx-input-soft">
                            @foreach ($kategoriKosong as $k) <option value="{{ $k->id }}">{{ $k->nama }}</option> @endforeach
                        </select>
                        <input name="batas" required type="text" inputmode="numeric" autocomplete="off" placeholder="Batas per bulan, mis. 500.000"
                               aria-label="Batas per bulan" class="fx-input fx-input-soft" data-rupiah>
                        <button type="submit" class="fb-btn fb-btn-dark">Tambah anggaran</button>
                    </form>
                @endif
            </section>

            {{-- Pengeluaran yang direncanakan --}}
            <section class="fb-card" aria-label="Pengeluaran yang direncanakan">
                <div class="fb-head"><h3>Pengeluaran yang direncanakan</h3></div>
                <div class="rx-list">
                    @forelse ($rencana as $i => $r)
                        @php $telat = $r->tanggal->toDateString() < $hari; @endphp
                        <div class="rx-item">
                            <div class="rx-tgl" style="background: {{ $telat ? '#FDDDEB' : ['#FFE3B8', '#C7ECF1', '#E3F2CF'][$i % 3] }}">
                                <b class="fb-d">{{ $r->tanggal->day }}</b><span>{{ $BULAN[$r->tanggal->month - 1] }}</span>
                            </div>
                            <div class="rx-info">
                                <p class="rx-nama">{{ $r->nama }}</p>
                                <p class="fx-meta">
                                    @if ($telat)<b>Lewat sejak {{ Format::tgl($r->tanggal) }}</b>@else{{ $r->category?->nama ?? 'Tanpa kategori' }}@endif
                                    @if ($r->berulang) · <x-ikon nama="repeat" :size="12" /> tiap bulan @endif
                                </p>
                            </div>
                            <p class="rx-nominal">{{ Format::rupiah($r->nominal) }}</p>
                            <div class="rx-aksi">
                                <form method="POST" action="{{ route('rencana.bayar', $r) }}" class="rx-bayar">
                                    @csrf
                                    <select name="wallet_id" aria-label="Bayar dari wallet" class="fx-input">
                                        @foreach ($wallets as $w) <option value="{{ $w->id }}">{{ $w->nama }}</option> @endforeach
                                    </select>
                                    <button type="submit" class="fb-btn fb-btn-dark">Bayar</button>
                                </form>
                                <form method="POST" action="{{ route('rencana.hapus', $r) }}" data-konfirmasi="Hapus rencana ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="fx-act is-hapus" title="Hapus" aria-label="Hapus rencana {{ $r->nama }}"><x-ikon nama="trash" :size="15" /></button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="ux-kosong">
                            <x-tupai pose="senang" alt="" class="ux-kosong-tupai" />
                            <p>Bayar kos, UKT, kado teman... catat di sini supaya Prediksi tidak kaget.</p>
                        </div>
                    @endforelse
                </div>
                @if ($f['rencanaDulu'] > 0)
                    <p class="rx-catatan">
                        {{ Format::rupiah($f['rencanaDulu']) }} jatuh sebelum
                        {{ $f['next'] ? 'pemasukan berikutnya (' . Format::tgl($f['next']['tanggal_perkiraan']) . ')' : '30 hari ke depan' }}
                        dan sudah dihitung di <a href="{{ route('prediksi') }}">Prediksi</a>.
                    </p>
                @endif
            </section>
        </div>

        {{-- ===== Kolom kanan ===== --}}
        <aside class="fb-side">
            {{-- Target tabungan --}}
            <section class="rx-target" aria-label="Target tabungan">
                <x-tupai pose="koin" alt="" class="fx-form-tupai" />
                <h2 class="fx-form-title">Target tabungan</h2>
                @forelse ($targets as $t)
                    @php $info = $t->info; @endphp
                    <div class="rx-target-kartu">
                        <div class="rx-target-atas">
                            <p class="rx-nama">{{ $t->nama }}</p>
                            <b class="fb-d">{{ $info['persen'] }}%</b>
                            <form method="POST" action="{{ route('rencana.target.hapus', $t) }}" data-konfirmasi="Hapus target &quot;{{ $t->nama }}&quot;?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="fx-act is-hapus" title="Hapus" aria-label="Hapus target {{ $t->nama }}"><x-ikon nama="trash" :size="14" /></button>
                            </form>
                        </div>
                        <p class="fx-meta">{{ Format::rupiah($t->terkumpul) }} dari {{ Format::rupiah($t->target) }}@if ($t->tenggat) · tenggat {{ Format::tgl($t->tenggat) }}@endif</p>
                        <div class="rx-bar is-besar" role="progressbar" aria-valuenow="{{ $info['persen'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Kemajuan {{ $t->nama }}">
                            <i style="width: {{ $info['persen'] }}%; background: {{ $info['tercapai'] ? '#98C54E' : '#FFA934' }}"></i>
                        </div>
                        @if ($info['tercapai'])
                            <p class="rx-saran">Tercapai! Keren banget.</p>
                        @else
                            <p class="rx-saran">
                                @if ($info['perMinggu'])
                                    Sisihkan {{ Format::rupiah($info['perMinggu']) }}/minggu supaya tepat waktu.
                                @elseif ($info['telat'])
                                    Tenggat lewat, kurang {{ Format::rupiah($info['kurang']) }}.
                                @else
                                    Kurang {{ Format::rupiah($info['kurang']) }} lagi.
                                @endif
                            </p>
                            <form method="POST" action="{{ route('rencana.setor', $t) }}" class="rx-baris-form">
                                @csrf
                                <input name="nominal" required type="text" inputmode="numeric" autocomplete="off" placeholder="Setor, mis. 50.000"
                                       aria-label="Setoran untuk {{ $t->nama }}" class="fx-input fx-input-soft" data-rupiah>
                                <button type="submit" class="fb-btn fb-btn-dark">Setor</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="rx-target-kosong">Mau beli apa? Laptop, tiket konser, dana darurat... buat targetnya di bawah.</p>
                @endforelse

                <details class="rx-baru" @if ($errors->target->any() || $targets->isEmpty()) open @endif>
                    <summary class="fb-btn fb-btn-dark">+ Target baru</summary>
                    <form method="POST" action="{{ route('rencana.target') }}" class="rx-baru-form">
                        @csrf
                        <div>
                            <label for="tn" class="fx-label">Nama</label>
                            <input id="tn" name="nama" required maxlength="80" placeholder="Laptop baru" class="fx-input">
                        </div>
                        <div>
                            <label for="tt" class="fx-label">Jumlah target</label>
                            <div class="fx-rp"><span aria-hidden="true">Rp</span>
                                <input id="tt" name="target" required type="text" inputmode="numeric" autocomplete="off" placeholder="8.000.000"
                                       class="fx-input fx-input-big" data-rupiah></div>
                        </div>
                        <div>
                            <label for="tg" class="fx-label">Tenggat <span class="fx-label-opt">(opsional)</span></label>
                            <input id="tg" type="date" name="tenggat" class="fx-input">
                        </div>
                        <button type="submit" class="fb-btn fb-btn-dark fx-submit">Buat target</button>
                    </form>
                </details>
                <p class="fx-meta" style="font-size: 13px">Uang tabungan tetap tersimpan di wallet-mu. Kalau mau dipisah, buat wallet "Tabungan".</p>
            </section>

            {{-- Form rencana pengeluaran --}}
            <form method="POST" action="{{ route('rencana.store') }}" id="rencana-baru" class="fx-form">
                @csrf
                <h2 class="fx-form-title" style="padding-right: 0">Rencanakan pengeluaran</h2>
                <div>
                    <label for="rn" class="fx-label">Nama</label>
                    <input id="rn" name="nama" required maxlength="100" value="{{ old('nama') }}" placeholder="Bayar kos Desember" class="fx-input">
                </div>
                <div>
                    <label for="rnom" class="fx-label">Nominal</label>
                    <div class="fx-rp"><span aria-hidden="true">Rp</span>
                        <input id="rnom" name="nominal" required type="text" inputmode="numeric" autocomplete="off" value="{{ old('nominal') }}"
                               placeholder="1.200.000" class="fx-input fx-input-big" data-rupiah></div>
                </div>
                <div class="fx-grid2" style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr))">
                    <div>
                        <label for="rt" class="fx-label">Tanggal</label>
                        <input id="rt" type="date" name="tanggal" required value="{{ old('tanggal') }}" class="fx-input">
                    </div>
                    <div>
                        <label for="rk" class="fx-label">Kategori</label>
                        <select id="rk" name="category_id" class="fx-input">
                            <option value="">Tanpa kategori</option>
                            @foreach ($kategori as $k) <option value="{{ $k->id }}" @selected((string) old('category_id') === (string) $k->id)>{{ $k->nama }}</option> @endforeach
                        </select>
                    </div>
                </div>
                <label class="ux-cek"><input type="checkbox" name="berulang" value="1" @checked(old('berulang'))> Berulang tiap bulan</label>
                <button type="submit" class="fb-btn fb-btn-dark fx-submit">Simpan rencana</button>
            </form>
        </aside>
    </div>
</div>
@endsection
