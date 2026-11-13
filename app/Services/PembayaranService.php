<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\PembayaranTunai;
use App\Models\PesananAktif;
use App\Support\StatusPesanan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Mengatur konfirmasi pembayaran tunai oleh mitra.
//
// Hanya mitra pemilik pesanan yang boleh konfirmasi.
// Nominal dan metode tidak boleh dari klien (BR-07).
// Set status pembayaran + update status pesanan + notifikasi ke pelanggan.
class PembayaranService
{
    // Konfirmasi pembayaran tunai.
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
}