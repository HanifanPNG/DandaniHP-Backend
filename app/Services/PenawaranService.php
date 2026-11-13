<?php

namespace App\Services;

use App\Models\PenawaranMitra;
use App\Models\PermintaanService;
use App\Models\PesananAktif;
use App\Models\RiwayatStatusPesanan;
use App\Services\NotifikasiService;
use App\Support\StatusPesanan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Mengatur perubahan status penawaran yang berdampak ke pesanan.
//
// Alur setujui sengaja ditaruh di Service (bukan controller) karena
// melibatkan banyak tabel sekaligus dan wajib berjalan dalam satu transaksi
// (tech spec 2.5, BR-04).
class PenawaranService
{
    // Menyetujui satu penawaran dan membuat pesanan aktif.
    //
    // Aturan:
    // - Penawaran harus masih Menunggu Keputusan.
    // - Permintaan belum boleh punya pesanan (BR-06).
    // - Penawaran lain yang masih menunggu otomatis Kedaluwarsa.
    public function setujui(PenawaranMitra $penawaran): PesananAktif
    {
        return DB::transaction(function () use ($penawaran) {
            // lockForUpdate mencegah dua request menyetujui bersamaan.
            // Request kedua akan menunggu sampai transaksi pertama selesai,
            // lalu melihat status yang sudah berubah.
            $penawaran = PenawaranMitra::with('mitra')
                ->whereKey($penawaran->id_penawaran)
                ->lockForUpdate()
                ->firstOrFail();

            $permintaan = PermintaanService::whereKey($penawaran->id_permintaan)
                ->lockForUpdate()
                ->firstOrFail();

            // Hanya penawaran yang masih menunggu yang bisa disetujui.
            if ($penawaran->status_penawaran !== StatusPesanan::PENAWARAN_MENUNGGU) {
                throw ValidationException::withMessages([
                    'status_penawaran' => 'Penawaran ini sudah tidak bisa disetujui.',
                ]);
            }

            // Satu permintaan hanya boleh punya satu pesanan.
            // Unique constraint di tabel pesanan_aktif menjadi lapis kedua
            // kalau ada request yang lolos hampir bersamaan.
            if (PesananAktif::where('id_permintaan', $permintaan->id_permintaan)->exists()) {
                throw ValidationException::withMessages([
                    'penawaran' => 'Permintaan ini sudah memiliki pesanan aktif.',
                ]);
            }

            // 1. Penawaran terpilih disetujui.
            $penawaran->update([
                'status_penawaran' => StatusPesanan::PENAWARAN_DISETUJUI,
            ]);

            // 2. Penawaran lain yang masih menunggu menjadi Kedaluwarsa.
            // Penawaran yang sudah ditolak pelanggan dibiarkan apa adanya
            // supaya riwayat keputusan tetap benar.
            PenawaranMitra::where('id_permintaan', $permintaan->id_permintaan)
                ->where('id_penawaran', '!=', $penawaran->id_penawaran)
                ->where('status_penawaran', StatusPesanan::PENAWARAN_MENUNGGU)
                ->update(['status_penawaran' => StatusPesanan::PENAWARAN_KEDALUWARSA]);

            // 3. Buat pesanan aktif. Semua FK diturunkan dari penawaran
            // dan permintaan, bukan dari input client (BR-04).
            $pesanan = PesananAktif::create([
                'id_penawaran'          => $penawaran->id_penawaran,
                'id_permintaan'         => $permintaan->id_permintaan,
                'id_pelanggan'          => $permintaan->id_pelanggan,
                'id_mitra'              => $penawaran->id_mitra,
                'status_pengerjaan'     => StatusPesanan::PESANAN_MASUK_ANTREAN,
                'waktu_dibuat'          => now(),
                'waktu_update_terakhir' => now(),
            ]);

            // 4. Permintaan selesai dilelang (sudah ada pemenang).
            $permintaan->update([
                'status_permintaan' => StatusPesanan::PERMINTAAN_SELESAI_DILELANG,
            ]);

            // 5. Catat riwayat awal pesanan.
            RiwayatStatusPesanan::create([
                'id_pesanan' => $pesanan->id_pesanan,
                'status'     => StatusPesanan::PESANAN_MASUK_ANTREAN,
                'catatan'    => 'Pesanan dibuat setelah pelanggan menyetujui penawaran.',
                'aktor'      => 'Sistem',
                'waktu'      => now(),
            ]);

            // 6. Beri tahu pelanggan bahwa penawarannya disetujui.
            $notifikasiService = app(NotifikasiService::class);
            $notifikasiService->kirimKePelanggan(
                $permintaan->pelanggan,
                'Penawaran',
                'Penawaran disetujui',
                'Penawaran dari '.($penawaran->mitra?->nama_usaha ?? 'mitra')
                    .' telah disetujui. Pesanan masuk antrean mitra.',
                $pesanan->id_pesanan
            );

            // Relasi disiapkan agar resource tidak query ulang.
            $pesanan->setRelation('permintaan', $permintaan);
            $pesanan->setRelation('mitra', $penawaran->mitra);
            $pesanan->setRelation('penawaran', $penawaran);

            return $pesanan;
        });
    }

    // Menolak satu penawaran atas nama pelanggan.
    //
    // BR-05: penolakan tidak pernah membuat pesanan. Kalau semua
    // penawaran ditolak, status kanonik permintaan otomatis menjadi
    // rejected saat dibaca (lihat StatusPesanan::kanonik).
    public function tolak(PenawaranMitra $penawaran, ?string $alasan = null): PenawaranMitra
    {
        return DB::transaction(function () use ($penawaran, $alasan) {
            $penawaran = PenawaranMitra::whereKey($penawaran->id_penawaran)
                ->lockForUpdate()
                ->firstOrFail();

            // Hanya penawaran yang masih menunggu yang bisa ditolak.
            if ($penawaran->status_penawaran !== StatusPesanan::PENAWARAN_MENUNGGU) {
                throw ValidationException::withMessages([
                    'status_penawaran' => 'Penawaran ini sudah tidak bisa ditolak.',
                ]);
            }

            $penawaran->update([
                'status_penawaran'  => StatusPesanan::PENAWARAN_DITOLAK,
                'alasan_penolakan'  => $alasan,
            ]);

            return $penawaran->load('mitra');
        });
    }
}
