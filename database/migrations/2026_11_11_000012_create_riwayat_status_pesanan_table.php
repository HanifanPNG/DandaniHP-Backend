<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_status_pesanan', function (Blueprint $table) {
            $table->id('id_riwayat');
            $table->foreignId('id_pesanan')
                  ->constrained('pesanan_aktif', 'id_pesanan')
                  ->cascadeOnDelete();
            $table->string('status', 50);
            $table->text('catatan')->nullable();
            $table->enum('aktor', ['Sistem', 'Mitra', 'Pelanggan'])->default('Sistem');
            $table->timestamp('waktu')->nullable()->useCurrent();

            $table->index(['id_pesanan', 'waktu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_status_pesanan');
    }
};
