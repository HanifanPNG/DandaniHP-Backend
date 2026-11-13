<?php

namespace App\Http\Resources;

use App\Support\StatusPesanan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Item daftar riwayat service (GET /api/riwayat).
//
// Satu item mewakili satu permintaan, karena permintaan yang ditolak
// atau dibatalkan tidak punya pesanan. Data pesanan hanya pelengkap.
class RiwayatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $pesanan = $this->pesanan;
        $kanonik = StatusPesanan::kanonik($this->resource);

        return [
            'id_permintaan' => $this->id_permintaan,
            'kode_pesanan'  => $this->kode_pesanan,

            'tipe_hp' => trim(($this->merek_hp ?? '').' '.($this->model_hp ?? '')),

            // Mitra hanya ada kalau permintaan sudah menjadi pesanan.
            'mitra' => $pesanan?->mitra === null ? null : [
                'id_mitra'   => $pesanan->mitra->id_mitra,
                'nama_usaha' => $pesanan->mitra->nama_usaha,
            ],

            'tanggal' => $this->waktu_dibuat?->toIso8601String(),

            // Biaya final dari penawaran pemenang; null untuk
            // permintaan yang ditolak/dibatalkan.
            'biaya' => $pesanan?->penawaran === null
                ? null
                : (int) $pesanan->penawaran->harga_penawaran,

            'status_kanonik' => $kanonik,
            'label_status'   => StatusPesanan::label($kanonik),

            'id_pesanan' => $pesanan?->id_pesanan,
        ];
    }
}
