<?php

namespace App\Http\Controllers\Api\Pelanggan;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotifikasiResource;
use App\Models\Notifikasi;
use App\Services\NotifikasiService;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    // GET /api/notifikasi
    //
    // Menampilkan daftar notifikasi pelanggan, dikelompokkan:
    // - hari_ini (waktu_dibuat hari ini)
    // - kemarin (waktu_dibuat kemarin)
    // - sebelumnya (waktu_dibuat sebelum kemarin)
    // Plus jumlah belum dibaca.
    public function index(Request $request, NotifikasiService $service)
    {
        $pelanggan = $request->user();

        $notifikasi = Notifikasi::where('id_pelanggan', $pelanggan->id_pelanggan)
            ->latest('waktu_dibuat')
            ->get();

        // Kelompokkan berdasarkan hari
        $hariIni = $notifikasi->filter(function ($n) {
            return $n->waktu_dibuat->isToday();
        })->values();

        $kemarin = $notifikasi->filter(function ($n) {
            return $n->waktu_dibuat->isYesterday();
        })->values();

        $sebelumnya = $notifikasi->filter(function ($n) {
            return !$n->waktu_dibuat->isToday() && !$n->waktu_dibuat->isYesterday();
        })->values();

        $jumlahBelumDibaca = $service->belumDibacaPelanggan($pelanggan);

        return response()->json([
            'data' => [
                'hari_ini'          => NotifikasiResource::collection($hariIni),
                'kemarin'           => NotifikasiResource::collection($kemarin),
                'sebelumnya'        => NotifikasiResource::collection($sebelumnya),
                'jumlah_belum_dibaca' => $jumlahBelumDibaca,
            ],
        ]);
    }

    // POST /api/notifikasi/{id}/baca
    //
    // Tandai satu notifikasi sudah dibaca.
    // Hanya pemilik notifikasi yang boleh.
    public function baca(Request $request, int $id, NotifikasiService $service)
    {
        $pelanggan = $request->user();

        $notifikasi = Notifikasi::where('id_notifikasi', $id)
            ->where('id_pelanggan', $pelanggan->id_pelanggan)
            ->firstOrFail();

        $service->tandaiBaca($notifikasi);

        return response()->json([
            'message' => 'Notifikasi ditandai sudah dibaca.',
            'data'    => new NotifikasiResource($notifikasi->fresh()),
        ]);
    }

    // POST /api/notifikasi/baca-semua
    //
    // Tandai semua notifikasi milik pelanggan sudah dibaca.
    // Idempotent: boleh dipanggil berkali-kali.
    public function bacaSemua(Request $request, NotifikasiService $service)
    {
        $pelanggan = $request->user();

        $jumlah = $service->tandaiSemuaBacaPelanggan($pelanggan);

        return response()->json([
            'message' => 'Semua notifikasi ditandai sudah dibaca.',
            'data'    => ['jumlah_ditandai' => $jumlah],
        ]);
    }
}