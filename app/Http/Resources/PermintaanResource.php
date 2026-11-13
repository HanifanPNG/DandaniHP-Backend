<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Bentuk response satu permintaan service.
// Dipakai di endpoint buat permintaan (POST /api/permintaan)
// dan nanti detail permintaan (GET /api/permintaan/{id}).
class PermintaanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_permintaan'       => $this->id_permintaan,
            // Kode ini yang ditampilkan ke pelanggan sebagai nomor pesanan.
            'kode_pesanan'        => $this->kode_pesanan,
            'status_permintaan'   => $this->status_permintaan,

            'merek_hp'            => $this->merek_hp,
            'model_hp'            => $this->model_hp,
            'kategori_kerusakan'  => $this->kategori_kerusakan,
            'deskripsi_kerusakan' => $this->deskripsi_kerusakan,
            'lama_kerusakan'      => $this->lama_kerusakan,
            'kondisi_hp'          => $this->kondisi_hp,
            'catatan_tambahan'    => $this->catatan_tambahan,

            // BR-12: tanggal ISO-8601 dengan zona Asia/Jakarta.
            'waktu_dibuat'        => $this->waktu_dibuat?->toIso8601String(),
        ];
    }
}
