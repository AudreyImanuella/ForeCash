{{-- Form transaksi — desain baru: kartu oranye lembut, kolom putih tanpa garis (port dari transaksi/components/TxForm.tsx versi Next.js).
     Dipakai di index (catat baru, $t = null) dan edit (ubah, $t = transaksi).
     Variabel: $aksi (URL), $t, $wallets, $kategori, $jenisAwal --}}
@php
    $t = $t ?? null;
    $jenis = old('jenis', $t?->jenis ?? $jenisAwal ?? 'pengeluaran');
    $transfer = $jenis === 'transfer';
    // old() berisi teks persis seperti yang diketik ("100.000"); titiknya dirapikan oleh JavaScript (data-rupiah)
    $nominalAwal = old('nominal', $t ? number_format((float) $t->nominal, 0, ',', '.') : '');
    $kategoriPilih = old('category_id', $t?->category_id);
    $walletPilih = old('wallet_id', $t?->wallet_id ?? $wallets->first()?->id);
    $tujuanPilih = old('wallet_tujuan_id', $t?->wallet_tujuan_id ?? $wallets->get(1)?->id);
@endphp

<form method="POST" action="{{ $aksi }}" enctype="multipart/form-data" class="fx-form" data-form-tx
      @if (! $t && Route::has('transaksi.scan')) data-scan-url="{{ route('transaksi.scan') }}" @endif>
    @csrf
    @if ($t) @method('PUT') @endif

    @unless ($t)<x-tupai pose="koin" alt="" class="fx-form-tupai" />@endunless
    <h2 class="fx-form-title">{{ $t ? 'Ubah transaksi' : 'Catat transaksi' }}</h2>

    <div class="fx-grid2">
        <div>
            <label for="jenis" class="fx-label">Jenis</label>
            <select id="jenis" name="jenis" class="fx-input" data-pilih-jenis>
                <option value="pengeluaran" @selected($jenis === 'pengeluaran')>Pengeluaran</option>
                <option value="pemasukan" @selected($jenis === 'pemasukan')>Pemasukan</option>
                <option value="transfer" @selected($jenis === 'transfer')>Transfer antar wallet</option>
            </select>
            @error('jenis') <p class="fx-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="nominal" class="fx-label">Nominal</label>
            {{-- type="text" + inputmode="numeric": supaya bisa diberi titik ribuan, tapi HP tetap membuka keyboard angka --}}
            <div class="fx-rp"><span aria-hidden="true">Rp</span>
            <input id="nominal" name="nominal" type="text" inputmode="numeric" autocomplete="off" required
                   value="{{ $nominalAwal }}" placeholder="25.000" class="fx-input fx-input-big" data-rupiah></div>
            @error('nominal') <p class="fx-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="keterangan" class="fx-label">
            Keterangan <span class="fx-label-opt">(opsional · kosong = pakai nama kategori)</span>
        </label>
        <input id="keterangan" name="keterangan" maxlength="150" value="{{ old('keterangan', $t?->keterangan) }}"
               placeholder="Contoh: Makan siang kantin" class="fx-input">
        @error('keterangan') <p class="fx-error">{{ $message }}</p> @enderror
    </div>

    <div class="fx-grid2">
        {{-- kategori: disembunyikan saat transfer --}}
        <div data-bukan-transfer @if ($transfer) hidden @endif>
            <label for="category_id" class="fx-label">Kategori</label>
            <select id="category_id" name="category_id" class="fx-input" data-pilih-kategori>
                @foreach (['pengeluaran' => 'Pengeluaran', 'pemasukan' => 'Pemasukan'] as $j => $label)
                    <optgroup label="{{ $label }}" data-jenis="{{ $j }}">
                        @foreach ($kategori->where('jenis', $j) as $c)
                            <option value="{{ $c->id }}" @selected((string) $kategoriPilih === (string) $c->id)>{{ $c->nama }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            @error('category_id') <p class="fx-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="tanggal" class="fx-label">Tanggal</label>
            <input id="tanggal" type="date" name="tanggal" required max="{{ \App\Support\Format::hariIni() }}"
                   value="{{ old('tanggal', $t?->tanggal?->toDateString() ?? \App\Support\Format::hariIni()) }}"
                   class="fx-input">
            @error('tanggal') <p class="fx-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="fx-grid2">
        <div>
            <label for="wallet_id" class="fx-label" data-label-wallet>{{ $transfer ? 'Dari wallet' : 'Wallet' }}</label>
            <select id="wallet_id" name="wallet_id" class="fx-input">
                @foreach ($wallets as $w)
                    <option value="{{ $w->id }}" @selected((string) $walletPilih === (string) $w->id)>
                        {{ $w->nama }} ({{ \App\Support\Format::rupiah($w->saldo_sekarang) }})
                    </option>
                @endforeach
            </select>
            @error('wallet_id') <p class="fx-error">{{ $message }}</p> @enderror
        </div>
        {{-- wallet tujuan: hanya saat transfer --}}
        <div data-hanya-transfer @if (! $transfer) hidden @endif>
            <label for="wallet_tujuan_id" class="fx-label">Ke wallet</label>
            <select id="wallet_tujuan_id" name="wallet_tujuan_id" class="fx-input">
                @foreach ($wallets as $w)
                    <option value="{{ $w->id }}" @selected((string) $tujuanPilih === (string) $w->id)>{{ $w->nama }}</option>
                @endforeach
            </select>
            <p class="fx-hint">Transfer tidak dihitung sebagai pemasukan/pengeluaran.</p>
            @error('wallet_tujuan_id') <p class="fx-error">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Scan struk: foto dibaca AI (Gemini), cadangannya OCR di browser; hasilnya mengisi form di atas --}}
    @unless ($t)
        <div class="fx-scan">
            <button type="button" class="fx-scan-btn" data-scan-struk>
                <span class="fx-scan-ic"><x-ikon nama="scan-line" :size="22" :tebal="2.4" /></span>
                <span><b>Scan struk</b><small>Foto struk, nominal &amp; tanggal terisi otomatis</small></span>
            </button>
            <input type="file" accept="image/*" capture="environment" class="sr-only" data-scan-input tabindex="-1" aria-hidden="true">
            <p class="fx-scan-hasil" data-scan-hasil hidden aria-live="polite"></p>
        </div>
    @endunless

    <div>
        <label for="bukti" class="fx-label">
            {{ $t ? 'Foto struk' : 'Atau lampirkan foto saja' }} <span class="fx-label-opt">(opsional · JPG/JPEG/PNG, maks. 2 MB)</span>
        </label>
        <input id="bukti" type="file" name="bukti" accept=".jpg,.jpeg,.png,image/jpeg,image/png"
               class="fx-file">
        @error('bukti') <p class="fx-error">{{ $message }}</p> @enderror

        @if ($t?->bukti)
            <div class="flex items-center gap-3 mt-2">
                <a href="{{ route('transaksi.struk', $t) }}" target="_blank" rel="noopener">
                    <img src="{{ route('transaksi.struk', $t) }}" alt="Struk tersimpan" class="w-16 h-16 object-cover rounded-xl border">
                </a>
                <label class="flex items-center gap-2 text-xs font-bold text-hsRed">
                    <input type="checkbox" name="hapus_bukti" value="1" @checked(old('hapus_bukti'))> Hapus foto ini
                </label>
            </div>
        @endif
    </div>

    @if ($t)
        <div class="fx-btn-row">
            <a href="{{ route('transaksi.index', ['bulan' => $t->tanggal->format('Y-m')]) }}" class="fb-btn fb-btn-light">Batal</a>
            <button type="submit" class="fb-btn fb-btn-dark fx-submit">Simpan perubahan</button>
        </div>
    @else
        <button type="submit" class="fb-btn fb-btn-dark fx-submit">Simpan transaksi</button>
    @endif
</form>