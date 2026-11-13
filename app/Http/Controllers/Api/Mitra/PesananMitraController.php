<?php

namespace App\Http\Controllers\Api\Mitra;

use App\Http\Controllers\Controller;
use App\Http\Resources\PesananResource;
use App\Models\PesananAktif;
use App\Support\StatusPesanan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PesananMitraController extends Controller
{
    // GET /api/mitra/pesanan?status=
    //
    // Semua pesanan milik mitra yang login. Filter status opsional.
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(StatusPesanan::DAFTAR_PENGERJAAN)],
        ]);

        $pesanan = PesananAktif::with(['permintaan', 'mitra', 'penawaran'])
            ->where('id_mitra', $request->user()->id_mitra)
            ->when($validated['status'] ?? null, function ($q, $status) {
                $q->where('status_pengerjaan', $status);
            })
            // Yang baru berubah status tampil paling atas.
            ->orderByDesc('waktu_update_terakhir')
            ->orderByDesc('id_pesanan')
            ->get();

        return PesananResource::collection($pesanan);
    }
}
