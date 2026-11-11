<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesanan_aktif', function (Blueprint $table) {
            $table->id('id_pesanan');

            $table->foreignId('id_penawaran')
                  ->unique()
                  ->constrained('penawaran_mitra', 'id_penawaran');
            $table->foreignId('id_permintaan')
                  ->unique()
                  ->constrained('permintaan_service', 'id_permintaan');
            $table->foreignId('id_pelanggan')
                  ->constrained('pelanggan', 'id_pelanggan');
            $table->foreignId('id_mitra')
                  ->constrained('mitra_service', 'id_mitra');

            $table->enum('status_pengerjaan', [
                'Masuk Antrean',
                'Sedang Diproses',
                'Service Selesai',
                'Menunggu Pembayaran Tunai',
                'Pembayaran Diterima',
                'Pesanan Selesai',
            ])->default('Masuk Antrean');

            $table->text('catatan_perkembangan')->nullable();
            $table->timestamp('waktu_dibuat')->nullable()->useCurrent();
            $table->timestamp('waktu_update_terakhir')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanan_aktif');
    }
};
