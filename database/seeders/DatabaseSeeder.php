<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdministratorSeeder::class,
            PelangganSeeder::class,
            MitraServiceSeeder::class,
            PermintaanServiceSeeder::class,
            NotifikasiSeeder::class,
        ]);
    }
}
