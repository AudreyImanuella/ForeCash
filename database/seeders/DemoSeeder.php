<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Data demo ForeCash (sama dengan versi Next.js/Supabase).
 *
 * Akun (kata sandi semuanya: demo12345):
 *   demo@forecash.id   Momo Mahasiswa  (mahasiswa, kode teman MOMO26, kode hubung DEMO2026)
 *   teman@forecash.id  Andi Pratama    (mahasiswa, kode teman ANDI26)
 *   ortu@forecash.id   Ibu Momo        (orang tua, terhubung ke Momo)
 *   admin@forecash.id  Admin ForeCash  (admin)
 *
 * Aman dijalankan ulang: akun demo dihapus dulu (datanya ikut terhapus), lalu dibuat lagi.
 * Tanggal dihitung dari hari ini (WIB), jadi demo selalu terlihat baru.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $hari = Carbon::now('Asia/Jakarta')->startOfDay();
        $tgl = fn (int $selisih) => $hari->copy()->addDays($selisih)->toDateString();
        $sekarang = now();
        $waktu = ['created_at' => $sekarang, 'updated_at' => $sekarang];

        DB::transaction(function () use ($tgl, $waktu) {
            // ---------- hapus akun demo lama (wallet, transaksi, dll ikut terhapus)
            $email = ['demo@forecash.id', 'teman@forecash.id', 'ortu@forecash.id', 'admin@forecash.id'];
            $lama = DB::table('users')->whereIn('email', $email)->pluck('id');
            DB::table('transactions')->whereIn('user_id', $lama)->delete();   // transaksi dulu (wallet dikunci restrict)
            DB::table('users')->whereIn('id', $lama)->delete();

            // ---------- akun
            $pengguna = fn (array $data) => DB::table('users')->insertGetId($data + [
                'password' => Hash::make('demo12345'),
                'email_verified_at' => now(),
            ] + $waktu);

            $mhs = $pengguna(['name' => 'Momo Mahasiswa', 'email' => 'demo@forecash.id', 'role' => 'mahasiswa',
                'avatar' => 'a2', 'batas_harian' => 45000, 'kode_hubung' => 'DEMO2026', 'kode_teman' => 'MOMO26']);
            $tmn = $pengguna(['name' => 'Andi Pratama', 'email' => 'teman@forecash.id', 'role' => 'mahasiswa',
                'avatar' => 'a3', 'kode_teman' => 'ANDI26']);
            $ortu = $pengguna(['name' => 'Ibu Momo', 'email' => 'ortu@forecash.id', 'role' => 'orangtua', 'avatar' => 'a4']);
            $pengguna(['name' => 'Admin ForeCash', 'email' => 'admin@forecash.id', 'role' => 'admin', 'avatar' => 'a1']);

            // ---------- wallet
            $wallet = fn (int $user, string $nama, string $tipe, int $saldo) => DB::table('wallets')->insertGetId(
                ['user_id' => $user, 'nama' => $nama, 'tipe' => $tipe, 'saldo_awal' => $saldo] + $waktu);
            $wUtama = $wallet($mhs, 'Utama', 'tunai', 250000);
            $wBca = $wallet($mhs, 'BCA', 'bank', 0);
            $wGopay = $wallet($mhs, 'GoPay', 'ewallet', 0);
            $wallet($tmn, 'Utama', 'tunai', 300000);

            // ---------- kategori bawaan
            $kat = fn (string $nama, string $jenis = 'pengeluaran') => DB::table('categories')
                ->whereNull('user_id')->where('nama', $nama)->where('jenis', $jenis)->value('id');
            $kMakan = $kat('Makan & Minum');
            $kTransport = $kat('Transportasi');
            $kKos = $kat('Kos / Sewa');
            $kKuliah = $kat('Kebutuhan Kuliah');
            $kHiburan = $kat('Hiburan');
            $kSaku = $kat('Uang Saku', 'pemasukan');

            // ---------- transaksi Momo
            $tx = [
                [$wBca,   null,    $kSaku,      'pemasukan',   1000000, 'Uang saku bulanan',   -13],
                [$wBca,   $wGopay, null,        'transfer',     300000, 'Isi GoPay',           -12],
                [$wGopay, null,    $kMakan,     'pengeluaran',   85000, 'Makan bareng teman',  -12],
                [$wBca,   null,    $kKos,       'pengeluaran',  250000, 'Bayar kos (cicilan)', -13],
                [$wGopay, null,    $kTransport, 'pengeluaran',   40000, 'Ojek online',          -9],
                [$wUtama, null,    $kMakan,     'pengeluaran',   60000, 'Makan kantin',         -8],
                [$wGopay, null,    $kHiburan,   'pengeluaran',   75000, 'Nonton bioskop',       -6],
                [$wUtama, null,    $kKuliah,    'pengeluaran',   35000, 'Print tugas',          -5],
                [$wGopay, null,    $kMakan,     'pengeluaran',   55000, 'Kopi & camilan',       -3],
                [$wUtama, null,    $kTransport, 'pengeluaran',   30000, 'Bensin',               -2],
                [$wUtama, null,    $kMakan,     'pengeluaran',   45000, 'Makan siang',          -1],
            ];
            foreach ($tx as [$w, $tujuan, $k, $jenis, $nominal, $ket, $hariKe]) {
                DB::table('transactions')->insert([
                    'user_id' => $mhs, 'wallet_id' => $w, 'wallet_tujuan_id' => $tujuan, 'category_id' => $k,
                    'jenis' => $jenis, 'nominal' => $nominal, 'keterangan' => $ket, 'tanggal' => $tgl($hariKe),
                ] + $waktu);
            }

            // ---------- jadwal pemasukan & utang
            DB::table('income_schedules')->insert(['user_id' => $mhs, 'sumber' => 'Uang saku dari orang tua',
                'nominal' => 1000000, 'tanggal_perkiraan' => $tgl(13)] + $waktu);

            DB::table('debts')->insert([
                ['user_id' => $mhs, 'nama_teman' => 'Andi', 'keterangan' => 'Patungan makan', 'nominal' => 28000,
                    'arah' => 'piutang', 'jatuh_tempo' => $tgl(3)] + $waktu,
                ['user_id' => $mhs, 'nama_teman' => 'Sasa', 'keterangan' => 'Pinjam ongkos', 'nominal' => 20000,
                    'arah' => 'utang', 'jatuh_tempo' => null] + $waktu,
            ]);

            // ---------- rencana: anggaran, target tabungan, rencana pengeluaran
            foreach ([[$kMakan, 600000], [$kTransport, 200000], [$kHiburan, 100000], [$kKuliah, 150000]] as [$k, $batas]) {
                DB::table('anggaran')->insert(['user_id' => $mhs, 'category_id' => $k, 'batas' => $batas] + $waktu);
            }

            $tLaptop = DB::table('target_tabungan')->insertGetId(['user_id' => $mhs, 'nama' => 'Laptop baru',
                'target' => 8000000, 'terkumpul' => 1500000, 'tenggat' => $tgl(200)] + $waktu);
            $tKonser = DB::table('target_tabungan')->insertGetId(['user_id' => $mhs, 'nama' => 'Tiket konser',
                'target' => 750000, 'terkumpul' => 450000, 'tenggat' => $tgl(45)] + $waktu);
            foreach ([[$tLaptop, 1000000, -20], [$tLaptop, 500000, -6], [$tKonser, 300000, -13], [$tKonser, 150000, -2]] as [$t, $n, $h]) {
                DB::table('setoran_tabungan')->insert(['user_id' => $mhs, 'target_id' => $t, 'nominal' => $n, 'tanggal' => $tgl($h)] + $waktu);
            }

            DB::table('rencana_pengeluaran')->insert([
                ['user_id' => $mhs, 'nama' => 'Patungan kado ultah Sasa', 'nominal' => 50000, 'tanggal' => $tgl(5),
                    'category_id' => null, 'berulang' => false] + $waktu,
                ['user_id' => $mhs, 'nama' => 'Bayar kos', 'nominal' => 1200000, 'tanggal' => $tgl(17),
                    'category_id' => $kKos, 'berulang' => true] + $waktu,
                ['user_id' => $mhs, 'nama' => 'Cicilan UKT', 'nominal' => 500000, 'tanggal' => $tgl(25),
                    'category_id' => $kKuliah, 'berulang' => false] + $waktu,
            ]);

            // ---------- pet
            DB::table('user_pets')->insert([
                ['user_id' => $mhs, 'pet_id' => 'celeng', 'menetas_pada' => $tgl(-10), 'dipakai' => true] + $waktu,
                ['user_id' => $mhs, 'pet_id' => 'mochi', 'menetas_pada' => $tgl(-5), 'dipakai' => false] + $waktu,
            ]);

            // ---------- teman: Momo <-> Andi + satu tagihan split bill yang saling terhubung
            DB::table('teman')->insert([
                ['user_id' => $mhs, 'teman_id' => $tmn] + $waktu,
                ['user_id' => $tmn, 'teman_id' => $mhs] + $waktu,
            ]);
            $tagihMomo = DB::table('debts')->insertGetId(['user_id' => $mhs, 'nama_teman' => 'Andi Pratama',
                'keterangan' => 'Split bill: pizza malam', 'nominal' => 35000, 'arah' => 'piutang',
                'jatuh_tempo' => $tgl(4), 'teman_id' => $tmn] + $waktu);
            $tagihAndi = DB::table('debts')->insertGetId(['user_id' => $tmn, 'nama_teman' => 'Momo Mahasiswa',
                'keterangan' => 'Split bill: pizza malam', 'nominal' => 35000, 'arah' => 'utang',
                'jatuh_tempo' => $tgl(4), 'teman_id' => $mhs, 'pasangan_id' => $tagihMomo] + $waktu);
            DB::table('debts')->where('id', $tagihMomo)->update(['pasangan_id' => $tagihAndi]);

            // ---------- orang tua memantau Momo
            DB::table('parent_links')->insert(['orangtua_id' => $ortu, 'anak_id' => $mhs] + $waktu);
        });
    }
}
