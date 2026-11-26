<?php

namespace Tests\Unit;

use App\Services\Forecast;
use PHPUnit\Framework\TestCase;

/**
 * Menguji inti ForeCash: burn rate, runway (berapa hari saldo cukup), dan status peringatan.
 * Forecast::hitung() "murni" (tanpa database), jadi tes ini cepat dan tidak butuh DB.
 */
class ForecastTest extends TestCase
{
    private const HARI_INI = '2026-11-26';

    /** Pengeluaran 400.000 pada 23 Nov -> user baru dihitung 4 hari -> burn rate 100.000/hari. */
    private function pengeluaranContoh(): array
    {
        return [['tanggal' => '2026-11-23', 'nominal' => 400000.0]];
    }

    private function jadwal(string $tanggal, int $id = 1): array
    {
        return ['id' => $id, 'sumber' => 'Orang tua', 'nominal' => 1000000.0,
            'tanggal_perkiraan' => $tanggal, 'status' => 'menunggu'];
    }

    public function test_tanpa_pengeluaran_burn_nol_dan_status_aman(): void
    {
        $f = Forecast::hitung(self::HARI_INI, 1000000, null, [], []);

        $this->assertSame(0.0, (float) $f['burn']);
        $this->assertNull($f['runway']);
        $this->assertNull($f['habis']);
        $this->assertSame('aman', $f['status']);
    }

    public function test_burn_rate_dan_tanggal_saldo_habis(): void
    {
        $f = Forecast::hitung(self::HARI_INI, 500000, '2026-11-23', $this->pengeluaranContoh(), []);

        $this->assertSame(4, $f['hari']);
        $this->assertEquals(100000, $f['burn']);
        $this->assertSame(5, $f['runway']);          // 500.000 / 100.000
        $this->assertSame('2026-12-01', $f['habis']);  // 26 Nov + 5 hari
    }

    public function test_status_aman_kalau_saldo_cukup_sampai_uang_berikutnya(): void
    {
        $f = Forecast::hitung(self::HARI_INI, 500000, '2026-11-23', $this->pengeluaranContoh(),
            [$this->jadwal('2026-11-30')]);   // 4 hari lagi, saldo cukup 5 hari

        $this->assertSame('aman', $f['status']);
        $this->assertSame(4, $f['sisaHari']);
        $this->assertEquals(125000, $f['batasAman']);   // 500.000 / 4 hari
    }

    public function test_status_waspada_kalau_kurang_satu_sampai_tiga_hari(): void
    {
        $f = Forecast::hitung(self::HARI_INI, 500000, '2026-11-23', $this->pengeluaranContoh(),
            [$this->jadwal('2026-12-02')]);   // 6 hari lagi, saldo cukup 5 hari -> kurang 1

        $this->assertSame('waspada', $f['status']);
    }

    public function test_status_kritis_kalau_kurang_lebih_dari_tiga_hari(): void
    {
        $f = Forecast::hitung(self::HARI_INI, 500000, '2026-11-23', $this->pengeluaranContoh(),
            [$this->jadwal('2026-12-05')]);   // 9 hari lagi, saldo cukup 5 hari -> kurang 4

        $this->assertSame('kritis', $f['status']);
    }

    public function test_saldo_nol_langsung_kritis(): void
    {
        $f = Forecast::hitung(self::HARI_INI, 0, null, [], []);

        $this->assertSame('kritis', $f['status']);
    }

    public function test_rencana_pengeluaran_sebelum_uang_datang_mengurangi_saldo_bebas(): void
    {
        $f = Forecast::hitung(self::HARI_INI, 500000, '2026-11-23', $this->pengeluaranContoh(),
            [$this->jadwal('2026-11-30')],
            [['tanggal' => '2026-11-28', 'nominal' => 450000.0]]);

        $this->assertEquals(450000, $f['rencanaDulu']);
        $this->assertSame(0, $f['runway']);   // saldo bebas tinggal 50.000, kurang dari 1 hari burn
        $this->assertSame('kritis', $f['status']);
    }

    public function test_rencana_setelah_uang_datang_tidak_dihitung(): void
    {
        $f = Forecast::hitung(self::HARI_INI, 500000, '2026-11-23', $this->pengeluaranContoh(),
            [$this->jadwal('2026-11-30')],
            [['tanggal' => '2026-12-10', 'nominal' => 450000.0]]);

        $this->assertEquals(0, $f['rencanaDulu']);
        $this->assertSame('aman', $f['status']);
    }

    public function test_jadwal_terlambat_membuat_status_waspada(): void
    {
        $f = Forecast::hitung(self::HARI_INI, 1000000, null, [], [$this->jadwal('2026-11-20')]);

        $this->assertCount(1, $f['terlambat']);
        $this->assertSame('waspada', $f['status']);
    }

    public function test_pembantu_tanggal(): void
    {
        $this->assertSame('2027-01-02', Forecast::tambahHari('2026-12-30', 3));
        $this->assertSame('2026-11-23', Forecast::tambahHari('2026-11-26', -3));
        $this->assertSame(9, Forecast::selisihHari('2026-11-26', '2026-12-05'));
    }
}
