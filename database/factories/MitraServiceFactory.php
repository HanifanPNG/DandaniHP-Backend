<?php

namespace Database\Factories;

use App\Models\MitraService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<MitraService>
 */
class MitraServiceFactory extends Factory
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
            'nama_pemilik'      => fake()->name(),
            'nama_usaha'        => fake()->company(),
            'email'             => fake()->unique()->safeEmail(),
            'kata_sandi'        => static::$password ??= Hash::make('password'),
            'nomor_telepon'     => fake()->unique()->numerify('08########'),
            'alamat_usaha'      => fake()->address(),
            'status_verifikasi' => fake()->randomElement(['Menunggu', 'Disetujui', 'Ditolak', 'Non Aktif']),
            'rating_rata_rata'  => fake()->optional()->randomFloat(2, 1, 5),
        ];
    }

    /**
     * Indicate that the mitra should be verified.
     */
    public function terverifikasi(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_verifikasi' => 'Disetujui',
            'rating_rata_rata'  => 4.5,
        ]);
    }

    /**
     * Indicate that the mitra should be disapproved.
     */
    public function ditolak(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_verifikasi' => 'Ditolak',
            'alasan_penolakan'  => 'Alasan penolakan uji',
        ]);
    }

    /**
     * Indicate that the mitra should be cancelled/inactive.
     */
    public function nonaktif(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_verifikasi' => 'Non Aktif',
        ]);
    }
}