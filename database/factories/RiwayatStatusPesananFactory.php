<?php

namespace Database\Factories;

use App\Models\PesananAktif;
use App\Models\RiwayatStatusPesanan;
use App\Support\StatusPesanan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiwayatStatusPesanan>
 */
class RiwayatStatusPesananFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pesanan = PesananAktif::factory()->create();

        return [
            'id_pesanan'  => $pesanan->id_pesanan,
            'status'      => fake()->randomElement(StatusPesanan::DAFTAR_PENGERJAAN),
            'catatan'     => fake()->sentence(),
            'aktor'       => fake()->randomElement(['Mitra', 'Sistem']),
            'waktu'       => now(),
        ];
    }

    /**
     * Indicate that the riwayat is from a Mitra.
     */
    public function mitra(): static
    {
        return $this->state(fn (array $attributes) => [
            'aktor' => 'Mitra',
        ]);
    }

    /**
     * Indicate that the riwayat is from Sistem.
     */
    public function sistem(): static
    {
        return $this->state(fn (array $attributes) => [
            'aktor' => 'Sistem',
        ]);
    }
}