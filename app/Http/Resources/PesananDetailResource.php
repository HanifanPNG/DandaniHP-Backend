<?php

namespace App\Http\Resources;

use App\Support\StatusPesanan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Bentuk response detail satu pesanan (GET /api/pesanan/{id}).
//
// PesananResource hanya ringkas untuk kartu daftar; resource ini
// memuat data HP, kontak mitra, timeline, dan info pembayaran.
class PesananDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $kanonik = StatusPesanan::kanonikPengerjaan($this->status_pengerjaan);
        $permintaan = $this->permintaan;
        $penawaran = $this->penawaran;
        $pembayaran = $this->pembayaran;

        // Estimasi selesai = waktu pesanan dibuat + estimasi hari dari
        // penawaran pemenang. Null kalau mitra tidak mengisi estimasi.
        $estimasiSelesai = null;
        if ($this->waktu_dibuat !== null && $penawaran?->estimasi_hari !== null) {
            $estimasiSelesai = $this->waktu_dibuat
                ->copy()
                ->addDays((int) $penawaran->estimasi_hari);
        }

        return [
            'id_pesanan'       => $this->id_pesanan,
            'kode_pesanan'     => $permintaan?->kode_pesanan,

            'status_pengerjaan' => $this->status_pengerjaan,
            'catatan_perkembangan' => $this->catatan_perkembangan,
            'status_kanonik'    => $kanonik,
            'label_status'      => StatusPesanan::label($kanonik),

            // Data HP & kerusakan dari permintaan asal.
            'merek_hp'            => $permintaan?->merek_hp,
            'model_hp'            => $permintaan?->model_hp,
            'kategori_kerusakan'  => $permintaan?->kategori_kerusakan,
            'deskripsi_kerusakan' => $permintaan?->deskripsi_kerusakan,
            'catatan_tambahan'    => $permintaan?->catatan_tambahan,

            // Kontak mitra dibutuhkan pelanggan untuk koordinasi.
            'mitra' => $this->mitra === null ? null : [
                'id_mitra'      => $this->mitra->id_mitra,
                'nama_usaha'    => $this->mitra->nama_usaha,
                'nomor_telepon' => $this->mitra->nomor_telepon,
                'alamat_usaha'  => $this->mitra->alamat_usaha,
            ],

            // Harga final = harga pada penawaran yang disetujui.
            'harga_penawaran'  => (int) ($penawaran?->harga_penawaran ?? 0),
            'estimasi_hari'    => $penawaran?->estimasi_hari,
            'estimasi_selesai' => $estimasiSelesai?->toIso8601String(),

            // BR-07: hanya informasi, tidak ada tombol/aksi konfirmasi
            // pembayaran di sisi pelanggan.
            'pembayaran' => $this->pembayaran === null ? null : [
                'id_pembayaran'          => $this->pembayaran->id_pembayaran,
                'jumlah_pembayaran'      => (int) $this->pembayaran->jumlah_pembayaran,
                'status_pembayaran'      => $this->pembayaran->status_pembayaran,
                'waktu_dibuat'           => $this->pembayaran->waktu_dibuat?->toIso8601String(),
                'waktu_konfirmasi_mitra' => $this->pembayaran->waktu_konfirmasi_mitra?->toIso8601String(),
            ],

            // Timeline perubahan status, sudah urut waktu dari relasi.
            'timeline' => $this->riwayat->map(fn ($item) => [
                'id_riwayat'   => $item->id_riwayat,
                'status'       => $item->status,
                'label_status' => StatusPesanan::label(
                    StatusPesanan::kanonikPengerjaan($item->status)
                ),
                'aktor'        => $item->aktor,
                'catatan'      => $item->catatan,
                'waktu'        => $item->waktu?->toIso8601String(),
            ])->values(),

            'waktu_dibuat'          => $this->waktu_dibuat?->toIso8601String(),
            'waktu_update_terakhir' => $this->waktu_update_terakhir?->toIso8601String(),
        ];
    }
}
