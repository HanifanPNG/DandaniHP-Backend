<?php

namespace App\Http\Resources;

use App\Support\LabelRating;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Bentuk detail ulasan yang ditampilkan di detail riwayat.
// Memuat nama mitra, label rating, dan foto hasil servis.
class UlasanDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_ulasan'       => $this->id_ulasan,
            'rating_bintang'  => (int) $this->rating_bintang,
            'label_rating'    => LabelRating::label((int) $this->rating_bintang),
            'warna_rating'    => LabelRating::warna((int) $this->rating_bintang),
            'komentar_ulasan' => $this->komentar_ulasan,
            'foto_ulasan'     => $this->foto_ulasan,
            'waktu_ulasan'    => $this->waktu_ulasan?->toIso8601String(),
            'mitra'           => $this->whenLoaded('mitra', fn () => [
                'id_mitra'   => $this->mitra?->id_mitra,
                'nama_usaha' => $this->mitra?->nama_usaha,
            ]),
        ];
    }
}