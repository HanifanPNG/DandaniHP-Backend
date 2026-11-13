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
            $table->dropForeign(['id_pelanggan']);
            $table->foreignId('id_pelanggan')
                  ->nullable()
                  ->change()
                  ->constrained('pelanggan', 'id_pelanggan')
                  ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifikasi', function (Blueprint $table) {
            $table->dropForeign(['id_pelanggan']);
            $table->foreignId('id_pelanggan')
                  ->constrained('pelanggan', 'id_pelanggan')
                  ->cascadeOnDelete()
                  ->change();
        });
    }
};
