<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mitra_service', function (Blueprint $table) {
            $table->id('id_mitra');
            $table->string('nama_pemilik', 100);
            $table->string('nama_usaha', 100);
            $table->string('email', 100)->unique();
            $table->string('kata_sandi', 255);
            $table->string('nomor_telepon', 20);
            $table->text('alamat_usaha')->nullable();
            $table->text('deskripsi_usaha')->nullable();
            $table->string('jam_operasional', 50)->nullable();
            $table->time('jam_buka')->nullable();
            $table->time('jam_tutup')->nullable();
            $table->string('foto_toko', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('rating_rata_rata', 3, 2)->default(0.00);
            $table->enum('status_verifikasi', ['Menunggu', 'Disetujui', 'Ditolak'])
                  ->default('Menunggu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mitra_service');
    }
};
