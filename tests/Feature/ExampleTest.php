<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** Halaman awal untuk tamu mengarahkan ke halaman masuk (bukan 200). */
    public function test_halaman_awal_mengarahkan_tamu_ke_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
