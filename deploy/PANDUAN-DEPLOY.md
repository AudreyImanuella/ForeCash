# Panduan Deploy ForeCash ke InfinityFree

InfinityFree tidak punya SSH, Composer, atau `php artisan`. Karena itu semua disiapkan di laptop, lalu diunggah.

## A. Di panel InfinityFree (sekali saja)

1. Daftar di infinityfree.com, lalu **Create Account** dan pilih subdomain gratis (misalnya `forecash.rf.gd`).
2. Buka **Control Panel → MySQL Databases**, lalu buat database baru. Catat datanya; kata sandi database ada di halaman akun hosting:
   - **MySQL Host Name** (misalnya `sqlXXX.infinityfree.com`);
   - **Database Name** (misalnya `if0_XXXX_forecash`);
   - **MySQL User Name** (misalnya `if0_XXXX`).
3. Opsional: aktifkan SSL gratis di menu **SSL Certificates**, supaya bisa dibuka lewat `https://`.

## B. Di laptop: buat paket

1. Supaya data demonya bersih, reset database lokal dulu:
   ```powershell
   php artisan migrate:fresh --seed
   ```
2. Buat paket (hentikan `npm run dev` dulu kalau sedang jalan):
   ```powershell
   powershell -ExecutionPolicy Bypass -File deploy\buat-paket.ps1
   ```
   Saat diminta kata sandi MySQL lokal, tekan Enter kalau XAMPP-mu tanpa kata sandi. Hasilnya dua file:
   - `deploy\forecash-upload.zip`: kode, `vendor`, dan `public/build`;
   - `deploy\forecash-database.sql`: isi database.
3. Buat APP_KEY baru khusus produksi. Perintah ini hanya menampilkan kunci dan tidak mengubah `.env`:
   ```powershell
   php artisan key:generate --show
   ```

## C. Unggah ke hosting

1. **Database:** buka **phpMyAdmin** dari panel, pilih database tadi, lalu **Import** file `forecash-database.sql`.
2. **File:** buka **File Manager** (atau pakai FileZilla dengan data FTP dari panel). Masuk ke folder `htdocs`, hapus file bawaan (`index2.html` dan sejenisnya), unggah `forecash-upload.zip`, lalu **Extract** di dalam `htdocs`.
   Pastikan hasil ekstrak **tidak** masuk ke folder baru `htdocs/htdocs` (isi tujuan ekstrak dengan `/htdocs`). Kalau terlanjur, pindahkan (Move) isinya ke `htdocs`.
   Kalau File Manager tidak bisa mengekstrak, ekstrak ZIP di laptop dan unggah isi foldernya lewat FileZilla.
   Hasil akhirnya harus begini: di dalam `htdocs` langsung ada `.htaccess`, `app/`, `public/`, `vendor/`, dan seterusnya.
3. **Konfigurasi produksi:** File Manager InfinityFree tidak bisa membuat file bernama `.env`, jadi buat file **`env.txt`** di `htdocs` (sejajar dengan `app` dan `vendor`, jangan di-rename). Laravel otomatis membacanya kalau `.env` tidak ada (lihat `bootstrap/app.php`). Isi sendiri:
   ```
   APP_NAME=ForeCash
   APP_ENV=production
   APP_KEY=        <- tempel hasil key:generate --show
   APP_DEBUG=false
   APP_URL=https://forecash.rf.gd   <- subdomain kamu

   APP_LOCALE=id
   LOG_CHANNEL=single
   LOG_LEVEL=error

   DB_CONNECTION=mysql
   DB_HOST=        <- MySQL Host Name
   DB_PORT=3306
   DB_DATABASE=    <- Database Name
   DB_USERNAME=    <- MySQL User Name
   DB_PASSWORD=    <- kata sandi database hosting

   SESSION_DRIVER=database
   CACHE_STORE=database
   QUEUE_CONNECTION=sync
   FILESYSTEM_DISK=local
   MAIL_MAILER=log

   # chatbot AI (opsional, key dari aistudio.google.com)
   GEMINI_API_KEY=
   GEMINI_MODEL=gemini-3.5-flash-lite,gemini-flash-lite-latest,gemini-3.8-flash
   ```
   Cek keamanan: `http://subdomainmu/env.txt` harus menampilkan 404.
4. Buka subdomainnya di browser, lalu login dengan `demo@forecash.id` / `demo12345`.

## D. Update setelah ada perubahan kode

Jalankan perintah berikut. Kalau database tidak berubah, `-TanpaDatabase` membuat langkah ekspor dilewati.

```powershell
powershell -ExecutionPolicy Bypass -File deploy\buat-paket.ps1 -TanpaDatabase
```

Lalu unggah dan ekstrak lagi ZIP-nya. `env.txt` di hosting tidak akan tertimpa karena tidak ikut di ZIP. Setelah itu hapus isi `storage/framework/views/` di hosting supaya tampilan lama tidak tersimpan.

## Kalau ada masalah

| Gejala | Penyebab biasanya |
|---|---|
| Error 500 | `.env` belum ada atau `APP_KEY` kosong. Ubah sementara `APP_DEBUG=true` untuk melihat pesan aslinya, lalu kembalikan ke `false`. |
| "SQLSTATE … Connection refused" | `DB_HOST` diisi `localhost`. Harus pakai MySQL Host Name dari panel. |
| Tampilan tanpa CSS | Folder `public/build` tidak terunggah, atau `APP_URL` salah (http/https). |
| Error 500 "Please provide a valid cache path" | Folder `storage/framework/views`, `sessions`, atau `cache/data` hilang. Buat manual di File Manager. |
| "No application encryption key" | `env.txt` tidak ada atau `APP_KEY` kosong. Cek dengan membuka `env.txt` di File Manager. |
| Chatbot "tidak bisa terhubung ke server" | Firewall InfinityFree memblokir alamat yang mengandung kata "bot". Chatbot sudah memakai alamat `/asisten`; jangan diganti kembali ke `/chatbot`. |
| Chatbot selalu "AI sedang sibuk" | Lihat baris `Gemini ... gagal` di `storage/logs/laravel.log` (503 = model penuh, 429 = kuota habis, 404 = nama model salah). |
| Error 419 saat login | `APP_URL` tidak sama dengan alamat yang dibuka. Buka lewat alamat yang persis sama, termasuk https. |
