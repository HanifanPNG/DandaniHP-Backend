<?php

namespace Database\Factories;

use App\Models\Pelanggan;
use App\Models\PermintaanService;
use App\Support\StatusPesanan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PermintaanService>
 */
class PermintaanServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode_pesanan'        => 'SRV-2026-'.fake()->unique()->numerify('00000'),
            'id_pelanggan'        => Pelanggan::factory(),
            'merek_hp'            => fake()->randomElement(['Samsung', 'Xiaomi', 'OPPO', 'Vivo', 'Apple', 'Realme', 'Motorola']),
            'model_hp'            => fake()->regexify('[A-Z]{1}[0-9]{3,4}[A-Z]?'),
            'kategori_kerusakan'  => fake()->randomElement(['Layar', 'Baterai', 'Volume/Tombol', 'Kamera', 'Software', 'Audio', 'Lainnya']),
            'deskripsi_kerusakan' => 'Kerusakan ' . fake()->sentence(),
            'status_permintaan'   => StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN,
            'waktu_dibuat'        => now(),
        ];
    }

    /**
     * Indicate that the permintaan is looking for offers.
     */
    public function mencariPenawaran(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_permintaan' => StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN,
        ]);
    }

    /**
     * Indicate that the permintaan is completed in auction.
     */
    public function selesiDilelang(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_permintaan' => StatusPesanan::PERMINTAAN_SELESAI_DILELANG,
        ]);
    }

    /**
     * Indicate that the permintaan is cancelled.
     */
    public function dibatalkan(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_permintaan' => StatusPesanan::PERMINTAAN_DIBATALKAN,
        ]);
    }
}