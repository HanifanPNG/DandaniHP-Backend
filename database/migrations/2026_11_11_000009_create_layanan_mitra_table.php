<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layanan_mitra', function (Blueprint $table) {
            $table->id('id_layanan');
            $table->foreignId('id_mitra')
                  ->constrained('mitra_service', 'id_mitra')
                  ->cascadeOnDelete();
            $table->string('nama_layanan', 100);

            $table->unique(['id_mitra', 'nama_layanan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('layanan_mitra');
    }
};
