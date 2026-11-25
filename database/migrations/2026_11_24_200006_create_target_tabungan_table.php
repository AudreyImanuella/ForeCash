<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('target_tabungan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nama', 80);
            $table->decimal('target', 14, 2);
            $table->decimal('terkumpul', 14, 2)->default(0);
            $table->date('tenggat')->nullable();
            $table->date('tercapai_pada')->nullable();
            $table->timestamps();
        });

        // riwayat setoran (dipakai badge "Nabung Rutin")
        Schema::create('setoran_tabungan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_id')->constrained('target_tabungan')->cascadeOnDelete();
            $table->decimal('nominal', 14, 2);
            $table->date('tanggal');
            $table->timestamps();

            $table->index(['user_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setoran_tabungan');
        Schema::dropIfExists('target_tabungan');
    }
};
