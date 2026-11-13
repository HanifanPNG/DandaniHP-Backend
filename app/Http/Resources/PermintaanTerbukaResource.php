<?php

namespace App\Http\Resources;

use App\Services\LampiranService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Item daftar permintaan terbuka untuk mitra (GET /api/mitra/permintaan).
//
// Mitra perlu detail kerusakan + foto untuk menilai, tapi tidak perlu
// identitas lengkap pelanggan. Nama disamarkan, kontak tidak dikirim.
class PermintaanTerbukaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_permintaan' => $this->id_permintaan,
            'kode_pesanan'  => $this->kode_pesanan,

            // Nama disamarkan: "Budi Santoso" -> "Budi S."
            'nama_pelanggan' => $this->namaDisamarkan($this->pelanggan?->nama_lengkap),

            'merek_hp'            => $this->merek_hp,
            'model_hp'            => $this->model_hp,
            'kategori_kerusakan'  => $this->kategori_kerusakan,
            'deskripsi_kerusakan' => $this->deskripsi_kerusakan,
            'lama_kerusakan'      => $this->lama_kerusakan,
            'kondisi_hp'          => $this->kondisi_hp,
            'catatan_tambahan'    => $this->catatan_tambahan,

            // Foto kerusakan membantu mitra menyusun harga.
            'lampiran' => $this->lampiran->map(fn ($item) => [
                'id_lampiran' => $item->id_lampiran,
                'url'         => app(LampiranService::class)->url($item->path),
            ])->values(),

            // Berapa mitra lain sudah menawar (tanpa nominal).
            'jumlah_penawaran' => (int) ($this->penawaran_count ?? 0),

            'waktu_dibuat' => $this->waktu_dibuat?->toIso8601String(),
        ];
    }

    private function namaDisamarkan(?string $nama): ?string
    {
        if ($nama === null || trim($nama) === '') {
            return null;
        }

        $bagian = preg_split('/\s+/', trim($nama));
        $hasil = $bagian[0];

        // Tambahkan inisial nama belakang kalau ada.
        if (count($bagian) > 1) {
            $hasil .= ' '.strtoupper(substr((string) end($bagian), 0, 1)).'.';
        }

        return $hasil;
    }
}
