<?php

namespace Database\Factories;

use App\Models\Notifikasi;
use App\Models\Pelanggan;
use App\Models\PesananAktif;
use App\Support\TipeNotifikasi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notifikasi>
 */
class NotifikasiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pelanggan = Pelanggan::factory()->create();

        return [
            'id_pelanggan'   => $pelanggan->id_pelanggan,
            'id_mitra'       => null,
            'tipe'           => fake()->randomElement(TipeNotifikasi::SEMUA),
            'judul'          => fake()->sentence(),
            'pesan'          => fake()->sentence(),
            'id_pesanan'     => PesananAktif::factory(),
            'dibaca'         => false,
            'waktu_dibuat'   => now(),
        ];
    }

    /**
     * Indicate that the notifikasi is unread.
     */
    public function belumDibaca(): static
    {
        return $this->state(fn (array $attributes) => [
            'dibaca' => false,
        ]);
    }

    /**
     * Indicate that the notifikasi is read.
     */
    public function dibaca(): static
    {
        return $this->state(fn (array $attributes) => [
            'dibaca' => true,
        ]);
    }

    /**
     * Indicate that the notifikasi is for a penawaran type.
     */
    public function tipePenawaran(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipe' => TipeNotifikasi::PENAWARAN,
        ]);
    }

    /**
     * Indicate that the notifikasi is for a progres type.
     */
    public function tipeProgres(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipe' => TipeNotifikasi::PROGRES,
        ]);
    }

    /**
     * Indicate that the notifikasi is for a pembayaran type.
     */
    public function tipePembayaran(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipe' => TipeNotifikasi::PEMBAYARAN,
        ]);
    }

    /**
     * Indicate that the notifikasi is for a selesai type.
     */
    public function tipeSelesai(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipe' => TipeNotifikasi::SELESAI,
        ]);
    }

    /**
     * Indicate that the notifikasi is for an ulasan type.
     */
    public function tipeUlasan(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipe' => TipeNotifikasi::ULASAN,
        ]);
    }
}