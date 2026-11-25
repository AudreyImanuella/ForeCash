<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // satu baris = orang tua X boleh melihat data anak Y
        Schema::create('parent_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orangtua_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('anak_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['orangtua_id', 'anak_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_links');
    }
};
