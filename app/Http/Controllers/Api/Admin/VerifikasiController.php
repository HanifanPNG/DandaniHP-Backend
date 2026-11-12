<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MitraService;
use App\Models\Pelanggan;
use Illuminate\Http\Request;


class VerifikasiController extends Controller
{
    public function verifikasiPelanggan(Request $request, $id)
    {
        $data = $request->validate([
            'status_verifikasi' => 'required|in:Disetujui,Ditolak',
            'alasan_penolakan' => 'required_if:status_verifikasi,Ditolak|nullable|string|max:255',
        ]);

        // 404 otomatis kalau id tidak ada.
        $pelanggan = Pelanggan::findOrFail($id);

        $pelanggan->status_verifikasi = $data['status_verifikasi'];

        // Saat disetujui, alasan penolakan lama dibersihkan agar tidak
        // membingungkan pelanggan di endpoint /api/auth/me.
        $pelanggan->alasan_penolakan = $data['status_verifikasi'] === 'Ditolak'
            ? $data['alasan_penolakan']
            : null;

        $pelanggan->save();

        return response()->json([
            'message' => 'Status verifikasi pelanggan berhasil diperbarui.',
            'data' => $pelanggan,
        ]);
    }

    public function verifikasiMitra(Request $request, $id)
    {
        $data = $request->validate([
            'status_verifikasi' => 'required|in:Disetujui,Ditolak',
        ]);

        $mitra = MitraService::findOrFail($id);
        $mitra->status_verifikasi = $data['status_verifikasi'];
        $mitra->save();

        return response()->json([
            'message' => 'Status verifikasi mitra berhasil diperbarui.',
            'data' => $mitra,
        ]);
    }
}
