<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MitraResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
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
            'jam_buka'        => $this->jamBuka(),
            'jam_tutup'       => $this->jamTutup(),
            'is_open'         => $this->isOpen(),

            // Hanya ada jika request memakai sort=jarak + lat/lng.
            'jarak'           => $this->jarak !== null
                ? round((float) $this->jarak, 2)
                : null,
        ];
    }

    /** Ambil "HH:MM" dari kolom time "HH:MM:SS". */
    private function jamBuka(): ?string
    {
        return $this->jam_buka ? substr($this->jam_buka, 0, 5) : null;
    }

    private function jamTutup(): ?string
    {
        return $this->jam_tutup ? substr($this->jam_tutup, 0, 5) : null;
    }

    /** Status buka dihitung saat request, bukan disimpan di database. */
    private function isOpen(): bool
    {
        if (! $this->jam_buka || ! $this->jam_tutup) {
            return false;
        }

        // Keduanya diformat "HH:MM" agar perbandingan string konsisten.
        $sekarang = now('Asia/Jakarta')->format('H:i');

        return $sekarang >= $this->jamBuka() && $sekarang <= $this->jamTutup();
    }
}
