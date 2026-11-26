{{-- Utang & Patungan — desain baru (Nov 2026): ringkasan berwarna, kartu per orang, filter pil (tanpa JS, pakai :has),
     split bill dengan pratinjau, Teman ForeCash dengan kode + QR + link undangan. --}}
@extends('layouts.app')
@use('App\Support\Format')
@use('App\Http\Controllers\UtangController')

@section('judul', 'Utang & Patungan')
@section('judul_halaman', 'Utang & Patungan')

@php
    $hari = Format::hariIni();
    $sepekan = date('Y-m-d', strtotime($hari . ' +7 day'));
    $piutangTotal = (float) $belum->where('arah', 'piutang')->sum('nominal');
    $utangTotal = (float) $belum->where('arah', 'utang')->sum('nominal');
    $orangPiutang = $belum->where('arah', 'piutang')->pluck('nama_teman')->unique()->count();
    $orangUtang = $belum->where('arah', 'utang')->pluck('nama_teman')->unique()->count();
    $dekat = $belum->filter(fn ($d) => $d->jatuh_tempo && $d->jatuh_tempo->toDateString() <= $sepekan);
    $telat = $dekat->filter(fn ($d) => $d->jatuh_tempo->toDateString() < $hari)->count();
    $avatarTeman = $teman->keyBy('id');
    $linkUndangan = $user->kode_teman ? route('teman.undangan', $user->kode_teman) : null;
    $WARNA_AV = ['#FFF2A0', '#C7ECF1', '#E3F2CF', '#FDDDEB', '#FFE3B8'];
@endphp

