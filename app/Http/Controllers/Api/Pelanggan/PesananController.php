<?php

namespace App\Http\Controllers\Api\Pelanggan;

use App\Http\Controllers\Controller;
use App\Http\Resources\PesananDetailResource;
use App\Http\Resources\PesananResource;
use App\Models\PesananAktif;
use App\Support\StatusPesanan;

class PesananController extends Controller
{
    // GET /api/pesanan/{id}
    //
    // Detail pesanan: data HP, kontak mitra, timeline, dan pembayaran.
    // Hanya pemilik atau mitra terkait (PesananPolicy).
    public function show(int $id)
    {
        // Semua relasi dimuat sekaligus supaya response tidak
        // memicu query berulang (N+1).
        $pesanan = PesananAktif::with([
            'permintaan',
            'mitra',
            'penawaran',
            'pembayaran',
            'riwayat',
        ])->findOrFail($id);

        $this->authorize('view', $pesanan);

        return new PesananDetailResource($pesanan);
    }
}
