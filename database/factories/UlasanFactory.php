<?php

namespace Database\Factories;

use App\Models\MitraService;
use App\Models\Pelanggan;
use App\Models\PesananAktif;
use App\Models\Ulasan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ulasan>
 */
class UlasanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pesanan = PesananAktif::factory()->selesai()->create();

        return [
            'id_pesanan'      => $pesanan->id_pesanan,
            'id_pelanggan'    => $pesanan->id_pelanggan,
            'id_mitra'        => $pesanan->id_mitra,
            'rating_bintang'  => fake()->numberBetween(1, 5),
            'komentar_ulasan' => fake()->optional()->sentence(),
            'waktu_ulasan'    => now(),
        ];
    }

    /**
     * Indicate that the ulasan has a 5-star rating.
     */
    public function empatBintang(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating_bintang' => 4,
        ]);
    }

    /**
     * Indicate that the ulasan has a 5-star rating.
     */
    public function limaBintang(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating_bintang' => 5,
        ]);
    }
}