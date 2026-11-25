<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Isi database: kategori bawaan dulu, lalu akun & data demo.
     * Jalankan dengan: php artisan db:seed
     */
    public function run(): void
    {
        $this->call([
            KategoriSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
