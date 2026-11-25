<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nama_teman', 100);
            $table->string('keterangan', 150);
            $table->decimal('nominal', 14, 2);
            $table->enum('arah', ['piutang', 'utang']);           // piutang = teman utang ke saya
            $table->enum('status', ['belum', 'lunas'])->default('belum');
            $table->date('jatuh_tempo')->nullable();
            // diisi kalau temannya juga pengguna ForeCash
            $table->foreignId('teman_id')->nullable()->constrained('users')->nullOnDelete();
            // baris pasangan di akun teman (tagihan split bill antar-akun)
            $table->unsignedBigInteger('pasangan_id')->nullable();
            // teman menandai lunas, transaksinya belum dicatat ke wallet saya
            $table->boolean('perlu_dicatat')->default(false);
            $table->timestamps();

            $table->foreign('pasangan_id')->references('id')->on('debts')->nullOnDelete();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
