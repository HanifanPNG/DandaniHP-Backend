<?php

namespace App\Services;

use App\Models\LampiranPermintaan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// Mengurus file lampiran permintaan: simpan, hapus, dan ubah jadi URL.
//
// Kenapa dipisah ke Service?
// - Controller tidak perlu tahu detail disk/folder.
// - Aturan hapus-file dipakai juga saat permintaan dibatalkan (T-25),
//   jadi lebih aman kalau ada di satu tempat.
class LampiranService
{
    // Disk "public" -> file bisa diakses lewat /storage/... setelah storage:link.
    private const DISK = 'public';

    // Menyimpan semua file yang diunggah untuk satu permintaan.
    // Mengembalikan daftar model LampiranPermintaan yang baru dibuat.
    public function simpan(int $idPermintaan, array $daftarFile): array
    {
        $tersimpan = [];

        foreach ($daftarFile as $file) {
            // Pastikan hanya objek UploadedFile yang diproses.
            if (! $file instanceof UploadedFile) {
                continue;
            }

            // store() memberi nama acak, jadi dua file dengan nama asli
            // sama tidak saling menimpa.
            $path = $file->store("lampiran/{$idPermintaan}", self::DISK);

            $tersimpan[] = LampiranPermintaan::create([
                'id_permintaan' => $idPermintaan,
                'path'          => $path,
                'waktu_unggah'  => now(),
            ]);
        }

        return $tersimpan;
    }

    // Menghapus file fisik + baris database milik satu permintaan.
    // Dipakai saat permintaan dibatalkan (T-25).
    public function hapusSemua(int $idPermintaan): void
    {
        $lampiran = LampiranPermintaan::where('id_permintaan', $idPermintaan)->get();

        foreach ($lampiran as $item) {
            Storage::disk(self::DISK)->delete($item->path);
            $item->delete();
        }
    }

    // Dipakai saat transaksi database gagal setelah file terlanjur tersimpan.
    // Baris database sudah di-rollback, jadi yang perlu dibersihkan foldernya.
    public function hapusFolder(int $idPermintaan): void
    {
        Storage::disk(self::DISK)->deleteDirectory("lampiran/{$idPermintaan}");
    }

    // URL publik untuk aplikasi Flutter.
    public function url(string $path): string
    {
        return Storage::disk(self::DISK)->url($path);
    }
}
