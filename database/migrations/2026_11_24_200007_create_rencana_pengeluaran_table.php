<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // pengeluaran besar yang sudah direncanakan (bayar kos, UKT, kado), ikut dihitung di Prediksi
        Schema::create('rencana_pengeluaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nama', 100);
            $table->decimal('nominal', 14, 2);
            $table->date('tanggal');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->boolean('berulang')->default(false);   // true = muncul lagi bulan depan
            $table->enum('status', ['rencana', 'selesai'])->default('rencana');
            $table->timestamps();

            $table->index(['user_id', 'status', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rencana_pengeluaran');
    }
};
