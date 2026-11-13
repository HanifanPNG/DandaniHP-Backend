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

            // Whitelist transisi: hanya status berikutnya yang diizinkan.
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

    // Konfirmasi pembayaran tunai oleh mitra (BR-08).
    //
    // Aturan:
    // - Hanya `auth:mitra`, pesanan harus milik mitra tersebut.
    // - Status pesanan harus `Menunggu Pembayaran Tunai`.
    // - Set `pembayaran.status_pembayaran = Diterima`,
    //   `waktu_konfirmasi_mitra = now()`.
    // - Pesanan → `Pembayaran Diterima` + riwayat + notifikasi.
    public function konfirmasi(PesananAktif $pesanan): PesananAktif
    {
        return DB::transaction(function () use ($pesanan) {
            // Lock pesanan supaya dua mitra konfirmasi bersamaan tidak bentrok.
            $pesanan = PesananAktif::whereKey($pesanan->id_pesanan)
                ->lockForUpdate()
                ->firstOrFail();

            // Hanya status Menunggu Pembayaran Tunai yang boleh dikonfirmasi.
            if ($pesanan->status_pengerjaan !== StatusPesanan::PESANAN_MENUNGGU_PEMBAYARAN) {
                throw ValidationException::withMessages([
                    'status_pengerjaan' => 'Pesanan ini bukan Menunggu Pembayaran Tunai.',
                ]);
            }

            $pesanan->update([
                'status_pengerjaan' => StatusPesanan::PESANAN_PEMBAYARAN_DITERIMA,
                'waktu_update_terakhir' => now(),
            ]);

            // Pastikan baris pembayaran tunai ada dan statusnya Diterima.
            $pembayaran = PembayaranTunai::where('id_pesanan', $pesanan->id_pesanan)->first();

            if ($pembayaran === null) {
                // Buat baris baru dengan nominal dari harga penawaran.
                $harga = $pesanan->penawaran?->harga_penawaran ?? 0;

                $pembayaran = PembayaranTunai::create([
                    'id_pesanan'             => $pesanan->id_pesanan,
                    'jumlah_pembayaran'      => (int) $harga,
                    'status_pembayaran'      => StatusPesanan::PEMBAYARAN_DITERIMA,
                    'waktu_dibuat'           => now(),
                    'waktu_konfirmasi_mitra' => now(),
                ]);
            } else {
                $pembayaran->update([
                    'status_pembayaran'      => StatusPesanan::PEMBAYARAN_DITERIMA,
                    'waktu_konfirmasi_mitra' => now(),
                ]);
            }

            // Catat riwayat perubahan status pembayaran.
            RiwayatStatusPesanan::create([
                'id_pesanan' => $pesanan->id_pesanan,
                'status'     => StatusPesanan::PESANAN_PEMBAYARAN_DITERIMA,
                'catatan'    => 'Pembayaran tunai dikonfirmasi oleh mitra.',
                'aktor'      => 'Mitra',
                'waktu'      => now(),
            ]);

            // Notifikasi ke pelanggan.
            Notifikasi::create([
                'id_pelanggan' => $pesanan->id_pelanggan,
                'tipe'         => 'Pembayaran',
                'judul'        => 'Pembayaran diterima',
                'pesan'        => 'Pembayaran tunai sejumlah Rp'.number_format((int) $harga, 0, ',', '.').' untuk pesanan '.$pesanan->permintaan?->kode_pesanan.' telah dikonfirmasi.',
                'id_pesanan'   => $pesanan->id_pesanan,
                'waktu_dibuat' => now(),
            ]);

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