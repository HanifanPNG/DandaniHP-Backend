<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id('id_notifikasi');
            $table->foreignId('id_pelanggan')
                  ->constrained('pelanggan', 'id_pelanggan')
                  ->cascadeOnDelete();
            $table->enum('tipe', ['Penawaran', 'Progres', 'Pembayaran', 'Selesai', 'Ulasan']);
            $table->string('judul', 100);
            $table->text('pesan');
            $table->foreignId('id_pesanan')
                  ->nullable()
                  ->constrained('pesanan_aktif', 'id_pesanan')
                  ->nullOnDelete();
            $table->boolean('dibaca')->default(false);
            $table->timestamp('waktu_dibuat')->nullable()->useCurrent();

            $table->index(['id_pelanggan', 'dibaca', 'waktu_dibuat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
