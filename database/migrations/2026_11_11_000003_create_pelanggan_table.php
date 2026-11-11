<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelanggan', function (Blueprint $table) {
            $table->id('id_pelanggan');
            $table->string('nama_lengkap', 100);
            $table->string('email', 100)->unique();
            $table->string('nomor_telepon', 20)->unique();
            $table->text('alamat')->nullable();
            $table->string('kata_sandi', 255);
            $table->string('foto_profil', 255)->nullable();
            $table->enum('status_verifikasi', ['Menunggu', 'Disetujui', 'Ditolak'])
                  ->default('Menunggu');
            $table->text('alasan_penolakan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggan');
    }
};
