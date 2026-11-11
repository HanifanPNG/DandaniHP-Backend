<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ulasan', function (Blueprint $table) {
            $table->id('id_ulasan');
            $table->foreignId('id_pesanan')
                  ->unique()
                  ->constrained('pesanan_aktif', 'id_pesanan')
                  ->cascadeOnDelete();
            $table->foreignId('id_pelanggan')
                  ->constrained('pelanggan', 'id_pelanggan')
                  ->cascadeOnDelete();
            $table->foreignId('id_mitra')
                  ->constrained('mitra_service', 'id_mitra')
                  ->cascadeOnDelete();
            $table->unsignedTinyInteger('rating_bintang');
            $table->text('komentar_ulasan')->nullable();
            $table->timestamp('waktu_ulasan')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ulasan');
    }
};
