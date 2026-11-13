<?php

namespace App\Http\Controllers\Api\Pelanggan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pelanggan\StorePermintaanRequest;
use App\Http\Resources\PermintaanDetailResource;
use App\Http\Resources\PermintaanResource;
use App\Models\PermintaanService;
use App\Services\KodePesananGenerator;
use App\Services\LampiranService;
use App\Support\StatusPesanan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PermintaanController extends Controller
{
    // POST /api/permintaan
    //
    // Hanya pelanggan terverifikasi yang boleh membuat permintaan
    // (dijaga middleware verified.pelanggan, BR-01).
    public function store(StorePermintaanRequest $request, KodePesananGenerator $generator)
    {
        $idPermintaan = null;

        // Semua langkah dibungkus transaksi: kode pesanan, baris permintaan,
        // dan baris lampiran harus sukses bersama. Kalau insert gagal, tidak
        // ada nomor urut yang terpakai percuma.
        try {
            $permintaan = DB::transaction(function () use ($request, $generator, &$idPermintaan) {
                $permintaan = PermintaanService::create([
                    // Data yang sudah divalidasi. id_pelanggan tidak ada di sini
                    // karena request yang mengirimnya ditolak (prohibited).
                    ...$request->validated(),

                    // Pemilik diambil dari token, bukan dari body. Inilah yang
                    // mencegah pelanggan membuat permintaan atas nama orang lain.
                    'id_pelanggan' => $request->user()->id_pelanggan,

                    // Selalu ditentukan server agar tidak bisa dipalsukan.
                    'kode_pesanan' => $generator->buat(),
                    'status_permintaan' => 'Mencari Penawaran',

                    // Diisi eksplisit supaya response langsung memuat tanggal,
                    // tidak menunggu default dari database.
                    'waktu_dibuat' => now(),
                ]);

                $idPermintaan = $permintaan->id_permintaan;

                // File disimpan setelah baris permintaan ada, karena folder
                // penyimpanan memakai id_permintaan.
                $lampiran = app(LampiranService::class)->simpan(
                    $idPermintaan,
                    $request->file('lampiran', []) // array dari lampiran[]
                );

                // Agar resource bisa langsung menampilkan lampiran tanpa
                // query ulang ke database.
                $permintaan->setRelation('lampiran', collect($lampiran));

                return $permintaan;
            });
        } catch (\Throwable $e) {
            // Transaksi sudah membatalkan baris database. File yang telanjur
            // tersimpan perlu dibersihkan supaya tidak jadi sampah.
            if ($idPermintaan !== null) {
                app(LampiranService::class)->hapusFolder($idPermintaan);
            }

            throw $e;
        }

        // 201 Created: data baru berhasil dibuat.
        return (new PermintaanResource($permintaan))
            ->response()
            ->setStatusCode(201);
    }

    // GET /api/permintaan/{id}
    //
    // Detail satu permintaan. Hanya pemilik yang boleh melihat
    // (dicek lewat PermintaanPolicy::view).
    public function show(int $id)
    {
        // Relasi dimuat sekaligus supaya tidak ada query berulang (N+1).
        $permintaan = PermintaanService::with([
            'lampiran',
            // Penawaran + data mitra yang dipakai di kartu penawaran.
            'penawaran.mitra:id_mitra,nama_usaha,rating_rata_rata',
            // Pesanan dipakai helper status kanonik.
            'pesanan',
        ])->findOrFail($id);

        // 403 kalau bukan pemilik, 404 sudah ditangani findOrFail.
        $this->authorize('view', $permintaan);

        return new PermintaanDetailResource($permintaan);
    }

    // POST /api/permintaan/{id}/batalkan
    //
    // BR-06: pembatalan hanya boleh sebelum ada pesanan aktif.
    // Setelah penawaran disetujui, pelanggan tidak bisa membatalkan
    // lewat endpoint ini.
    public function batalkan(int $id)
    {
        $permintaan = PermintaanService::with('pesanan')->findOrFail($id);

        // Hanya pemilik permintaan yang boleh membatalkan.
        $this->authorize('update', $permintaan);

        // Sudah dibatalkan sebelumnya: tidak ada yang perlu diubah.
        if ($permintaan->status_permintaan === StatusPesanan::PERMINTAAN_DIBATALKAN) {
            throw ValidationException::withMessages([
                'status_permintaan' => 'Permintaan ini sudah dibatalkan.',
            ]);
        }

        // BR-06: pesanan aktif menandakan penawaran sudah disetujui,
        // jadi proses lelang sudah tidak bisa dibatalkan sepihak.
        if ($permintaan->pesanan !== null) {
            throw ValidationException::withMessages([
                'status_permintaan' => 'Permintaan tidak dapat dibatalkan karena sudah ada pesanan.',
            ]);
        }

        DB::transaction(function () use ($permintaan) {
            $permintaan->update([
                'status_permintaan' => StatusPesanan::PERMINTAAN_DIBATALKAN,
            ]);

            // Lampiran tidak berguna lagi setelah dibatalkan, sekaligus
            // menjaga storage tidak menumpuk file yatim.
            app(LampiranService::class)->hapusSemua($permintaan->id_permintaan);
        });

        // Relasi dimuat ulang: lampiran sekarang kosong dan status berubah.
        $permintaan->load('lampiran', 'penawaran.mitra', 'pesanan');

        return response()->json([
            'message' => 'Permintaan berhasil dibatalkan.',
            'data'    => (new PermintaanDetailResource($permintaan))->resolve(),
        ]);
    }
}
