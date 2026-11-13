<?php

namespace App\Http\Resources;

use App\Support\StatusPesanan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Bentuk response detail satu permintaan (GET /api/permintaan/{id}).
//
// Berbeda dari PermintaanResource (dipakai saat membuat permintaan),
// resource ini memuat lampiran, ringkasan penawaran, dan status kanonik.
class PermintaanDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Dihitung sekali supaya tidak memanggil helper dua kali.
        $kanonik = StatusPesanan::kanonik($this->resource);

        return [
            'id_permintaan'       => $this->id_permintaan,
            'kode_pesanan'        => $this->kode_pesanan,
            'status_permintaan'   => $this->status_permintaan,

            // Status ringkas untuk ditampilkan di aplikasi.
            'status_kanonik'      => $kanonik,
            'label_status'        => StatusPesanan::label($kanonik),

            'merek_hp'            => $this->merek_hp,
            'model_hp'            => $this->model_hp,
            'kategori_kerusakan'  => $this->kategori_kerusakan,
            'deskripsi_kerusakan' => $this->deskripsi_kerusakan,
            'lama_kerusakan'      => $this->lama_kerusakan,
            'kondisi_hp'          => $this->kondisi_hp,
            'catatan_tambahan'    => $this->catatan_tambahan,
            'waktu_dibuat'        => $this->waktu_dibuat?->toIso8601String(),

            'lampiran'            => $this->lampiran->map(fn ($item) => [
                'id_lampiran' => $item->id_lampiran,
                'url'         => app(\App\Services\LampiranService::class)->url($item->path),
            ])->values(),

            // Ringkasan penawaran: cukup data yang dipakai kartu daftar.
            'jumlah_penawaran'    => $this->penawaran->count(),
            'penawaran'           => $this->penawaran->map(fn ($item) => [
                'id_penawaran'    => $item->id_penawaran,
                'mitra'           => [
                    'id_mitra'   => $item->mitra?->id_mitra,
                    'nama_usaha' => $item->mitra?->nama_usaha,
                    'rating'     => $item->mitra !== null
                        ? (float) $item->mitra->rating_rata_rata
                        : null,
                ],
                'harga_penawaran' => (float) $item->harga_penawaran,
                'estimasi_hari'   => $item->estimasi_hari,
                'status_penawaran' => $item->status_penawaran,
                'waktu_penawaran' => $item->waktu_penawaran?->toIso8601String(),
            ])->values(),
        ];
    }
}
