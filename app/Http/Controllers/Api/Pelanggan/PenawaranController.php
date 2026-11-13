<?php

namespace App\Http\Controllers\Api\Pelanggan;

use App\Http\Controllers\Controller;
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
}
