<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penawaran_mitra', function (Blueprint $table) {
            $table->id('id_penawaran');
            $table->foreignId('id_permintaan')
                  ->constrained('permintaan_service', 'id_permintaan')
                  ->cascadeOnDelete();
            $table->foreignId('id_mitra')
                  ->constrained('mitra_service', 'id_mitra')
                  ->cascadeOnDelete();
            $table->unsignedBigInteger('harga_penawaran');
            $table->unsignedTinyInteger('estimasi_hari')->nullable();
            $table->string('estimasi_waktu', 50);
            $table->text('catatan_pemeriksaan')->nullable();
            $table->text('alasan_penolakan')->nullable();
            $table->timestamp('waktu_penawaran')->nullable()->useCurrent();
            $table->enum('status_penawaran', ['Menunggu Keputusan', 'Disetujui', 'Ditolak Pelanggan', 'Kedaluwarsa'])
                  ->default('Menunggu Keputusan');

            $table->unique(['id_permintaan', 'id_mitra']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penawaran_mitra');
    }
};
