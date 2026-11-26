{{-- Halaman Transaksi mahasiswa — desain baru (Nov 2026): kartu berwarna tanpa outline, riwayat per tanggal,
     filter pil (tanpa JavaScript, pakai :has), batas jajan dengan reaksi tupai. --}}
@extends('layouts.app')
@use('App\Support\Format')

@section('judul', 'Transaksi')
@section('judul_halaman', 'Transaksi')

@php
    $KELAS_TX = ['pemasukan' => 'is-in', 'pengeluaran' => 'is-out', 'transfer' => 'is-move'];
    $WARNA_TX = ['pemasukan' => 'fx-in', 'pengeluaran' => 'fx-out', 'transfer' => 'fx-move'];
    $TANDA_TX = ['pemasukan' => '+', 'pengeluaran' => '−', 'transfer' => ''];
    $IKON_WALLET = ['tunai' => 'banknote', 'bank' => 'landmark', 'ewallet' => 'smartphone'];
    $WARNA_WALLET = ['#FFF2A0', '#E3F2CF', '#C7ECF1', '#FDDDEB', '#FFE3B8'];

    // ringkasan bulan yang sedang dilihat
    $masuk = (float) $transaksi->where('jenis', 'pemasukan')->sum('nominal');
    $keluar = (float) $transaksi->where('jenis', 'pengeluaran')->sum('nominal');
    $selisih = $masuk - $keluar;
    $jumlah = $transaksi->countBy('jenis');

    // navigasi bulan
    $bulanLalu = substr(Format::tambahBulan($bulan . '-01', -1), 0, 7);
    $bulanDepan = substr(Format::tambahBulan($bulan . '-01', 1), 0, 7);
    $bulanIni = substr(Format::hariIni(), 0, 7);

    // riwayat dikelompokkan per tanggal
    $hariIni = Format::hariIni();
    $kemarin = date('Y-m-d', strtotime($hariIni . ' -1 day'));
    $perHari = $transaksi->groupBy(fn ($t) => $t->tanggal->toDateString());
@endphp

