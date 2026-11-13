<?php

namespace App\Http\Controllers\Api\Mitra;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermintaanTerbukaResource;
use App\Models\PermintaanService;
use App\Support\StatusPesanan;
use Illuminate\Http\Request;

class PermintaanTerbukaController extends Controller
{
    // GET /api/mitra/permintaan
    //
    // Daftar lelang yang masih terbuka. Permintaan yang sudah pernah
    // ditawar mitra ini disembunyikan supaya tidak muncul dua kali.
    public function index(Request $request)
    {
        $validated = $request->validate([
            'kategori' => ['nullable', 'string', 'max:100'],
        ]);

        $mitra = $request->user();

        $permintaan = PermintaanService::query()
            ->with(['lampiran', 'pelanggan'])
            ->withCount('penawaran')
            ->where('status_permintaan', StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN)
            // Satu mitra hanya boleh menawar sekali per permintaan.
            ->whereDoesntHave('penawaran', function ($q) use ($mitra) {
                $q->where('id_mitra', $mitra->id_mitra);
            })
            ->when($validated['kategori'] ?? null, function ($q, $kategori) {
                $q->where('kategori_kerusakan', 'like', '%'.$kategori.'%');
            })
            // Lelang terbaru tampil lebih dulu.
            ->orderByDesc('waktu_dibuat')
            ->orderByDesc('id_permintaan')
            ->paginate(10);

        return PermintaanTerbukaResource::collection($permintaan);
    }
}