@section('isi')
<div class="fb fx ux">
    <p class="fx-sub">Catat utang-piutang antarteman dan bagi tagihan bersama.</p>

    {{-- Ringkasan berwarna --}}
    <section class="fx-sum" aria-label="Ringkasan utang">
        <div class="fx-sum-card" style="--c: #E3F2CF">
            <span class="fx-sum-ic"><x-ikon nama="hand-coins" :size="20" /></span>
            <p class="fx-sum-label">Teman utang ke kamu</p>
            <p class="fx-sum-value fx-in">{{ Format::rupiah($piutangTotal) }}</p>
            <p class="fx-sum-note">{{ $orangPiutang }} orang</p>
        </div>
        <div class="fx-sum-card" style="--c: #FDDDEB">
            <span class="fx-sum-ic"><x-ikon nama="user-minus" :size="20" /></span>
            <p class="fx-sum-label">Kamu utang ke teman</p>
            <p class="fx-sum-value">{{ Format::rupiah($utangTotal) }}</p>
            <p class="fx-sum-note">{{ $orangUtang }} orang</p>
        </div>
        <div class="fx-sum-card" style="--c: #FFF2A0">
            <span class="fx-sum-ic"><x-ikon nama="calendar-check" :size="20" /></span>
            <p class="fx-sum-label">Jatuh tempo 7 hari ke depan</p>
            <p class="fx-sum-value">{{ $dekat->count() }} catatan</p>
            <p class="fx-sum-note">{{ $telat ? $telat . ' sudah terlewat' : ($dekat->isNotEmpty() ? 'paling dekat ' . Format::tgl($dekat->min('jatuh_tempo')) : 'aman, belum ada') }}</p>
        </div>
    </section>

    {{-- Teman menandai lunas: catat ke wallet --}}
    @foreach ($pelunasan as $d)
        @php $masuk = $d->arah === 'piutang'; @endphp
        <section class="ux-konfirmasi" aria-label="Pelunasan dari teman">
            <span class="ux-av" style="background: #C7ECF1">{{ mb_strtoupper(mb_substr($d->nama_teman, 0, 1)) }}</span>
            <p class="ux-konfirmasi-teks"><b>{{ $d->nama_teman }}</b> menandai <b>{{ $d->keterangan }}</b> ({{ Format::rupiah($d->nominal) }}) sudah lunas.
                {{ $masuk ? 'Uangnya masuk ke wallet mana?' : 'Dibayar dari wallet mana?' }}</p>
            <form method="POST" action="{{ route('utang.pelunasan', $d) }}" class="ux-konfirmasi-form">
                @csrf
                <select name="wallet_id" aria-label="{{ $masuk ? 'Masuk ke wallet' : 'Dibayar dari wallet' }}" class="fx-input">
                    @foreach ($wallets as $w) <option value="{{ $w->id }}">{{ $w->nama }}</option> @endforeach
                </select>
                <button type="submit" class="fb-btn fb-btn-dark">Catat {{ $masuk ? 'pemasukan' : 'pengeluaran' }}</button>
                <button type="submit" name="abaikan" value="1" class="fb-btn fb-btn-light">Abaikan</button>
            </form>
        </section>
    @endforeach

    <div class="fb-grid">
        {{-- ===== Kolom kiri ===== --}}
        <div class="fb-main">
            {{-- Belum lunas: kartu per orang + filter pil --}}
            <section class="fb-card ux-daftar" aria-label="Belum lunas">
                <div class="fb-head">
                    <h3>Belum lunas</h3>
                    @foreach (['semua' => ['Semua', $belum->count()], 'piutang' => ['Piutang', $belum->where('arah', 'piutang')->count()], 'utang' => ['Utang', $belum->where('arah', 'utang')->count()]] as $k => [$lbl, $n])
                        <input type="radio" name="ux-filter" id="ux-f-{{ $k }}" class="fx-f sr-only" @checked($k === 'semua')>
                        <label for="ux-f-{{ $k }}" class="fx-pill">{{ $lbl }} <span>{{ $n }}</span></label>
                    @endforeach
                </div>
                <div class="ux-grid">
                    @forelse ($belum as $i => $d)
                        @php
                            $piutang = $d->arah === 'piutang';
                            $lewat = $d->jatuh_tempo && $d->jatuh_tempo->toDateString() < $hari;
                            $hariLewat = $lewat ? (int) round((strtotime($hari) - strtotime($d->jatuh_tempo->toDateString())) / 86400) : 0;
                            $fc = $d->teman_id ? $avatarTeman->get($d->teman_id) : null;
                        @endphp
                        <article class="ux-kartu {{ $piutang ? 'is-piutang' : 'is-utang' }}" data-arah="{{ $d->arah }}">
                            <div class="ux-kartu-atas">
                                @if ($fc)
                                    <span class="ux-av ux-av-img"><x-avatar :id="$fc->avatar" :size="44" /></span>
                                @else
                                    <span class="ux-av" style="background: {{ $WARNA_AV[$i % 5] }}">{{ mb_strtoupper(mb_substr($d->nama_teman, 0, 1)) }}</span>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="ux-nama">{{ $d->nama_teman }}</p>
                                    <p class="ux-arah">{{ $piutang ? 'Utang ke kamu' : 'Kamu utang ke dia' }}</p>
                                </div>
                                <p class="ux-nominal {{ $piutang ? 'fx-in' : '' }}">{{ $piutang ? '+' : '' }}{{ Format::rupiah($d->nominal) }}</p>
                            </div>
                            <p class="fx-meta">{{ $d->keterangan }}</p>
                            <div class="ux-chips">
                                @if ($d->teman_id)<span class="ux-chip is-fc"><x-ikon nama="link" :size="12" /> Terhubung di ForeCash</span>@endif
                                @if ($d->jatuh_tempo)
                                    <span class="ux-chip {{ $lewat ? 'is-telat' : 'is-tempo' }}">{{ $lewat ? 'Telat ' . $hariLewat . ' hari' : 'Jatuh tempo ' . Format::tgl($d->jatuh_tempo) }}</span>
                                @endif
                            </div>
                            <div class="ux-aksi">
                                <form method="POST" action="{{ route('utang.lunas', $d) }}" class="ux-lunas">
                                    @csrf
                                    <select name="wallet_id" aria-label="Wallet untuk pelunasan" class="fx-input">
                                        @foreach ($wallets as $w) <option value="{{ $w->id }}">{{ $w->nama }}</option> @endforeach
                                    </select>
                                    <button type="submit" class="fb-btn fb-btn-dark">Lunas</button>
                                </form>
                                @if ($piutang && ! $d->teman_id)
                                    <a href="{{ UtangController::linkPengingatWA($d->nama_teman, $d->keterangan, (float) $d->nominal) }}" target="_blank" rel="noopener"
                                       class="fx-act is-wa" title="Ingatkan via WhatsApp" aria-label="Ingatkan {{ $d->nama_teman }} via WhatsApp"><x-ikon nama="message-circle" :size="16" /></a>
                                @endif
                                <form method="POST" action="{{ route('utang.destroy', $d) }}" data-konfirmasi="Hapus catatan ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="fx-act" title="Hapus" aria-label="Hapus catatan {{ $d->nama_teman }}"><x-ikon nama="trash" :size="15" /></button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <div class="ux-kosong">
                            <x-tupai pose="senang" alt="" class="ux-kosong-tupai" />
                            <p><b>Bersih!</b> Tidak ada utang atau piutang yang belum lunas.</p>
                        </div>
                    @endforelse
                </div>
                <p class="fx-kosong-filter">Tidak ada catatan jenis ini.</p>
            </section>

            {{-- Split bill: kartu oranye lembut + pratinjau per orang --}}
            <form method="POST" action="{{ route('utang.split') }}" id="split" class="fx-form" data-split>
                @csrf
                <x-tupai pose="koin" alt="" class="fx-form-tupai" />
                <h2 class="fx-form-title">Split bill</h2>
                @if ($errors->split->any())
                    <p class="fx-error">{{ $errors->split->first() }}</p>
                @endif
                <div class="fx-grid2">
                    <div>
                        <label for="sj" class="fx-label">Untuk apa</label>
                        <input id="sj" name="judul" maxlength="150" value="{{ old('judul') }}" placeholder="Makan malam angkatan" class="fx-input">
                    </div>
                    <div>
                        <label for="st" class="fx-label">Total tagihan</label>
                        <div class="fx-rp"><span aria-hidden="true">Rp</span>
                            <input id="st" name="total" required type="text" inputmode="numeric" autocomplete="off" value="{{ old('total') }}"
                                   placeholder="150.000" class="fx-input fx-input-big" data-rupiah></div>
                    </div>
                </div>
                @if ($teman->isNotEmpty())
                    <fieldset>
                        <legend class="fx-label">Teman ForeCash (tagihannya langsung masuk ke akun mereka)</legend>
                        <div class="ux-pilih">
                            @foreach ($teman as $t)
                                <label class="ux-pilih-teman">
                                    <input type="checkbox" name="teman_fc[]" value="{{ $t->id }}" class="sr-only" @checked(in_array($t->id, old('teman_fc', [])))>
                                    <span class="ux-av ux-av-sm ux-av-img"><x-avatar :id="$t->avatar" :size="28" /></span>
                                    {{ \Illuminate\Support\Str::before($t->name, ' ') ?: $t->name }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif
                <div>
                    <label for="sn" class="fx-label">{{ $teman->isNotEmpty() ? 'Teman lain (pisahkan dengan koma)' : 'Nama teman (pisahkan dengan koma)' }}</label>
                    <input id="sn" name="teman" value="{{ old('teman') }}" placeholder="Budi, Siti, Bima" class="fx-input">
                </div>
                <div class="fx-grid2">
                    <label class="ux-cek"><input type="checkbox" name="termasuk_saya" value="1" @checked(old('termasuk_saya', '1'))> Saya ikut menanggung bagian</label>
                    <div>
                        <label for="sd" class="fx-label">Jatuh tempo (opsional)</label>
                        <input id="sd" type="date" name="jatuh_tempo" value="{{ old('jatuh_tempo') }}" class="fx-input">
                    </div>
                </div>
                <p class="ux-split-hasil" data-split-hasil hidden></p>
                <button type="submit" class="fb-btn fb-btn-dark fx-submit">Bagi tagihan</button>
            </form>
        </div>

        {{-- ===== Kolom kanan ===== --}}
        <aside class="fb-side">
            {{-- Teman ForeCash: kode + QR + link undangan --}}
            <section class="ux-teman" aria-label="Teman ForeCash">
                <h3 class="fb-d" style="font-size: 22px">Teman ForeCash</h3>
                @if ($linkUndangan)
                    <div class="ux-kode">
                        <canvas class="ux-qr" data-qr="{{ $linkUndangan }}" width="104" height="104" role="img" aria-label="Kode QR undangan teman"></canvas>
                        <div class="min-w-0 flex-1">
                            <p class="ux-kode-label">Kode temanmu</p>
                            <p class="ux-kode-isi fb-d">{{ $user->kode_teman }}</p>
                            <div class="ux-kode-aksi">
                                <button type="button" data-salin="{{ $user->kode_teman }}" class="ux-mini is-dark"><x-ikon nama="copy" :size="14" /> <span>Salin</span></button>
                                <button type="button" class="ux-mini is-green" data-bagikan="{{ $linkUndangan }}"
                                        data-teks="Yuk berteman di ForeCash biar split bill lebih gampang! Buka link ini: ">
                                    <x-ikon nama="link" :size="14" /> Bagikan link</button>
                            </div>
                        </div>
                    </div>
                    <p class="ux-teman-info">Temanmu cukup <b>scan QR</b> pakai kamera HP atau buka <b>link undangan</b>. Bisa juga ketik kodenya:</p>
                @endif
                <form method="POST" action="{{ route('teman.store') }}" class="fx-inline" style="padding-right: 0">
                    @csrf
                    <input name="kode" required maxlength="8" placeholder="Kode teman, mis. ANDI26" aria-label="Kode teman" autocomplete="off" class="fx-input" style="text-transform: uppercase">
                    <button type="submit" class="fb-btn fb-btn-dark">Tambah</button>
                </form>
                @if ($errors->teman->any())
                    <p class="fx-error">{{ $errors->teman->first() }}</p>
                @endif
                @if ($teman->isNotEmpty())
                    <ul class="ux-daftar-teman">
                        @foreach ($teman as $t)
                            <li>
                                <span class="ux-av ux-av-sm ux-av-img"><x-avatar :id="$t->avatar" :size="32" /></span>
                                <span class="flex-1 min-w-0 font-bold truncate">{{ $t->name }}</span>
                                <form method="POST" action="{{ route('teman.destroy', $t) }}" data-konfirmasi="Putus pertemanan dengan {{ $t->name }}?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="fx-act" aria-label="Putus pertemanan dengan {{ $t->name }}" title="Putus pertemanan"><x-ikon nama="user-minus" :size="14" /></button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Catat utang / piutang --}}
            <form method="POST" action="{{ route('utang.store') }}" class="fb-card ux-catat" data-catat-utang>
                @csrf
                <div class="fb-head"><h3>Catat utang</h3></div>
                @if ($errors->catat->any())
                    <p class="fx-error">{{ $errors->catat->first() }}</p>
                @endif
                @if ($teman->isNotEmpty())
                    <div>
                        <label for="dfc" class="fx-label">Teman ForeCash (opsional)</label>
                        <select id="dfc" name="teman_fc" class="fx-input fx-input-soft" data-pilih-teman>
                            <option value="">Bukan pengguna ForeCash</option>
                            @foreach ($teman as $t)
                                <option value="{{ $t->id }}" @selected((string) old('teman_fc') === (string) $t->id)>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div data-nama-teman>
                    <label for="dn" class="fx-label">Nama teman</label>
                    <input id="dn" name="nama_teman" maxlength="100" value="{{ old('nama_teman') }}" class="fx-input fx-input-soft">
                </div>
                <div>
                    <label for="da" class="fx-label">Posisi</label>
                    <select id="da" name="arah" class="fx-input fx-input-soft">
                        <option value="piutang" @selected(old('arah') === 'piutang')>Dia utang ke saya</option>
                        <option value="utang" @selected(old('arah') === 'utang') data-bukan-teman>Saya utang ke dia</option>
                    </select>
                    <p class="fx-hint" data-info-teman hidden>Tagihan akan muncul di akun temanmu.</p>
                </div>
                <div>
                    <label for="dk" class="fx-label">Keterangan</label>
                    <input id="dk" name="keterangan" required maxlength="150" value="{{ old('keterangan') }}" placeholder="Pinjam untuk ongkos" class="fx-input fx-input-soft">
                </div>
                <div class="fx-grid2" style="grid-template-columns: repeat(auto-fit, minmax(130px, 1fr))">
                    <div>
                        <label for="dnom" class="fx-label">Nominal</label>
                        <input id="dnom" name="nominal" required type="text" inputmode="numeric" autocomplete="off" value="{{ old('nominal') }}"
                               placeholder="20.000" class="fx-input fx-input-soft" data-rupiah>
                    </div>
                    <div>
                        <label for="dt" class="fx-label">Jatuh tempo</label>
                        <input id="dt" type="date" name="jatuh_tempo" value="{{ old('jatuh_tempo') }}" class="fx-input fx-input-soft">
                    </div>
                </div>
                <button type="submit" class="fb-btn fb-btn-dark fx-submit">Simpan</button>
            </form>

            @if ($riwayat->isNotEmpty())
                <section class="fb-card" aria-label="Riwayat lunas">
                    <div class="fb-head"><h3>Riwayat lunas</h3></div>
                    @foreach ($riwayat as $d)
                        <p class="ux-riwayat"><span><b>{{ $d->nama_teman }}</b> · {{ $d->keterangan }}</span><span class="fx-in">{{ Format::rupiah($d->nominal) }}</span></p>
                    @endforeach
                </section>
            @endif
        </aside>
    </div>
</div>
@endsection
