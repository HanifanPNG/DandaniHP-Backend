<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrator', function (Blueprint $table) {
            $table->id('id_admin');
            $table->string('nama_lengkap', 100);
            $table->string('email', 100)->unique();
            $table->string('kata_sandi', 255);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrator');
    }
};
