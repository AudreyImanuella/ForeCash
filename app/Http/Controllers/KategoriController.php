<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Kategori buatan mahasiswa sendiri (port dari KategoriSaya.tsx versi Next.js). */
class KategoriController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $uid = $request->user()->id;
        $data = $request->validateWithBag('kategori', [
            'nama' => ['required', 'string', 'max:50'],
            'jenis' => ['required', Rule::in(['pemasukan', 'pengeluaran'])],
        ], [
            'nama.required' => 'Nama kategori wajib diisi.',
            'nama.max' => 'Nama kategori maksimal 50 karakter.',
        ]);

        $nama = trim($data['nama']);
        $sudahAda = Category::milik($uid)->where('jenis', $data['jenis'])->where('nama', $nama)->exists();
        if ($sudahAda) {
            return back()->withErrors(['nama' => 'Kategori "' . $nama . '" sudah ada.'], 'kategori')->withInput();
        }

        $request->user()->categories()->create(['nama' => $nama, 'jenis' => $data['jenis']]);

        return back()->with('sukses', 'Kategori ditambahkan.');
    }

    public function destroy(Request $request, Category $kategori): RedirectResponse
    {
        // kategori bawaan (user_id kosong) & milik orang lain tidak bisa dihapus
        abort_unless((int) $kategori->user_id === (int) $request->user()->id, 404);

        // transaksi yang memakai kategori ini otomatis jadi tanpa kategori (nullOnDelete di migration)
        $kategori->delete();

        return back()->with('sukses', 'Kategori dihapus.');
    }
}
