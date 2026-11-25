<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // pet yang sudah menetas di fitur Petualangan
        Schema::create('user_pets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('pet_id', 30);                 // kunci katalog pet
            $table->date('menetas_pada');
            $table->boolean('dipakai')->default(false);   // sedang menemani di peta & beranda
            $table->timestamps();

            $table->unique(['user_id', 'pet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_pets');
    }
};
