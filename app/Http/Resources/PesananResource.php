<?php

namespace App\Http\Resources;

use App\Support\StatusPesanan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Bentuk response ringkas satu pesanan.
// Dipakai saat penawaran disetujui (T-27) dan nanti daftar pesanan (M5).
class PesananResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $kanonik = StatusPesanan::kanonikPengerjaan($this->status_pengerjaan);

        return [
            'id_pesanan'    => $this->id_pesanan,
            'kode_pesanan'  => $this->whenLoaded(
                'permintaan',
                fn () => $this->permintaan?->kode_pesanan
            ),

            'mitra' => $this->whenLoaded('mitra', fn () => [
                'id_mitra'   => $this->mitra?->id_mitra,
                'nama_usaha' => $this->mitra?->nama_usaha,
            ]),

            // Harga yang disetujui pelanggan, diambil dari penawaran.
            'harga_penawaran' => $this->whenLoaded(
                'penawaran',
                fn () => (int) $this->penawaran?->harga_penawaran
            ),

            'status_pengerjaan' => $this->status_pengerjaan,
            'status_kanonik'    => $kanonik,
            'label_status'      => StatusPesanan::label($kanonik),

            'waktu_dibuat'          => $this->waktu_dibuat?->toIso8601String(),
            'waktu_update_terakhir' => $this->waktu_update_terakhir?->toIso8601String(),
        ];
    }
}
