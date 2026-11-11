<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('galeri_mitra', function (Blueprint $table) {
            $table->id('id_galeri');
            $table->foreignId('id_mitra')
                  ->constrained('mitra_service', 'id_mitra')
                  ->cascadeOnDelete();
            $table->string('path', 255);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('galeri_mitra');
    }
};
