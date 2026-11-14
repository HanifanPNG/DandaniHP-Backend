<?php

namespace App\Http\Controllers\Api\Pelanggan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pelanggan\StoreUlasanRequest;
use App\Http\Resources\UlasanDetailResource;
use App\Models\PesananAktif;
use App\Models\Ulasan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UlasanController extends Controller
{
    // POST /api/pesanan/{id}/ulasan
    //
    // BR-09: Hanya pemilik pesanan yang bisa kirim ulasan.
    // BR-10: Tidak ada ulasan yang duplikat (unique constraint DB).
    public function store(StoreUlasanRequest $request, int $id)
    {
        $pesanan = PesananAktif::with('mitra')
            ->where('id_pesanan', $id)
            ->where('id_pelanggan', $request->user()->id_pelanggan)
            ->firstOrFail();

        // BR-09: Status harus Pesanan Selesai.
        if ($pesanan->status_pengerjaan !== 'Pesanan Selesai') {
            return response()->json([
                'message' => 'Pesanan ini belum selesai, tidak dapat memberi ulasan.',
            ], 422);
        }

        // BR-10: Unique constraint DB mencegah duplikat.
        // Tambahan validasi di sini untuk pesan yang lebih jelas.
        if (Ulasan::where('id_pesanan', $pesanan->id_pesanan)->exists()) {
            return response()->json([
                'message' => 'Anda sudah memberi ulasan untuk pesanan ini.',
            ], 422);
        }

        $ulasan = DB::transaction(function () use ($request, $pesanan) {
            $data = $request->validated();

            // Upload foto hasil servis jika ada.
            if (isset($data['foto_ulasan'])) {
                $data['foto_ulasan'] = $request->file('foto_ulasan')
                    ->store('foto-ulasan', 'public');
            }

            return Ulasan::create([
                ...$data,

                'id_pesanan'  => $pesanan->id_pesanan,
                'id_pelanggan'=> $pesanan->id_pelanggan,
                'id_mitra'    => $pesanan->id_mitra,
                'waktu_ulasan'=> now(),
            ]);
        });

        // Rekalkulasi rating mitra (T-40) - observer handle otomatis.

        return (new UlasanDetailResource($ulasan->load('mitra')))
            ->response()
            ->setStatusCode(201);
    }
}