<?php

namespace App\Http\Controllers\Api\Pelanggan;

use App\Http\Controllers\Controller;
use App\Http\Resources\RiwayatDetailResource;
use App\Http\Resources\RiwayatResource;
use App\Models\PermintaanService;
use App\Support\StatusPesanan;
use Illuminate\Http\Request;

class RiwayatController extends Controller
{
    // GET /api/riwayat?filter=semua|berlangsung|selesai|ditolak|dibatalkan
    //
    // Riwayat berbasis permintaan supaya permintaan yang ditolak atau
    // dibatalkan tetap muncul walau tidak punya pesanan.
    public function index(Request $request)
    {
        // Nilai filter selain daftar ini ditolak 422.
        $validated = $request->validate([
            'filter' => ['nullable', 'in:semua,berlangsung,selesai,ditolak,dibatalkan'],
        ]);

        $filter = $validated['filter'] ?? 'semua';

        $query = PermintaanService::with([
            'penawaran',
            // Data pesanan + mitra + harga untuk item yang sudah jadi pesanan.
            'pesanan.mitra',
            'pesanan.penawaran',
        ])->where('id_pelanggan', $request->user()->id_pelanggan);

        match ($filter) {
            // Pesanan belum selesai (termasuk antrean, proses, pembayaran).
            'berlangsung' => $query->whereHas(
                'pesanan',
                fn ($q) => $q->where(
                    'status_pengerjaan',
                    '!=',
                    StatusPesanan::PESANAN_SELESAI
                )
            ),
            'selesai' => $query->whereHas(
                'pesanan',
                fn ($q) => $q->where(
                    'status_pengerjaan',
                    StatusPesanan::PESANAN_SELESAI
                )
            ),
            // Rejected: tidak ada pesanan, punya penawaran, dan semuanya
            // berstatus Ditolak Pelanggan (tech spec 2.3).
            'ditolak' => $query
                ->whereDoesntHave('pesanan')
                ->where('status_permintaan', '!=', StatusPesanan::PERMINTAAN_DIBATALKAN)
                ->whereHas('penawaran')
                ->whereDoesntHave(
                    'penawaran',
                    fn ($q) => $q->where(
                        'status_penawaran',
                        '!=',
                        StatusPesanan::PENAWARAN_DITOLAK
                    )
                ),
            'dibatalkan' => $query->where(
                'status_permintaan',
                StatusPesanan::PERMINTAAN_DIBATALKAN
            ),
            default => $query,
        };

        // Terbaru di atas, lalu paginasi 10 per halaman.
        $riwayat = $query
            ->orderByDesc('waktu_dibuat')
            ->orderByDesc('id_permintaan')
            ->paginate(10);

        return RiwayatResource::collection($riwayat);
    }

    // GET /api/riwayat/{id}
    //
    // Detail read-only milik pelanggan sendiri (id = id_permintaan).
    public function show(int $id)
    {
        $permintaan = PermintaanService::with([
            'penawaran',
            'pesanan.mitra',
            'pesanan.penawaran',
            'pesanan.pembayaran',
            'pesanan.riwayat',
            'pesanan.ulasan',
        ])->findOrFail($id);

        $this->authorize('view', $permintaan);

        return new RiwayatDetailResource($permintaan);
    }
}
