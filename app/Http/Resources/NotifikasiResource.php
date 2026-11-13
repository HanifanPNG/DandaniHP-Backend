<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Bentuk response satu notifikasi.
class NotifikasiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_notifikasi' => $this->id_notifikasi,
            'tipe'          => $this->tipe,
            'judul'         => $this->judul,
            'pesan'         => $this->pesan,
            'id_pesanan'    => $this->id_pesanan,
            'dibaca'        => (bool) $this->dibaca,
            'waktu_dibuat'  => $this->waktu_dibuat?->toIso8601String(),
        ];
    }
}