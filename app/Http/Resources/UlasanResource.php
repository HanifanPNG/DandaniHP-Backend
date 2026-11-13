<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Satu ulasan pada halaman daftar ulasan mitra
 * (GET /api/mitra/{id}/ulasan).
 */
class UlasanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_ulasan'      => $this->id_ulasan,
            // Identitas pelanggan dibatasi ke nama saja demi privasi.
            'nama_pelanggan' => $this->pelanggan?->nama_lengkap ?? 'Pelanggan',
            'rating_bintang' => (int) $this->rating_bintang,
            'komentar'       => $this->komentar_ulasan,
            // BR-12: semua tanggal ISO-8601 dengan zona Asia/Jakarta.
            'waktu'          => $this->waktu_ulasan?->toIso8601String(),
        ];
    }
}
