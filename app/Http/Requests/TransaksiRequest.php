<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Support\Format;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi tambah/ubah transaksi (port dari validasiTx() di transaksi/actions.ts versi Next.js).
 * Peran mahasiswa sudah dicek oleh middleware CekPeran di route.
 */
class TransaksiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Dijalankan SEBELUM validasi: rapikan isian dari form. */
    protected function prepareForValidation(): void
    {
        // "25.000" -> "25000", "12.500,50" -> "12500.50" (titik = pemisah ribuan, koma = desimal)
        $nominal = str_replace(['.', ' '], '', (string) $this->input('nominal'));
        $nominal = str_replace(',', '.', $nominal);

        $keterangan = trim((string) $this->input('keterangan'));
        if ($this->input('jenis') === 'transfer' && $keterangan === '') {
            $keterangan = 'Transfer antar wallet';
        }

        $this->merge(['nominal' => $nominal, 'keterangan' => $keterangan]);
    }

    public function rules(): array
    {
        $uid = $this->user()->id;
        $walletMilikUser = Rule::exists('wallets', 'id')->where('user_id', $uid);

        return [
            'jenis' => ['required', Rule::in(['pemasukan', 'pengeluaran', 'transfer'])],
            'nominal' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            // opsional: kalau kosong diisi otomatis di dataTransaksi()
            'keterangan' => ['nullable', 'string', 'max:150'],
            'tanggal' => ['required', 'date_format:Y-m-d', 'before_or_equal:' . Format::hariIni()],
            'wallet_id' => ['required', 'integer', $walletMilikUser],

            // hanya untuk transfer (selain transfer, isian ini dibuang)
            'wallet_tujuan_id' => ['exclude_unless:jenis,transfer', 'required', 'integer', 'different:wallet_id', $walletMilikUser],

            // hanya untuk pemasukan/pengeluaran: kategori bawaan atau milik user, dan jenisnya harus cocok
            'category_id' => ['exclude_if:jenis,transfer', 'required', 'integer',
                Rule::exists('categories', 'id')
                    ->where('jenis', $this->input('jenis'))
                    ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $uid)),
            ],

            // foto struk opsional (seperti Lab 9): jpg/jpeg/png, maks 2 MB (2048 KB)
            'bukti' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'hapus_bukti' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'jenis.required' => 'Pilih jenis transaksi.',
            'jenis.in' => 'Jenis transaksi tidak valid.',
            'nominal.required' => 'Nominal wajib diisi.',
            'nominal.numeric' => 'Nominal harus berupa angka.',
            'nominal.gt' => 'Nominal harus lebih dari 0.',
            'nominal.max' => 'Nominal terlalu besar.',
            'keterangan.max' => 'Keterangan maksimal 150 karakter.',
            'tanggal.required' => 'Tanggal wajib diisi.',
            'tanggal.date_format' => 'Tanggal tidak valid.',
            'tanggal.before_or_equal' => 'Tanggal tidak boleh melebihi hari ini.',
            'wallet_id.required' => 'Pilih wallet.',
            'wallet_id.exists' => 'Wallet tidak valid.',
            'wallet_tujuan_id.required' => 'Pilih wallet tujuan.',
            'wallet_tujuan_id.different' => 'Wallet asal dan tujuan tidak boleh sama.',
            'wallet_tujuan_id.exists' => 'Wallet tujuan tidak valid.',
            'category_id.required' => 'Pilih kategori.',
            'category_id.exists' => 'Pilih kategori yang sesuai.',
            'bukti.image' => 'File struk harus berupa gambar.',
            'bukti.mimes' => 'Foto struk harus berformat JPG, JPEG, atau PNG.',
            'bukti.max' => 'Ukuran foto struk maksimal 2 MB.',
            'bukti.uploaded' => 'Foto struk gagal diunggah (mungkin lebih dari 2 MB).',
        ];
    }

    /** Kolom yang siap disimpan ke tabel transactions (tanpa user_id & bukti). */
    public function dataTransaksi(): array
    {
        $v = $this->validated();

        // keterangan kosong: transfer -> "Transfer antar wallet" (di prepareForValidation),
        // pemasukan/pengeluaran -> nama kategorinya, misal "Uang Saku"
        $keterangan = trim((string) ($v['keterangan'] ?? ''));
        if ($keterangan === '' && isset($v['category_id'])) {
            $keterangan = (string) Category::whereKey($v['category_id'])->value('nama');
        }

        return [
            'jenis' => $v['jenis'],
            'nominal' => round((float) $v['nominal'], 2),
            'keterangan' => $keterangan,
            'tanggal' => $v['tanggal'],
            'wallet_id' => (int) $v['wallet_id'],
            'wallet_tujuan_id' => isset($v['wallet_tujuan_id']) ? (int) $v['wallet_tujuan_id'] : null,
            'category_id' => isset($v['category_id']) ? (int) $v['category_id'] : null,
        ];
    }
}