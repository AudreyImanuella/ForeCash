<?php

namespace Tests\Unit;

use App\Support\Saldo;
use PHPUnit\Framework\TestCase;

/** Menguji pengaruh sebuah transaksi ke saldo wallet (Saldo::efek). */
class SaldoTest extends TestCase
{
    public function test_pemasukan_menambah_saldo_wallet(): void
    {
        $efek = Saldo::efek(['jenis' => 'pemasukan', 'nominal' => 50000, 'wallet_id' => 1, 'wallet_tujuan_id' => null]);

        $this->assertEquals([1 => 50000], $efek);
    }

    public function test_pengeluaran_mengurangi_saldo_wallet(): void
    {
        $efek = Saldo::efek(['jenis' => 'pengeluaran', 'nominal' => 50000, 'wallet_id' => 1, 'wallet_tujuan_id' => null]);

        $this->assertEquals([1 => -50000], $efek);
    }

    public function test_transfer_memindahkan_saldo_dari_asal_ke_tujuan(): void
    {
        $efek = Saldo::efek(['jenis' => 'transfer', 'nominal' => 100000, 'wallet_id' => 1, 'wallet_tujuan_id' => 2]);

        $this->assertEquals([1 => -100000, 2 => 100000], $efek);
    }

    public function test_tanpa_transaksi_tidak_ada_efek(): void
    {
        $this->assertSame([], Saldo::efek(null));
    }
}
