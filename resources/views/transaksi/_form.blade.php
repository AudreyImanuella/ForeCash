{{-- Form transaksi (port dari transaksi/components/TxForm.tsx versi Next.js).
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

<form method="POST" action="{{ $aksi }}" enctype="multipart/form-data" class="hs-card p-5 space-y-3" data-form-tx>
    @csrf
    @if ($t) @method('PUT') @endif

    <h4 class="text-sm font-black">{{ $t ? 'Ubah Transaksi' : 'Catat Transaksi' }}</h4>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label for="jenis" class="text-xs font-bold">Jenis</label>
            <select id="jenis" name="jenis" class="w-full px-3 py-2.5 text-sm border" data-pilih-jenis>
                <option value="pengeluaran" @selected($jenis === 'pengeluaran')>Pengeluaran</option>
                <option value="pemasukan" @selected($jenis === 'pemasukan')>Pemasukan</option>
                <option value="transfer" @selected($jenis === 'transfer')>Transfer antar wallet</option>
            </select>
            @error('jenis') <p class="text-xs font-bold text-hsRed mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="nominal" class="text-xs font-bold">Nominal (Rp)</label>
            {{-- type="text" + inputmode="numeric": supaya bisa diberi titik ribuan, tapi HP tetap membuka keyboard angka --}}
            <input id="nominal" name="nominal" type="text" inputmode="numeric" autocomplete="off" required
                   value="{{ $nominalAwal }}" placeholder="Contoh: 25.000" class="w-full px-3 py-2.5 text-sm border" data-rupiah>
            @error('nominal') <p class="text-xs font-bold text-hsRed mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="keterangan" class="text-xs font-bold">
            Keterangan <span class="font-normal text-slate-400">(opsional · kosong = pakai nama kategori)</span>
        </label>
        <input id="keterangan" name="keterangan" maxlength="150" value="{{ old('keterangan', $t?->keterangan) }}"
               placeholder="Contoh: Makan siang kantin" class="w-full px-3 py-2.5 text-sm border">
        @error('keterangan') <p class="text-xs font-bold text-hsRed mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        {{-- kategori: disembunyikan saat transfer --}}
        <div data-bukan-transfer @if ($transfer) hidden @endif>
            <label for="category_id" class="text-xs font-bold">Kategori</label>
            <select id="category_id" name="category_id" class="w-full px-3 py-2.5 text-sm border" data-pilih-kategori>
                @foreach (['pengeluaran' => 'Pengeluaran', 'pemasukan' => 'Pemasukan'] as $j => $label)
                    <optgroup label="{{ $label }}" data-jenis="{{ $j }}">
                        @foreach ($kategori->where('jenis', $j) as $c)
                            <option value="{{ $c->id }}" @selected((string) $kategoriPilih === (string) $c->id)>{{ $c->nama }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            @error('category_id') <p class="text-xs font-bold text-hsRed mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="tanggal" class="text-xs font-bold">Tanggal</label>
            <input id="tanggal" type="date" name="tanggal" required max="{{ \App\Support\Format::hariIni() }}"
                   value="{{ old('tanggal', $t?->tanggal?->toDateString() ?? \App\Support\Format::hariIni()) }}"
                   class="w-full px-3 py-2.5 text-sm border">
            @error('tanggal') <p class="text-xs font-bold text-hsRed mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label for="wallet_id" class="text-xs font-bold" data-label-wallet>{{ $transfer ? 'Dari wallet' : 'Wallet' }}</label>
            <select id="wallet_id" name="wallet_id" class="w-full px-3 py-2.5 text-sm border">
                @foreach ($wallets as $w)
                    <option value="{{ $w->id }}" @selected((string) $walletPilih === (string) $w->id)>
                        {{ $w->nama }} ({{ \App\Support\Format::rupiah($w->saldo_sekarang) }})
                    </option>
                @endforeach
            </select>
            @error('wallet_id') <p class="text-xs font-bold text-hsRed mt-1">{{ $message }}</p> @enderror
        </div>
        {{-- wallet tujuan: hanya saat transfer --}}
        <div data-hanya-transfer @if (! $transfer) hidden @endif>
            <label for="wallet_tujuan_id" class="text-xs font-bold">Ke wallet</label>
            <select id="wallet_tujuan_id" name="wallet_tujuan_id" class="w-full px-3 py-2.5 text-sm border">
                @foreach ($wallets as $w)
                    <option value="{{ $w->id }}" @selected((string) $tujuanPilih === (string) $w->id)>{{ $w->nama }}</option>
                @endforeach
            </select>
            <p class="text-[10px] text-slate-500 mt-1">Transfer tidak dihitung sebagai pemasukan/pengeluaran.</p>
            @error('wallet_tujuan_id') <p class="text-xs font-bold text-hsRed mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="bukti" class="text-xs font-bold">
            Foto struk <span class="font-normal text-slate-400">(opsional · JPG/JPEG/PNG, maks. 2 MB)</span>
        </label>
        <input id="bukti" type="file" name="bukti" accept=".jpg,.jpeg,.png,image/jpeg,image/png"
               class="w-full px-3 py-2 text-xs border bg-white">
        @error('bukti') <p class="text-xs font-bold text-hsRed mt-1">{{ $message }}</p> @enderror

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
        <div class="flex gap-2">
            <a href="{{ route('transaksi.index', ['bulan' => $t->tanggal->format('Y-m')]) }}" class="hs-btn bg-slate-200 px-4 py-3 text-sm">Batal</a>
            <button type="submit" class="hs-btn bg-hsBlue text-white flex-1 py-3 text-sm">Simpan Perubahan</button>
        </div>
    @else
        <button type="submit" class="hs-btn bg-hsOrange text-white w-full py-3 text-sm">Simpan Transaksi</button>
    @endif
</form>