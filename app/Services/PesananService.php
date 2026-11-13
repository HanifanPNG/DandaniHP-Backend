<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\PembayaranTunai;
use App\Models\PesananAktif;
use App\Models\RiwayatStatusPesanan;
use App\Support\StatusPesanan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Mengatur transisi status pesanan oleh mitra.
//
// Semua perubahan wajib melewati whitelist di bawah supaya status tidak
// bisa melompat atau mundur (tech spec 2.4). Setiap perubahan menulis
// riwayat + notifikasi dalam satu transaksi.
class PesananService
{
    // Peta transisi yang diizinkan: status sekarang => status berikutnya.
    // Status terakhir (Pesanan Selesai) tidak punya lanjutan.
    private const TRANSISI = [
        StatusPesanan::PESANAN_MASUK_ANTREAN       => StatusPesanan::PESANAN_SEDANG_DIPROSES,
        StatusPesanan::PESANAN_SEDANG_DIPROSES     => StatusPesanan::PESANAN_SERVICE_SELESAI,
        StatusPesanan::PESANAN_SERVICE_SELESAI     => StatusPesanan::PESANAN_MENUNGGU_PEMBAYARAN,
        StatusPesanan::PESANAN_MENUNGGU_PEMBAYARAN => StatusPesanan::PESANAN_PEMBAYARAN_DITERIMA,
        StatusPesanan::PESANAN_PEMBAYARAN_DITERIMA => StatusPesanan::PESANAN_SELESAI,
        StatusPesanan::PESANAN_SELESAI             => null,
    ];

    public function ubahStatus(
        PesananAktif $pesanan,
        string $statusBaru,
        ?string $catatan = null
    ): PesananAktif {
        return DB::transaction(function () use ($pesanan, $statusBaru, $catatan) {
            // Lock supaya dua request tidak mengubah status bersamaan.
            $pesanan = PesananAktif::with(['permintaan', 'penawaran'])
                ->whereKey($pesanan->id_pesanan)
                ->lockForUpdate()
                ->firstOrFail();

            $berikutnya = self::TRANSISI[$pesanan->status_pengerjaan] ?? null;

            // Melompat, mundur, atau mengulang status yang sama -> 422.
            if ($berikutnya === null || $berikutnya !== $statusBaru) {
                throw ValidationException::withMessages([
                    'status_pengerjaan' => 'Transisi status tidak valid dari '
                        .$pesanan->status_pengerjaan.'.',
                ]);
            }

            $pesanan->update([
                'status_pengerjaan' => $statusBaru,
                // Catatan baru menggantikan yang lama; kalau kosong,
                // catatan terakhir tetap dipakai.
                'catatan_perkembangan' => $catatan ?? $pesanan->catatan_perkembangan,
                'waktu_update_terakhir' => now(),
            ]);

            // Tahap pembayaran: siapkan baris pembayaran tunai sekali saja.
            if ($statusBaru === StatusPesanan::PESANAN_MENUNGGU_PEMBAYARAN) {
                $this->siapkanPembayaran($pesanan);
            }

            // Kalau mitra menandai pembayaran diterima lewat endpoint status,
            // baris pembayarannya ikut disinkronkan (BR-07: nominal dari server).
            if ($statusBaru === StatusPesanan::PESANAN_PEMBAYARAN_DITERIMA) {
                $this->tandaiPembayaranDiterima($pesanan);
            }

            RiwayatStatusPesanan::create([
                'id_pesanan' => $pesanan->id_pesanan,
                'status'     => $statusBaru,
                'catatan'    => $catatan,
                'aktor'      => 'Mitra',
                'waktu'      => now(),
            ]);

            $this->kirimNotifikasi($pesanan, $statusBaru);

            // Muat relasi agar resource detail tidak query ulang.
            return $pesanan->load([
                'permintaan',
                'mitra',
                'penawaran',
                'pembayaran',
                'riwayat',
            ]);
        });
    }

    // Membuat pembayaran tunai dengan nominal dari harga penawaran.
    private function siapkanPembayaran(PesananAktif $pesanan): void
    {
        PembayaranTunai::firstOrCreate(
            ['id_pesanan' => $pesanan->id_pesanan],
            [
                'jumlah_pembayaran' => (int) ($pesanan->penawaran?->harga_penawaran ?? 0),
                'status_pembayaran' => StatusPesanan::PEMBAYARAN_MENUNGGU,
                'waktu_dibuat'      => now(),
            ]
        );
    }

    private function tandaiPembayaranDiterima(PesananAktif $pesanan): void
    {
        $pembayaran = PembayaranTunai::where('id_pesanan', $pesanan->id_pesanan)->first();

        if ($pembayaran !== null
            && $pembayaran->status_pembayaran !== StatusPesanan::PEMBAYARAN_DITERIMA) {
            $pembayaran->update([
                'status_pembayaran'      => StatusPesanan::PEMBAYARAN_DITERIMA,
                'waktu_konfirmasi_mitra' => now(),
            ]);
        }
    }

    private function kirimNotifikasi(PesananAktif $pesanan, string $statusBaru): void
    {
        $tipe = match ($statusBaru) {
            StatusPesanan::PESANAN_SELESAI         => 'Selesai',
            StatusPesanan::PESANAN_PEMBAYARAN_DITERIMA => 'Pembayaran',
            default                                 => 'Progres',
        };

        Notifikasi::create([
            'id_pelanggan' => $pesanan->id_pelanggan,
            'tipe'         => $tipe,
            'judul'        => 'Pesanan '.$pesanan->permintaan?->kode_pesanan,
            'pesan'        => 'Status pesanan diperbarui menjadi '.$statusBaru.'.',
            'id_pesanan'   => $pesanan->id_pesanan,
            'waktu_dibuat' => now(),
        ]);
    }
}
