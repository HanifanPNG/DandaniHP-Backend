<?php

namespace App\Http\Controllers\Api\Pelanggan;

use App\Http\Controllers\Controller;
use App\Http\Resources\PesananDetailResource;
use App\Http\Resources\PesananResource;
use App\Models\PesananAktif;
use App\Support\StatusPesanan;

class PesananController extends Controller
{
    // GET /api/pesanan
    //
    // Daftar pesanan yang masih berjalan milik pelanggan yang login.
    // Pesanan selesai dipindah ke riwayat, bukan di sini.
    public function index()
    {
        $pesanan = PesananAktif::with(['permintaan', 'mitra', 'penawaran'])
            ->where('id_pelanggan', request()->user()->id_pelanggan)
            ->where('status_pengerjaan', '!=', StatusPesanan::PESANAN_SELESAI)
            // Pesanan yang baru berubah status muncul paling atas.
            ->orderByDesc('waktu_update_terakhir')
            ->orderByDesc('id_pesanan')
            ->get();

        return PesananResource::collection($pesanan);
    }

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
