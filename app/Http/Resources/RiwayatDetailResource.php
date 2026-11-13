<?php

namespace App\Http\Resources;

use App\Support\StatusPesanan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Detail satu riwayat service (GET /api/riwayat/{id}).
//
// Read-only: berisi seluruh perubahan status, info pembayaran,
// tanggal selesai, dan ulasan bila sudah ada.
class RiwayatDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $pesanan = $this->pesanan;
        $kanonik = StatusPesanan::kanonik($this->resource);

        // Pesanan selesai -> tanggal diambil dari riwayat status terakhir
        // yang relevan, bukan sekadar waktu update kolom.
        $tanggalSelesai = $pesanan?->riwayat
            ->firstWhere('status', StatusPesanan::PESANAN_SELESAI)?->waktu;

        $ulasan = $pesanan?->ulasan;

        return [
            'id_permintaan' => $this->id_permintaan,
            'kode_pesanan'  => $this->kode_pesanan,

            'status_permintaan' => $this->status_permintaan,
            'status_kanonik'    => $kanonik,
            'label_status'      => StatusPesanan::label($kanonik),

            'merek_hp'            => $this->merek_hp,
            'model_hp'            => $this->model_hp,
            'tipe_hp'             => trim(($this->merek_hp ?? '').' '.($this->model_hp ?? '')),
            'kategori_kerusakan'  => $this->kategori_kerusakan,
            'deskripsi_kerusakan' => $this->deskripsi_kerusakan,

            'mitra' => $pesanan?->mitra === null ? null : [
                'id_mitra'      => $pesanan->mitra->id_mitra,
                'nama_usaha'    => $pesanan->mitra->nama_usaha,
                'nomor_telepon' => $pesanan->mitra->nomor_telepon,
            ],

            'harga_penawaran' => $pesanan?->penawaran === null
                ? null
                : (int) $pesanan->penawaran->harga_penawaran,

            // Untuk permintaan tanpa pesanan (rejected/cancelled),
            // timeline kosong dan info pesanan null.
            'pesanan' => $pesanan === null ? null : [
                'id_pesanan'        => $pesanan->id_pesanan,
                'status_pengerjaan' => $pesanan->status_pengerjaan,
                'status_kanonik'    => StatusPesanan::kanonikPengerjaan(
                    $pesanan->status_pengerjaan
                ),
                'label_status' => StatusPesanan::label(
                    StatusPesanan::kanonikPengerjaan($pesanan->status_pengerjaan)
                ),
                'waktu_dibuat' => $pesanan->waktu_dibuat?->toIso8601String(),
            ],

            'timeline' => $pesanan?->riwayat->map(fn ($item) => [
                'status'       => $item->status,
                'label_status' => StatusPesanan::label(
                    StatusPesanan::kanonikPengerjaan($item->status)
                ),
                'aktor'   => $item->aktor,
                'catatan' => $item->catatan,
                'waktu'   => $item->waktu?->toIso8601String(),
            ])->values() ?? [],

            'pembayaran' => $pesanan?->pembayaran === null ? null : [
                'jumlah_pembayaran'      => (int) $pesanan->pembayaran->jumlah_pembayaran,
                'status_pembayaran'      => $pesanan->pembayaran->status_pembayaran,
                'waktu_dibuat'           => $pesanan->pembayaran->waktu_dibuat?->toIso8601String(),
                'waktu_konfirmasi_mitra' => $pesanan->pembayaran
                    ->waktu_konfirmasi_mitra?->toIso8601String(),
            ],

            'tanggal_selesai' => $tanggalSelesai?->toIso8601String(),

            'ulasan' => $ulasan === null ? null : [
                'id_ulasan'        => $ulasan->id_ulasan,
                'rating_bintang'   => (int) $ulasan->rating_bintang,
                'komentar_ulasan'  => $ulasan->komentar_ulasan,
                'waktu_ulasan'     => $ulasan->waktu_ulasan?->toIso8601String(),
            ],

            // Flutter bisa memunculkan tombol "Beri Ulasan" dari flag ini.
            'bisa_ulasan' => $pesanan !== null
                && $pesanan->status_pengerjaan === StatusPesanan::PESANAN_SELESAI
                && $ulasan === null,

            'waktu_dibuat' => $this->waktu_dibuat?->toIso8601String(),
        ];
    }
}
