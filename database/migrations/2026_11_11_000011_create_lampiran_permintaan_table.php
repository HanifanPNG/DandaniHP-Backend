<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lampiran_permintaan', function (Blueprint $table) {
            $table->id('id_lampiran');
            $table->foreignId('id_permintaan')
                  ->constrained('permintaan_service', 'id_permintaan')
                  ->cascadeOnDelete();
            $table->string('path', 255);
            $table->timestamp('waktu_unggah')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lampiran_permintaan');
    }
};
