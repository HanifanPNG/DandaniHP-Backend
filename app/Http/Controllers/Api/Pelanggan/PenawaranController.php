<?php

namespace App\Http\Controllers\Api\Pelanggan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pelanggan\TolakPenawaranRequest;
use App\Http\Resources\PenawaranResource;
use App\Http\Resources\PesananResource;
use App\Models\MitraService;
use App\Models\PenawaranMitra;
use App\Models\PermintaanService;
use App\Services\PenawaranService;
use App\Support\StatusPesanan;
use Illuminate\Http\Request;

class PenawaranController extends Controller
{
    // GET /api/penawaran/baru
    //
    // Menampilkan penawaran yang masuk dan menunggu keputusan pelanggan.
    // Dipakai di widget "Tawaran Baru" pada beranda.
    // Hanya penawaran berstatus Menunggu Keputusan milik pelanggan login.
    public function baru(Request $request)
    {
        $pelanggan = $request->user();

        $penawaran = PenawaranMitra::with(['mitra', 'permintaan'])
            ->where('status_penawaran', StatusPesanan::PENAWARAN_MENUNGGU)
            ->whereHas('permintaan', fn ($q) => $q
                ->where('id_pelanggan', $pelanggan->id_pelanggan)
                ->where('status_permintaan', StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN))
            ->orderByDesc('waktu_penawaran')
            ->get();

        return PenawaranResource::collection($penawaran);
    }

    // GET /api/permintaan/{id}/penawaran
    //
    // Menampilkan semua penawaran yang masuk untuk satu permintaan.
    // Hanya pemilik permintaan yang boleh melihat (BR-03/D1).
    public function index(Request $request, int $id)
    {
        $permintaan = PermintaanService::findOrFail($id);

        // Bukan pemilik -> 403.
        $this->authorize('view', $permintaan);

        $query = PenawaranMitra::with('mitra')
            ->where('id_permintaan', $id)
            // Penawaran kedaluwarsa tidak perlu ditampilkan lagi
            // karena sudah bukan pilihan yang bisa disetujui.
            ->where('status_penawaran', '!=', StatusPesanan::PENAWARAN_KEDALUWARSA);

        // Sort default: harga terendah supaya penawaran termurah
        // langsung terlihat. ?sort=rating mengurutkan mitra rating tertinggi.
        if ($request->query('sort') === 'rating') {
            $query->orderByDesc(
                MitraService::select('rating_rata_rata')
                    ->whereColumn('mitra_service.id_mitra', 'penawaran_mitra.id_mitra')
            );
        } else {
            $query->orderBy('harga_penawaran');
        }

        // Secondary sort agar urutan konsisten saat harga/rating sama.
        $penawaran = $query->orderBy('id_penawaran')->get();

        return PenawaranResource::collection($penawaran);
    }

    // POST /api/penawaran/{id}/setujui
    //
    // Pelanggan memilih satu pemenang. Semua perubahan status dan
    // pembuatan pesanan dilakukan PenawaranService dalam satu transaksi.
    public function setujui(int $id, PenawaranService $service)
    {
        $penawaran = PenawaranMitra::with('permintaan')->findOrFail($id);

        // Hanya pemilik permintaan induk yang boleh menyetujui.
        $this->authorize('update', $penawaran);

        $pesanan = $service->setujui($penawaran);

        // 201 Created: pesanan baru ikut terbuat.
        return response()->json([
            'message' => 'Penawaran disetujui. Pesanan masuk antrean mitra.',
            'data'    => (new PesananResource($pesanan))->resolve(),
        ], 201);
    }

    // POST /api/penawaran/{id}/tolak
    //
    // Menolak penawaran tidak membuat pesanan (BR-05).
    // Kalau semua penawaran ditolak, permintaan dianggap gugur
    // dan status kanoniknya menjadi rejected.
    public function tolak(TolakPenawaranRequest $request, int $id, PenawaranService $service)
    {
        $penawaran = PenawaranMitra::findOrFail($id);

        // Hanya pemilik permintaan induk yang boleh menolak.
        $this->authorize('update', $penawaran);

        $penawaran = $service->tolak(
            $penawaran,
            // Null kalau pelanggan tidak mengisi alasan.
            $request->validated('alasan_penolakan')
        );

        return response()->json([
            'message' => 'Penawaran berhasil ditolak.',
            'data'    => (new PenawaranResource($penawaran))->resolve(),
        ]);
    }
}
