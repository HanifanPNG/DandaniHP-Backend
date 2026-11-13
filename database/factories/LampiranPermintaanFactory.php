<?php

namespace Database\Factories;

use App\Models\LampiranPermintaan;
use App\Models\PermintaanService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LampiranPermintaan>
 */
class LampiranPermintaanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_permintaan'  => PermintaanService::factory(),
            'tipe_file'      => 'image',
            'path_file'      => 'lampiran/temp/' . fake()->uuid() . '.jpg',
            'keterangan'     => fake()->sentence(),
            'waktu_ditambah' => now(),
        ];
    }

    /**
     * Indicate that the lampiran is an image.
     */
    public function gambar(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipe_file' => 'image',
            'path_file' => 'lampiran/temp/' . fake()->uuid() . '.jpg',
        ]);
    }

    /**
     * Indicate that the lampiran is a video.
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipe_file' => 'video',
            'path_file' => 'lampiran/temp/' . fake()->uuid() . '.mp4',
        ]);
    }

    /**
     * Indicate that the lampiran is a document.
     */
    public function dokumen(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipe_file' => 'document',
            'path_file' => 'lampiran/temp/' . fake()->uuid() . '.pdf',
        ]);
    }
}