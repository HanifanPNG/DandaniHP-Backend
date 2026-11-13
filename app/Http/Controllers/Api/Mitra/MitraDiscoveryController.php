<?php

namespace App\Http\Controllers\Api\Mitra;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MitraService;
use App\Http\Resources\MitraResource;

class MitraDiscoveryController extends Controller
{
    //
    public function index(Request $request)
    {
        $query = MitraService::query()->where('status_verifikasi', 'Disetujui')
        ->with('layanan')->withCount('ulasan');

        $query->when($request->filled('q'), function ($q) use ($request) {
            $cari = '%' . $request->string('q') . '%';
            $q->where(function ($sub) use ($cari){
                $sub->where('nama_usaha', 'like', $cari)
                    ->orWhere('alamat_usaha', 'like', $cari)
                    ->orWhereHas('layanan', function ($layanan) use ($cari) {
                        $layanan->where('nama_layanan', 'like', $cari);
                    });
            });
        });

        // --- Filter layanan tertentu (mis. "Ganti LCD") ---
        $query->when($request->filled('layanan'), function ($q) use ($request) {
            $q->whereHas('layanan', function ($layanan) use ($request) {
                $layanan->where('nama_layanan', $request->string('layanan'));
            });
        });

        // --- Filter rating minimum (4.5 dst) ---
        $query->when($request->filled('rating_min'), function ($q) use ($request) {
            $q->where('rating_rata_rata', '>=', $request->float('rating_min'));
        });

        // --- Filter sedang buka ---
        $query->when($request->boolean('buka'), function ($q) {
            $sekarang = now('Asia/Jakarta')->format('H:i:s');

            $q->whereNotNull('jam_buka')
                ->whereNotNull('jam_tutup')
                ->whereTime('jam_buka', '<=', $sekarang)
                ->whereTime('jam_tutup', '>=', $sekarang);
        });

         // --- Pengurutan ---
        $sort = $request->input('sort', 'rekomendasi');
        if ($sort === 'rating') {
            $query->orderByDesc('rating_rata_rata')
                ->orderByDesc('ulasan_count');
        }
        else if( $sort === 'jarak' && $request->filled('lat') && $request->filled('lng')){
            $lat = $request->float('lat');
            $lng = $request->float('lng');

             // Rumus Haversine: jarak permukaan bumi dalam kilometer.
            // Tanda ? diisi berurutan: lat, lng, lat.
            $query->selectRaw(
                'mitra_service.*, '
                .'(6371 * acos('
                .'cos(radians(?)) * cos(radians(latitude))'
                .' * cos(radians(longitude) - radians(?))'
                .' + sin(radians(?)) * sin(radians(latitude))'
                .')) as jarak',
                [$lat, $lng, $lat]
            )
            ->orderByRaw('jarak IS NULL, jarak ASC');
        } else {
            $query->orderByDesc('rating_rata_rata');
        }

        // --- Paginasi ---
        // per_page dibatasi 1..50 agar client tidak bisa minta 10.000 baris.
        $perPage = min(max($request->integer('per_page', 10), 1), 50);

        $mitra = $query->paginate($perPage)
            // Supaya link paginasi berikutnya tetap membawa filter yang dipakai.
            ->appends($request->query());

        // Resource collection otomatis membungkus "data" + "meta" paginasi.
        return MitraResource::collection($mitra);

    }
}
