<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('mahasiswa')->after('email');
            $table->string('avatar', 10)->default('a1')->after('role');
            $table->decimal('batas_harian', 14, 2)->default(0)->after('avatar');
            $table->string('kode_hubung', 8)->nullable()->unique()->after('batas_harian');
            $table->string('kode_teman', 6)->nullable()->unique()->after('kode_hubung');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'avatar', 'batas_harian', 'kode_hubung', 'kode_teman']);
        });
    }
};