<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();   // tampilan memakai @vite; tes tidak perlu hasil build npm
    }

    public function test_halaman_login_bisa_dibuka(): void
    {
        $this->get('/masuk')->assertOk();
    }

    public function test_tamu_tidak_bisa_membuka_beranda(): void
    {
        $this->get('/beranda')->assertRedirect(route('login'));
    }

    public function test_daftar_mahasiswa_membuat_akun_dan_wallet_pertama(): void
    {
        $response = $this->post('/daftar', [
            'name' => 'Momo Tes',
            'email' => 'Momo@Tes.id',
            'password' => 'rahasia123',
            'role' => 'mahasiswa',
            'avatar' => 'a1',
        ]);

        $response->assertRedirect(route('beranda'));
        $user = User::where('email', 'momo@tes.id')->firstOrFail();   // email disimpan huruf kecil
        $this->assertSame('mahasiswa', $user->role);
        $this->assertCount(1, $user->wallets);
        $this->assertAuthenticatedAs($user);
    }

    public function test_daftar_menolak_kata_sandi_pendek(): void
    {
        $this->post('/daftar', [
            'name' => 'Momo Tes', 'email' => 'momo@tes.id', 'password' => 'pendek',
            'role' => 'mahasiswa', 'avatar' => 'a1',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_orang_tua_tidak_bisa_membuka_halaman_mahasiswa(): void
    {
        $ortu = User::factory()->create(['role' => 'orangtua']);

        $this->actingAs($ortu)->get('/beranda')->assertRedirect(route('pantau'));
    }
}
