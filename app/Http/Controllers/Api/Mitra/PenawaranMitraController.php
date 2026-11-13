<?php

namespace App\Http\Controllers\Api\Mitra;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mitra\StorePenawaranRequest;
use App\Http\Resources\PenawaranResource;
use App\Models\Notifikasi;
use App\Models\PenawaranMitra;
use App\Models\PermintaanService;
use App\Support\StatusPesanan;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenawaranMitraController extends Controller
{
    // POST /api/mitra/permintaan/{id}/penawaran
    //
    // Mitra terverifikasi mengirim harga untuk satu permintaan terbuka.
    public function store(StorePenawaranRequest $request, int $id)
    {
        $permintaan = PermintaanService::findOrFail($id);

        // Lelang sudah ditutup -> tidak boleh ada penawaran baru.
        if ($permintaan->status_permintaan !== StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN) {
            throw ValidationException::withMessages([
                'permintaan' => 'Permintaan ini sudah tidak menerima penawaran.',
            ]);
        }

        // Pemilik penawaran selalu dari token (tidak bisa dipalsukan).
        $mitra = $request->user();

        // Cek duplikat lebih awal supaya pesan errornya jelas.
        $sudahAda = PenawaranMitra::where('id_permintaan', $id)
            ->where('id_mitra', $mitra->id_mitra)
            ->exists();

        if ($sudahAda) {
            throw ValidationException::withMessages([
                'id_mitra' => 'Anda sudah mengirim penawaran untuk permintaan ini.',
            ]);
        }

        try {
            $penawaran = DB::transaction(function () use ($request, $permintaan, $mitra) {
                $penawaran = PenawaranMitra::create([
                    ...$request->validated(),

                    'id_permintaan'    => $permintaan->id_permintaan,
                    'id_mitra'         => $mitra->id_mitra,
                    'status_penawaran' => StatusPesanan::PENAWARAN_MENUNGGU,
                    'waktu_penawaran'  => now(),
                ]);

                // Pelanggan diberi tahu ada penawaran masuk.
                Notifikasi::create([
                    'id_pelanggan' => $permintaan->id_pelanggan,
                    'tipe'         => 'Penawaran',
                    'judul'        => 'Penawaran baru',
                    'pesan'        => 'Mitra '.$mitra->nama_usaha.' mengirim penawaran untuk '
                        .$permintaan->kode_pesanan.'.',
                    'id_pesanan'   => null,
                    'waktu_dibuat' => now(),
                ]);

                return $penawaran;
            });
        } catch (QueryException $e) {
            // Lapis kedua: unique(id_permintaan, id_mitra) mencegah dua
            // penawaran saat dua request masuk hampir bersamaan.
            if (($e->errorInfo[1] ?? null) === 1062
                || str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                throw ValidationException::withMessages([
                    'id_mitra' => 'Anda sudah mengirim penawaran untuk permintaan ini.',
                ]);
            }

            throw $e;
        }

        return (new PenawaranResource($penawaran->load('mitra')))
            ->response()
            ->setStatusCode(201);
    }
}
