<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PelangganSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('pelanggan')->insert([
            'id_pelanggan' => 1,
            'nama_lengkap' => 'Budi Santoso',
            'email' => 'budi.santoso@example.com',
            'nomor_telepon' => '081234567890',
            'alamat' => 'Jl. Karangsentul No. 10, Purbalingga',
            'kata_sandi' => Hash::make('password123'),
            'foto_profil' => null,
            'status_verifikasi' => 'Disetujui',
            'alasan_penolakan' => null,
        ]);
    }
}
