<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Bentuk response satu ulasan (bisa ditampilkan di detail pesanan).
class UlasanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_ulasan'       => $this->id_ulasan,
            'rating_bintang'  => (int) $this->rating_bintang,
            'komentar_ulasan' => $this->komentar_ulasan,
            'waktu_ulasan'    => $this->waktu_ulasan?->toIso8601String(),
        ];
    }
}