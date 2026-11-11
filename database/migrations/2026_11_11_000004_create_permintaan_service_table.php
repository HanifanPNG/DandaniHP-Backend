<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_service', function (Blueprint $table) {
            $table->id('id_permintaan');
            $table->string('kode_pesanan', 30)->unique();
            $table->foreignId('id_pelanggan')
                  ->constrained('pelanggan', 'id_pelanggan')
                  ->cascadeOnDelete();
            $table->string('merek_hp', 50);
            $table->string('model_hp', 50);
            $table->string('kategori_kerusakan', 100);
            $table->text('deskripsi_kerusakan');
            $table->string('lama_kerusakan', 50)->nullable();
            $table->string('kondisi_hp', 100)->nullable();
            $table->text('catatan_tambahan')->nullable();
            $table->timestamp('waktu_dibuat')->nullable()->useCurrent();
            $table->enum('status_permintaan', ['Mencari Penawaran', 'Selesai Dilelang', 'Dibatalkan'])
                  ->default('Mencari Penawaran');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_service');
    }
};
