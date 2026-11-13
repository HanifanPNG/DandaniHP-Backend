<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notifikasi', function (Blueprint $table) {
            $table->foreignId('id_mitra')
                  ->nullable()
                  ->after('id_pelanggan')
                  ->constrained('mitra_service', 'id_mitra')
                  ->cascadeOnDelete();

            $table->index(['id_mitra', 'dibaca', 'waktu_dibuat']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifikasi', function (Blueprint $table) {
            $table->dropForeign(['id_mitra']);
            $table->dropColumn('id_mitra');
            $table->dropIndex(['id_mitra', 'dibaca', 'waktu_dibuat']);
        });
    }
};
