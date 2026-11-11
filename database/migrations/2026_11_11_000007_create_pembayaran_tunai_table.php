<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran_tunai', function (Blueprint $table) {
            $table->id('id_pembayaran');
            $table->foreignId('id_pesanan')
                  ->unique()
                  ->constrained('pesanan_aktif', 'id_pesanan')
                  ->cascadeOnDelete();
            $table->unsignedBigInteger('jumlah_pembayaran');
            $table->timestamp('waktu_dibuat')->nullable()->useCurrent();
            $table->timestamp('waktu_konfirmasi_mitra')->nullable();
            $table->enum('status_pembayaran', ['Menunggu Konfirmasi', 'Diterima'])
                  ->default('Menunggu Konfirmasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_tunai');
    }
};
