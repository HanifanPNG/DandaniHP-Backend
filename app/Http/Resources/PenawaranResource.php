<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Bentuk response satu penawaran mitra.
// Dipakai di daftar penawaran (T-26), setujui (T-27), dan tolak (T-28).
class PenawaranResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_penawaran' => $this->id_penawaran,

            // Info permintaan terkait (dipakai di widget "Tawaran Baru").
            'permintaan' => $this->whenLoaded('permintaan', fn () => [
                'id_permintaan' => $this->permintaan?->id_permintaan,
                'kode_pesanan'  => $this->permintaan?->kode_pesanan,
                'merek_hp'      => $this->permintaan?->merek_hp,
                'model_hp'      => $this->permintaan?->model_hp,
            ]),

            // Ringkasan mitra: cukup data yang ditampilkan di kartu penawaran.
            'mitra' => [
                'id_mitra'   => $this->mitra?->id_mitra,
                'nama_usaha' => $this->mitra?->nama_usaha,
                'rating'     => $this->mitra !== null
                    ? (float) $this->mitra->rating_rata_rata
                    : null,
            ],

            'harga_penawaran' => (int) $this->harga_penawaran,
            'estimasi_hari'   => $this->estimasi_hari,
            'estimasi_waktu'  => $this->estimasi_waktu,

            // Catatan hasil pemeriksaan mitra (opsional).
            'catatan_pemeriksaan' => $this->catatan_pemeriksaan,

            'status_penawaran' => $this->status_penawaran,
            'alasan_penolakan' => $this->alasan_penolakan,

            // BR-12: tanggal ISO-8601 zona Asia/Jakarta.
            'waktu_penawaran' => $this->waktu_penawaran?->toIso8601String(),
        ];
    }
}
