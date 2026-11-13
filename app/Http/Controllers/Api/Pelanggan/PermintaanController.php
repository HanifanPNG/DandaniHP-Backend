<?php

namespace App\Http\Controllers\Api\Pelanggan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pelanggan\StorePermintaanRequest;
use App\Http\Resources\PermintaanResource;
use App\Models\PermintaanService;
use App\Services\KodePesananGenerator;
use App\Services\LampiranService;
use Illuminate\Support\Facades\DB;

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
}
