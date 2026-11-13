<?php

namespace Database\Factories;

use App\Models\MitraService;
use App\Models\Pelanggan;
use App\Models\PenawaranMitra;
use App\Models\PermintaanService;
use App\Models\PesananAktif;
use App\Support\StatusPesanan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PesananAktif>
 */
class PesananAktifFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $permintaan = PermintaanService::factory()->selesiDilelang()->create();
        $pelanggan = Pelanggan::firstOrNew(['id_pelanggan' => $permintaan->id_pelanggan]);
        $mitra = MitraService::factory()->terverifikasi()->create();
        $penawaran = PenawaranMitra::factory()->disetujui()->create([
            'id_permintaan' => $permintaan->id_permintaan,
            'id_mitra' => $mitra->id_mitra,
        ]);

        return [
            'id_penawaran'        => $penawaran->id_penawaran,
            'id_permintaan'       => $permintaan->id_permintaan,
            'id_pelanggan'        => $pelanggan->id_pelanggan,
            'id_mitra'            => $mitra->id_mitra,
            'status_pengerjaan'   => fake()->randomElement(StatusPesanan::DAFTAR_PENGERJAAN),
            'waktu_dibuat'        => now(),
            'waktu_update_terakhir' => now(),
            'catatan_perkembangan' => 'Catatan uji',
        ];
    }

    /**
     * Indicate that the pesanan is in the queue.
     */
    public function masukAntrean(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_pengerjaan' => StatusPesanan::PESANAN_MASUK_ANTREAN,
        ]);
    }

    /**
     * Indicate that the pesanan is in progress.
     */
    public function sedangDiproses(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_pengerjaan' => StatusPesanan::PESANAN_SEDANG_DIPROSES,
        ]);
    }

    /**
     * Indicate that the pesanan is completed.
     */
    public function selesai(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_pengerjaan' => StatusPesanan::PESANAN_SELESAI,
        ]);
    }

    /**
     * Indicate that the pesanan is waiting for payment.
     */
    public function menungguPembayaran(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_pengerjaan' => StatusPesanan::PESANAN_MENUNGGU_PEMBAYARAN,
        ]);
    }

    /**
     * Indicate that the pesanan has payment received.
     */
    public function pembayaranDiterima(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_pengerjaan' => StatusPesanan::PESANAN_PEMBAYARAN_DITERIMA,
        ]);
    }
}