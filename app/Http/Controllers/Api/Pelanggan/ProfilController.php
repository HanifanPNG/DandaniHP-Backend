<?php

namespace App\Http\Controllers\Api\Pelanggan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pelanggan\UpdateProfilRequest;
use App\Http\Resources\PelangganResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfilController extends Controller
{
    // GET /api/profil
    //
    // Lihat profil pelanggan yang sedang login.
    // Status verifikasi tidak boleh diubah.
    public function show(Request $request)
    {
        $pelanggan = $request->user();

        return response()->json([
            'data' => new PelangganResource($pelanggan),
        ]);
    }

    // PUT /api/profil
    //
    // Update profil pelanggan.
    // - Hanya: nama_lengkap, nomor_telepon, alamat, foto_profil
    // - Status verifikasi tidak bisa diubah
    // - Upload foto: image, maks 2MB
    // - Unique telepon dengan pengecualian id sendiri (BR-11)
    public function update(UpdateProfilRequest $request)
    {
        $pelanggan = $request->user();

        $data = $request->validated();

        // Upload foto profil jika ada
        if (isset($data['foto_profil'])) {
            // Hapus foto lama jika ada
            if ($pelanggan->foto_profil) {
                Storage::disk('public')->delete($pelanggan->foto_profil);
            }

            // Simpan foto baru
            $data['foto_profil'] = $request->file('foto_profil')->store('foto-profil', 'public');
        }

        // Update profil
        $pelanggan->update($data);

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'data'    => new PelangganResource($pelanggan->fresh()),
        ]);
    }
}