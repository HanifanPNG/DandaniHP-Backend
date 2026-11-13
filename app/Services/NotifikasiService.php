<?php

namespace App\Services;

use App\Models\MitraService;
use App\Models\Notifikasi;
use App\Models\Pelanggan;
use App\Support\TipeNotifikasi;

// Service untuk pembuatan notifikasi yang konsisten.
// Dipanggil dari berbagai tempat (penawaran, pesanan, pembayaran).
//
// Catatan penting:
// - Tidak membuat notifikasi duplikat untuk event yang sama.
// - Semua notifikasi membuat `dibaca = false` secara default.
// - Mendukung notifikasi ke Pelanggan dan Mitra.
class NotifikasiService
{
    /**
     * Buat notifikasi baru untuk pelanggan.
     *
     * @param Pelanggan $pelanggan Penerima notifikasi
     * @param string $tipe Salah satu: Penawaran, Progres, Pembayaran, Selesai, Ulasan
     * @param string $judul Judul notifikasi (maks 100 karakter)
     * @param string $pesan Pesan detail (bisa line break)
     * @param int|null $idPesanan Opsional, untuk relasi ke pesanan
     * @return Notifikasi
     */
    public function kirimKePelanggan(
        Pelanggan $pelanggan,
        string $tipe,
        string $judul,
        string $pesan,
        ?int $idPesanan = null
    ): Notifikasi {
        return $this->kirim($pelanggan->id_pelanggan, null, $tipe, $judul, $pesan, $idPesanan);
    }

    /**
     * Buat notifikasi baru untuk mitra.
     *
     * @param MitraService $mitra Penerima notifikasi
     * @param string $tipe Salah satu: Penawaran, Progres, Pembayaran, Selesai, Ulasan
     * @param string $judul Judul notifikasi (maks 100 karakter)
     * @param string $pesan Pesan detail (bisa line break)
     * @param int|null $idPesanan Opsional, untuk relasi ke pesanan
     * @return Notifikasi
     */
    public function kirimKeMitra(
        MitraService $mitra,
        string $tipe,
        string $judul,
        string $pesan,
        ?int $idPesanan = null
    ): Notifikasi {
        return $this->kirim(null, $mitra->id_mitra, $tipe, $judul, $pesan, $idPesanan);
    }

    /**
     * Internal method to create notification.
     */
    private function kirim(
        ?int $idPelanggan,
        ?int $idMitra,
        string $tipe,
        string $judul,
        string $pesan,
        ?int $idPesanan = null
    ): Notifikasi {
        // Validate tipe.
        if (!in_array($tipe, TipeNotifikasi::SEMUA, true)) {
            throw new \InvalidArgumentException("Tipe notifikasi tidak valid: $tipe");
        }

        // Cek duplikat: tidak boleh ada notifikasi yang sama
        // (tipe + judul + pesan + penerima yang sama dalam 5 menit terakhir).
        $query = Notifikasi::where('tipe', $tipe)
            ->where('judul', $judul)
            ->where('pesan', $pesan)
            ->where('waktu_dibuat', '>=', now()->subMinutes(5));

        if ($idPelanggan !== null) {
            $query->where('id_pelanggan', $idPelanggan);
        } else {
            $query->where('id_mitra', $idMitra);
        }

        $duplikat = $query->first();

        if ($duplikat !== null) {
            return $duplikat;
        }

        return Notifikasi::create([
            'id_pelanggan' => $idPelanggan,
            'id_mitra'     => $idMitra,
            'tipe'         => $tipe,
            'judul'        => $judul,
            'pesan'        => $pesan,
            'id_pesanan'   => $idPesanan,
            'dibaca'       => false,
            'waktu_dibuat' => now(),
        ]);
    }

    /**
     * Tandai satu notifikasi sudah dibaca.
     * Hanya pemilik yang boleh mengakses.
     */
    public function tandaiBaca(Notifikasi $notifikasi): bool
    {
        return $notifikasi->update(['dibaca' => true]);
    }

    /**
     * Tandai semua notifikasi milik pelanggan sudah dibaca.
     * Idempotent: boleh dipanggil berkali-kali.
     */
    public function tandaiSemuaBacaPelanggan(Pelanggan $pelanggan): int
    {
        return Notifikasi::where('id_pelanggan', $pelanggan->id_pelanggan)
            ->where('dibaca', false)
            ->update(['dibaca' => true]);
    }

    /**
     * Tandai semua notifikasi milik mitra sudah dibaca.
     * Idempotent: boleh dipanggil berkali-kali.
     */
    public function tandaiSemuaBacaMitra(MitraService $mitra): int
    {
        return Notifikasi::where('id_mitra', $mitra->id_mitra)
            ->where('dibaca', false)
            ->update(['dibaca' => true]);
    }

    /**
     * Dapatkan jumlah notifikasi belum dibaca untuk pelanggan.
     */
    public function belumDibacaPelanggan(Pelanggan $pelanggan): int
    {
        return Notifikasi::where('id_pelanggan', $pelanggan->id_pelanggan)
            ->where('dibaca', false)
            ->count();
    }

    /**
     * Dapatkan jumlah notifikasi belum dibaca untuk mitra.
     */
    public function belumDibacaMitra(MitraService $mitra): int
    {
        return Notifikasi::where('id_mitra', $mitra->id_mitra)
            ->where('dibaca', false)
            ->count();
    }
}