@section('isi')
<div class="fb fx">
    {{-- Bar atas: keterangan, ganti bulan, unduh CSV --}}
    <div class="fx-top">
        <p class="fx-sub">Catat pemasukan, pengeluaran, dan transfer antar wallet.</p>
        <nav class="fx-month" aria-label="Pilih bulan">
            <a href="{{ route('transaksi.index', ['bulan' => $bulanLalu]) }}" class="fx-month-btn" aria-label="Bulan sebelumnya"><x-ikon nama="chevron-left" :size="16" :tebal="3" /></a>
            <span class="fb-d">{{ Format::namaBulan($bulan) }}</span>
            @if ($bulan < $bulanIni)
                <a href="{{ route('transaksi.index', ['bulan' => $bulanDepan]) }}" class="fx-month-btn" aria-label="Bulan berikutnya"><x-ikon nama="chevron-right" :size="16" :tebal="3" /></a>
            @else
                <span class="fx-month-btn is-off" aria-hidden="true"><x-ikon nama="chevron-right" :size="16" :tebal="3" /></span>
            @endif
        </nav>
        <a href="{{ route('transaksi.export', ['bulan' => $bulan]) }}" class="fb-btn fb-btn-dark" title="Unduh CSV (bisa dibuka di Excel)">
            <x-ikon nama="download" :size="16" /> Unduh CSV
        </a>
    </div>

    {{-- Ringkasan bulan: kartu berwarna --}}
    <section class="fx-sum" aria-label="Ringkasan {{ Format::namaBulan($bulan) }}">
        <div class="fx-sum-card" style="--c: #E3F2CF">
            <span class="fx-sum-ic"><x-ikon nama="plus" :size="20" :tebal="2.4" /></span>
            <p class="fx-sum-label">Pemasukan</p>
            <p class="fx-sum-value fx-in">{{ Format::rupiah($masuk) }}</p>
            <p class="fx-sum-note">{{ $jumlah['pemasukan'] ?? 0 }} transaksi</p>
        </div>
        <div class="fx-sum-card" style="--c: #FDDDEB">
            <span class="fx-sum-ic"><x-ikon nama="receipt" :size="20" /></span>
            <p class="fx-sum-label">Pengeluaran</p>
            <p class="fx-sum-value">{{ Format::rupiah($keluar) }}</p>
            <p class="fx-sum-note">{{ $jumlah['pengeluaran'] ?? 0 }} transaksi</p>
        </div>
        <div class="fx-sum-card" style="--c: #FFF2A0">
            <span class="fx-sum-ic"><x-ikon nama="calculator" :size="20" /></span>
            <p class="fx-sum-label">Selisih</p>
            <p class="fx-sum-value {{ $selisih >= 0 ? 'fx-in' : 'fx-out' }}">{{ $selisih >= 0 ? '+' : '−' }}{{ Format::rupiah(abs($selisih)) }}</p>
            <p class="fx-sum-note">{{ $selisih >= 0 ? 'masih surplus bulan ini' : 'pengeluaran lebih besar' }}</p>
        </div>
    </section>

    <div class="fb-grid">
        {{-- ===== Kolom kiri ===== --}}
        <div class="fb-main">
            @include('transaksi._form', ['aksi' => route('transaksi.store'), 't' => null])

            {{-- Riwayat: filter pil memakai radio + :has (tanpa JavaScript) --}}
            <section class="fb-card fx-riwayat" aria-label="Riwayat transaksi">
                <div class="fb-head">
                    <h3>Riwayat</h3>
                    @foreach (['semua' => ['Semua', $transaksi->count()], 'pengeluaran' => ['Keluar', $jumlah['pengeluaran'] ?? 0],
                               'pemasukan' => ['Masuk', $jumlah['pemasukan'] ?? 0], 'transfer' => ['Transfer', $jumlah['transfer'] ?? 0]] as $k => [$lbl, $n])
                        <input type="radio" name="fx-filter" id="fx-f-{{ $k }}" class="fx-f sr-only" @checked($k === 'semua')>
                        <label for="fx-f-{{ $k }}" class="fx-pill">{{ $lbl }} <span>{{ $n }}</span></label>
                    @endforeach
                </div>

                @forelse ($perHari as $tgl => $isi)
                    @php
                        $totalHari = $isi->where('jenis', 'pemasukan')->sum('nominal') - $isi->where('jenis', 'pengeluaran')->sum('nominal');
                        $judul = ($tgl === $hariIni ? 'Hari ini · ' : ($tgl === $kemarin ? 'Kemarin · ' : '')) . Format::tglPanjang($tgl);
                    @endphp
                    <div class="fx-day">
                        <p class="fx-day-head"><span>{{ $judul }}</span><span>{{ $totalHari > 0 ? '+' : ($totalHari < 0 ? '−' : '') }}{{ Format::rupiah(abs($totalHari)) }}</span></p>
                        @foreach ($isi as $t)
                            <div class="fx-row" data-jenis="{{ $t->jenis }}">
                                <span class="fb-tx-ic {{ $KELAS_TX[$t->jenis] }}"><x-ikon :nama="Format::kategoriIkon($t->category?->nama, $t->jenis)" :size="20" /></span>
                                <div class="min-w-0 flex-1">
                                    <p class="fb-tx-name">{{ $t->keterangan }}</p>
                                    <p class="fx-meta">
                                        @if ($t->jenis === 'transfer')
                                            Transfer · {{ $t->wallet?->nama }} ke {{ $t->walletTujuan?->nama }}
                                        @else
                                            {{ $t->category?->nama ?? 'Lainnya' }} · {{ $t->wallet?->nama }}
                                        @endif
                                    </p>
                                </div>
                                <p class="fb-tx-amt {{ $WARNA_TX[$t->jenis] }}">{{ $TANDA_TX[$t->jenis] }}{{ Format::rupiah($t->nominal) }}</p>
                                <div class="fx-actions">
                                    @if ($t->bukti)
                                        <a href="{{ route('transaksi.struk', $t) }}" target="_blank" rel="noopener" class="fx-act is-struk"
                                           title="Lihat foto struk" aria-label="Lihat foto struk {{ $t->keterangan }}"><x-ikon nama="receipt-text" :size="15" /></a>
                                    @endif
                                    <a href="{{ route('transaksi.edit', $t) }}" class="fx-act" title="Ubah" aria-label="Ubah {{ $t->keterangan }}"><x-ikon nama="pencil" :size="15" /></a>
                                    <form method="POST" action="{{ route('transaksi.destroy', $t) }}" data-konfirmasi="Hapus transaksi ini?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="fx-act is-hapus" title="Hapus" aria-label="Hapus {{ $t->keterangan }}"><x-ikon nama="trash" :size="15" /></button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <x-kosong judul="Belum ada transaksi di bulan ini">Catat pengeluaran pertamamu lewat form di atas.</x-kosong>
                @endforelse
                <p class="fx-kosong-filter">Tidak ada transaksi jenis ini di bulan ini.</p>
            </section>
        </div>

        {{-- ===== Kolom kanan ===== --}}
        <aside class="fb-side">
            {{-- Batas jajan harian: kartu kuning + tupai yang bereaksi --}}
            @php
                $batas = (float) $user->batas_harian;
                $lewat = $batas > 0 && $keluarHariIni > $batas;
                $pct = $batas > 0 ? (int) min(100, round($keluarHariIni / $batas * 100)) : 0;
                $pose = $lewat ? 'marah' : ($pct >= 80 ? 'cemas' : 'senang');
            @endphp
            <form method="POST" action="{{ route('transaksi.batas') }}" id="batas" class="fx-batas">
                @csrf
                @method('PUT')
                <x-tupai :pose="$pose" alt="" class="fx-batas-tupai" />
                <p class="fb-kicker">Batas jajan hari ini</p>
                @if ($batas > 0)
                    <p class="fx-batas-angka fb-d">{{ Format::rupiah($keluarHariIni) }} <small>/ {{ Format::rupiah($batas) }}</small></p>
                    <p class="fx-batas-ket">
                        {{ $lewat ? 'Lewat ' . Format::rupiah($keluarHariIni - $batas) . '. Besok lebih hemat ya!' : 'Sisa ' . Format::rupiah($batas - $keluarHariIni) . ($pct >= 80 ? '. Hampir habis!' : '. Masih aman.') }}
                    </p>
                    <div class="fx-batas-bar" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Batas jajan terpakai"><span style="width: {{ $pct }}%"></span></div>
                @else
                    <p class="fx-batas-ket">Belum diatur. Isi batas supaya tupai bisa mengingatkanmu.</p>
                @endif
                <div class="fx-inline">
                    <input name="batas_harian" type="text" inputmode="numeric" autocomplete="off" value="{{ Format::isianNominal($batas) }}"
                           aria-label="Batas pengeluaran harian (Rp)" class="fx-input" data-rupiah>
                    <button type="submit" class="fb-btn fb-btn-dark">Simpan</button>
                </div>
                <p class="fx-hint">Isi 0 kalau tidak ingin dibatasi.</p>
            </form>

            {{-- Saldo wallet --}}
            <section class="fb-card" aria-label="Saldo wallet">
                <div class="fb-head">
                    <h3>Saldo wallet</h3>
                    @if (Route::has('wallet.index'))<a href="{{ route('wallet.index') }}" class="fb-link">Kelola</a>@endif
                </div>
                @foreach ($wallets as $i => $w)
                    <div class="fb-wallet">
                        <span class="fb-tx-ic" style="background: {{ $WARNA_WALLET[$i % 5] }}"><x-ikon :nama="$IKON_WALLET[$w->tipe] ?? 'banknote'" :size="18" /></span>
                        <span class="flex-1 min-w-0 font-bold truncate">{{ $w->nama }}</span>
                        <span class="fb-d {{ $w->saldo_sekarang < 0 ? 'fx-out' : '' }}">{{ Format::rupiah($w->saldo_sekarang) }}</span>
                    </div>
                @endforeach
            </section>

            {{-- Kategori saya --}}
            <section class="fb-card" aria-label="Kategori saya">
                <div class="fb-head"><h3>Kategori saya</h3></div>
                @if ($errors->kategori->any())
                    <p class="fx-error">{{ $errors->kategori->first() }}</p>
                @endif
                <form method="POST" action="{{ route('kategori.store') }}" class="fx-kat-form">
                    @csrf
                    <input name="nama" maxlength="50" placeholder="Mis. Print/Fotokopi" aria-label="Nama kategori" class="fx-input fx-input-soft">
                    <div class="fx-inline">
                        <select name="jenis" aria-label="Jenis kategori" class="fx-input fx-input-soft">
                            <option value="pengeluaran">Pengeluaran</option>
                            <option value="pemasukan">Pemasukan</option>
                        </select>
                        <button type="submit" class="fb-btn fb-btn-dark">Tambah</button>
                    </div>
                </form>
                <div class="fx-chips">
                    @forelse ($kategori->where('user_id', $user->id) as $k)
                        <form method="POST" action="{{ route('kategori.destroy', $k) }}" data-konfirmasi="Hapus kategori {{ $k->nama }}?" class="fx-chip">
                            @csrf
                            @method('DELETE')
                            {{ $k->nama }}
                            <button type="submit" title="Hapus" aria-label="Hapus kategori {{ $k->nama }}"><x-ikon nama="x" :size="12" :tebal="3" /></button>
                        </form>
                    @empty
                        <p class="fx-hint">Kategori bawaan sudah tersedia. Tambahkan kategori khususmu di sini.</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
