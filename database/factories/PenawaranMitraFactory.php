<?php

namespace Database\Factories;

use App\Models\MitraService;
use App\Models\PermintaanService;
use App\Models\PenawaranMitra;
use App\Support\StatusPesanan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PenawaranMitra>
 */
class PenawaranMitraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_permintaan'    => PermintaanService::factory(),
            'id_mitra'         => MitraService::factory(),
            'harga_penawaran'  => fake()->randomElement([15000, 25000, 35000, 50000, 75000, 100000, 150000, 200000, 250000, 300000, 350000]),
            'estimasi_hari'    => fake()->numberBetween(1, 5),
            'estimasi_waktu'   => fake()->randomElement(['1 hari', '2 hari', '3 hari', '4 hari', '5 hari', '1 minggu']),
            'waktu_penawaran'  => now(),
            'status_penawaran' => StatusPesanan::PENAWARAN_MENUNGGU,
        ];
    }

    /**
     * Indicate that the penawaran is approved.
     */
    public function disetujui(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_penawaran' => StatusPesanan::PENAWARAN_DISETUJUI,
        ]);
    }

    /**
     * Indicate that the penawaran is rejected by customer.
     */
    public function ditolak(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_penawaran' => StatusPesanan::PENAWARAN_DITOLAK,
        ]);
    }

    /**
     * Indicate that the penawaran has expired.
     */
    public function kadaluarsa(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_penawaran' => StatusPesanan::PENAWARAN_KEDALUWARSA,
        ]);
    }
}