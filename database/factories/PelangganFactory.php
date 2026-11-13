<?php

namespace Database\Factories;

use App\Models\Pelanggan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Pelanggan>
 */
class PelangganFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_lengkap'      => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'nomor_telepon'     => fake()->unique()->numerify('08########'),
            'alamat'            => fake()->address(),
            'kata_sandi'        => static::$password ??= Hash::make('password'),
            'status_verifikasi' => fake()->randomElement(['Menunggu', 'Disetujui', 'Ditolak']),
            'alasan_penolakan'  => null,
        ];
    }

    /**
     * Indicate that the pelanggan should be verified.
     */
    public function terverifikasi(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_verifikasi' => 'Disetujui',
        ]);
    }

    /**
     * Indicate that the pelanggan should be unverified.
     */
    public function menunggu(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_verifikasi' => 'Menunggu',
        ]);
    }
}