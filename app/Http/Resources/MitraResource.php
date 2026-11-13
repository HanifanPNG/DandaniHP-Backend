<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk data mitra untuk daftar (GET /api/mitra).
 *
 * Resource bertugas menyaring kolom: hanya yang dibutuhkan aplikasi
 * yang keluar, misalnya kata_sandi dan status_verifikasi tidak ikut.
 */
class MitraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_mitra'        => $this->id_mitra,
            'nama_usaha'      => $this->nama_usaha,
            'alamat_usaha'    => $this->alamat_usaha,
            'foto_toko'       => $this->foto_toko,

            'rating'          => (float) $this->rating_rata_rata,
            'review_count'    => (int) ($this->ulasan_count ?? 0),

            // Badge "Terverifikasi" untuk aplikasi Flutter.
            'terverifikasi'   => $this->status_verifikasi === 'Disetujui',

            // Hanya nama layanan; id_layanan tidak perlu diekspos ke client.
            'layanan'         => $this->layanan->pluck('nama_layanan')->values(),

            'jam_operasional' => $this->jam_operasional,
            'jam_buka'        => $this->jamBukaFormat(),
            'jam_tutup'       => $this->jamTutupFormat(),
            'is_open'         => $this->isOpen(),

            // Hanya ada jika request memakai sort=jarak + lat/lng.
            'jarak'           => $this->jarak !== null
                ? round((float) $this->jarak, 2)
                : null,
        ];
    }
}
