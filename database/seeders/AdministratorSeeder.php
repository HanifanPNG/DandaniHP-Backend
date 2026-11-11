<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdministratorSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('administrator')->insert([
            'id_admin' => 1,
            'nama_lengkap' => 'Administrator Dandanin',
            'email' => 'admin@dandanin.id',
            'kata_sandi' => Hash::make('admin12345'),
        ]);
    }
}
