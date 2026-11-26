# ForeCash

Aplikasi web *early-warning* keuangan untuk mahasiswa: catat pengeluaran, **prediksi kapan saldo habis sebelum uang saku berikutnya datang**, dan biarkan orang tua ikut memantau.

Dibangun dengan **Laravel 12**, Blade, Tailwind CSS 4 (Vite), dan MySQL atau SQLite.

## Fitur

**Mahasiswa**
- **Beranda**: ringkasan saldo, batas pengeluaran harian, dan status keuangan (aman / waspada / kritis).
- **Prediksi saldo habis**: menghitung *burn rate* (rata-rata pengeluaran harian) lalu memperkirakan berapa hari saldo cukup dibanding jadwal pemasukan berikutnya (uang saku, gaji, dan sebagainya).
- **Transaksi**: pemasukan, pengeluaran, dan transfer antar-wallet, dengan kategori, foto struk, dan ekspor data. Saldo wallet tidak boleh minus.
- **Wallet**: tunai, bank, dan e-wallet.
- **Utang & patungan**: catat utang/piutang, bagi tagihan (split bill), dan teman lewat kode teman.
- **Rencana**: anggaran per kategori, target tabungan, dan rencana pengeluaran yang ikut dihitung dalam prediksi.
- **Laporan bulanan** per kategori.
- **Gamifikasi**: petualangan (peta, telur, pet), badge bertingkat, poin, level, dan tantangan mingguan.
- **ForeCash Assistant**: chatbot berbasis aturan.

**Orang tua**
- **Pantau anak**: hubungkan akun dengan kode hubung dari akun anak, lalu lihat saldo, status prediksi, ringkasan bulan ini, dan transaksi terbaru. Anak bisa membuat kode baru atau mencabut akses kapan saja.

**Admin**
- Statistik jumlah pengguna per peran dan daftar pengguna.

## Instalasi

Kebutuhan: PHP 8.2+, Composer, Node.js 20+ (disarankan 22).

```bash
git clone https://github.com/AudreyImanuella/ForeCash.git
cd ForeCash

composer install
cp .env.example .env
php artisan key:generate

# database (pilih salah satu, lihat bagian "Database")
touch database/database.sqlite

php artisan migrate --seed    # buat tabel + kategori bawaan + akun demo

npm install
npm run dev                   # biarkan jalan; buka terminal baru untuk langkah berikut
php artisan serve
```

Buka http://localhost:8000.

### Database

**SQLite** (default, tanpa instalasi tambahan): sudah diatur di `.env.example`.

**MySQL**: buat database `forecash`, lalu ubah di `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=forecash
DB_USERNAME=root
DB_PASSWORD=
```

## Akun demo

Dibuat oleh `php artisan migrate --seed`. Kata sandi semuanya `demo12345`.

| Peran | Email |
|---|---|
| Mahasiswa | `demo@forecash.id` |
| Mahasiswa (teman) | `teman@forecash.id` |
| Orang tua (terhubung ke Momo) | `ortu@forecash.id` |
| Admin | `admin@forecash.id` |

Akun demo dibuat ulang (dengan tanggal yang disesuaikan ke hari ini) setiap seeder dijalankan. **Jangan jalankan seeder ini di server publik**, karena kata sandinya diketahui umum.

## Menjalankan tes

```bash
php artisan test
```

Tes memakai SQLite di memori, jadi tidak menyentuh database aslimu. Tes mencakup logika prediksi (`Forecast`), penjaga saldo (`Saldo`), serta alur daftar dan hak akses per peran.

## Struktur singkat

```
app/Services/Forecast.php     inti prediksi: burn rate, runway, status
app/Support/                  Saldo, Laporan, Rencana, Gamify, Chatbot, Format
app/Http/Controllers/         satu controller per fitur
app/Http/Middleware/CekPeran  pembatas halaman per peran
database/seeders/             kategori bawaan + data demo
resources/views/              tampilan Blade
tests/                        tes Unit dan Feature
```

## Lisensi

[MIT](LICENSE)
