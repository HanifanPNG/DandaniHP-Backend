<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk data mitra untuk halaman detail (GET /api/mitra/{id}).
 *
 * Lebih lengkap dari MitraResource: ada deskripsi, kontak, galeri,
 * lokasi, dan 3 ulasan terbaru.
 */
class MitraDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_mitra'        => $this->id_mitra,
            'nama_usaha'      => $this->nama_usaha,
            'nama_pemilik'    => $this->nama_pemilik,
            'deskripsi_usaha' => $this->deskripsi_usaha,
            'alamat_usaha'    => $this->alamat_usaha,
            'nomor_telepon'   => $this->nomor_telepon,
            'foto_toko'       => $this->foto_toko,

            'rating'          => (float) $this->rating_rata_rata,
            'review_count'    => (int) ($this->ulasan_count ?? 0),
            'terverifikasi'   => $this->status_verifikasi === 'Disetujui',

            'layanan'         => $this->layanan->pluck('nama_layanan')->values(),
            'galeri'          => $this->galeri->pluck('path')->values(),

            'jam_operasional' => $this->jam_operasional,
            'jam_buka'        => $this->jamBukaFormat(),
            'jam_tutup'       => $this->jamTutupFormat(),
            'is_open'         => $this->isOpen(),

            'lokasi'          => [
                'latitude'  => $this->latitude !== null ? (float) $this->latitude : null,
                'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            ],

            // Hanya nama pelanggan. Email dan nomor telepon pelanggan
            // tidak boleh keluar di halaman publik mitra.
            'ulasan_terbaru'  => $this->ulasanTerbaru->map(fn ($u) => [
                'id_ulasan'      => $u->id_ulasan,
                'nama_pelanggan' => $u->pelanggan?->nama_lengkap ?? 'Pelanggan',
                'rating_bintang' => (int) $u->rating_bintang,
                'komentar'       => $u->komentar_ulasan,
                // BR-12: semua tanggal ISO-8601 dengan zona Asia/Jakarta.
                'waktu'          => $u->waktu_ulasan?->toIso8601String(),
            ])->values(),
        ];
    }
}
