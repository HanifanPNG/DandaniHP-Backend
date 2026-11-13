<?php

namespace App\Http\Controllers\Api\Pelanggan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pelanggan\StorePermintaanRequest;
use App\Http\Resources\PermintaanResource;
use App\Models\PermintaanService;
use App\Services\KodePesananGenerator;
use Illuminate\Support\Facades\DB;

class PermintaanController extends Controller
{
    // POST /api/permintaan
    //
    // Hanya pelanggan terverifikasi yang boleh membuat permintaan
    // (dijaga middleware verified.pelanggan, BR-01).
    public function store(StorePermintaanRequest $request, KodePesananGenerator $generator)
    {
        // Semua langkah dibungkus transaksi: kode pesanan dan baris
        // permintaan harus sukses bersama. Kalau insert gagal, tidak ada
        // nomor urut yang terpakai percuma.
        $permintaan = DB::transaction(function () use ($request, $generator) {
            return PermintaanService::create([
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
        });

        // 201 Created: data baru berhasil dibuat.
        return (new PermintaanResource($permintaan))
            ->response()
            ->setStatusCode(201);
    }
}
