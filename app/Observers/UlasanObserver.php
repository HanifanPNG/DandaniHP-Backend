<?php

namespace App\Observers;

use App\Models\MitraService;
use App\Models\Ulasan;

// Observer untuk otomatis menghitung rating_rata_rata mitra.
// Dipanggil ketika ulasan dibuat, diubah, atau dihapus.
//
// Catatan: Observer digunakan bukan hook di model karena kita butuh
// agregasi (AVG) dari semua ulasan, bukan hanya satu record.
class UlasanObserver
{
    public function created(Ulasan $ulasan): void
    {
        $this->hitungRating($ulasan->id_mitra);
    }

    public function updated(Ulasan $ulasan): void
    {
        $this->hitungRating($ulasan->id_mitra);
    }

    public function deleted(Ulasan $ulasan): void
    {
        $this->hitungRating($ulasan->id_mitra);
    }

    private function hitungRating(int $idMitra): void
    {
        $mitra = MitraService::find($idMitra);

        if ($mitra === null) {
            return;
        }

        // Ambil rata-rata semua ulasan mitra.
        $rataRata = Ulasan::where('id_mitra', $idMitra)->avg('rating_bintang');

        // Round ke 2 desimal (mis. 4.33).
        $mitra->update([
            'rating_rata_rata' => (float) round($rataRata, 2, PHP_ROUND_HALF_UP),
        ]);
    }
}