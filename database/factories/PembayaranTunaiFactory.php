<?php

namespace Database\Factories;

use App\Models\PembayaranTunai;
use App\Models\PesananAktif;
use App\Support\StatusPesanan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PembayaranTunai>
 */
class PembayaranTunaiFactory extends Factory
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
            'id_pesanan'             => $pesanan->id_pesanan,
            'jumlah_pembayaran'      => fake()->numberBetween(25000, 500000),
            'status_pembayaran'      => StatusPesanan::PEMBAYARAN_MENUNGGU,
            'waktu_dibuat'           => now(),
            'waktu_konfirmasi_mitra' => null,
        ];
    }

    /**
     * Indicate that the payment is waiting for confirmation.
     */
    public function menunggu(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_pembayaran' => StatusPesanan::PEMBAYARAN_MENUNGGU,
        ]);
    }

    /**
     * Indicate that the payment has been received.
     */
    public function diterima(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_pembayaran'      => StatusPesanan::PEMBAYARAN_DITERIMA,
            'waktu_konfirmasi_mitra' => now(),
        ]);
    }
}