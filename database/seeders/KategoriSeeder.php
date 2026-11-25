<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Kategori bawaan (user_id kosong = bisa dipakai semua pengguna).
 * Aman dijalankan berkali-kali: kategori yang sudah ada tidak dibuat dua kali.
 */
class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = [
            ['Makan & Minum', 'pengeluaran'],
            ['Transportasi', 'pengeluaran'],
            ['Kos / Sewa', 'pengeluaran'],
            ['Kebutuhan Kuliah', 'pengeluaran'],
            ['Hiburan', 'pengeluaran'],
            ['Lainnya', 'pengeluaran'],
            ['Uang Saku', 'pemasukan'],
            ['Kerja Sampingan', 'pemasukan'],
            ['Beasiswa', 'pemasukan'],
            ['Pelunasan Utang/Piutang', 'pemasukan'],
            ['Pelunasan Utang/Piutang', 'pengeluaran'],
        ];

        foreach ($kategori as [$nama, $jenis]) {
            DB::table('categories')->updateOrInsert(
                ['user_id' => null, 'nama' => $nama, 'jenis' => $jenis],
                ['created_at' => now(), 'updated_at' => now()],
            );
        }
    }
}
