<?php

namespace App\Http\Controllers\Api\Mitra;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mitra\UpdateStatusRequest;
use App\Http\Resources\PesananDetailResource;
use App\Http\Resources\PesananResource;
use App\Models\PesananAktif;
use App\Services\PesananService;
use App\Support\StatusPesanan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PembayaranMitraController extends Controller
{
    // POST /api/mitra/pesanan/{id}/pembayaran
    //
    // Mitra mengkonfirmasi pembayaran tunai (BR-08).
    //
    // Aturan:
    // - Hanya mitra pemilik pesanan (PesananPolicy::update).
    // - Status pesanan harus `Menunggu Pembayaran Tunai`.
    // - Nominal dan metode tidak boleh dari klien (harga dari penawaran).
    // - Tidak ada validasi input lain selain status pesanan (dilihat di service).
    public function konfirmasi(int $id, PesananService $service)
    {
        $pesanan = PesananAktif::findOrFail($id);

        // Hanya mitra pemilik pesanan yang boleh konfirmasi pembayaran.
        $this->authorize('update', $pesanan);

        $pesanan = $service->konfirmasi($pesanan);

        // Pastikan relation pembayaran ter-load untuk resource.
        $pesanan->load('pembayaran');

        return response()->json([
            'message' => 'Pembayaran tunai berhasil dikonfirmasi.',
            'data'    => (new PesananDetailResource($pesanan))->resolve(),
        ]);
    }
}