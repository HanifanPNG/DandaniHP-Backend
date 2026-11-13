<?php 

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\PelangganAuthController;
use App\Http\Controllers\Api\Auth\MitraAuthController;
use App\Http\Controllers\Api\Auth\AdminAuthController;
use App\Http\Controllers\Api\Admin\VerifikasiController;
use App\Http\Controllers\Api\Pelanggan\PermintaanController;
use App\Http\Controllers\Api\Pelanggan\PenawaranController;
use App\Http\Controllers\Api\Pelanggan\PesananController;
use App\Http\Controllers\Api\Pelanggan\RiwayatController;
use App\Http\Controllers\Api\Mitra\MitraDiscoveryController;


Route::get('/ping', function () {
    return response()->json(['message' => 'API is working!']);
});

// register
Route::post('/auth/register', [PelangganAuthController::class, 'register']);
// login
Route::post('/auth/login', [PelangganAuthController::class, 'login'])->middleware('throttle:5,1'); // Limit to 5 attempts per minute
// get current user
Route::middleware('auth:pelanggan')->group(
    function(){
        Route::get('/auth/me', [PelangganAuthController::class, 'me']);
        Route::post('/auth/logout', [PelangganAuthController::class, 'logout']);
    }
);

// Mitra routes
Route::post('/mitra/auth/register', [MitraAuthController::class, 'register']);
Route::post('/mitra/auth/login', [MitraAuthController::class, 'login'])->middleware('throttle:5,1'); // Limit to 5 attempts per minute
Route::middleware('auth:mitra')->group(
    function(){
        Route::get('/mitra/auth/me', [MitraAuthController::class, 'me']);
        Route::post('/mitra/auth/logout', [MitraAuthController::class, 'logout']);
    }
);

//admin routes
Route::post('/admin/auth/login', [AdminAuthController::class, 'login'])->middleware('throttle:5,1');

// verif admin
Route::middleware('auth:admin')->group(function () {
    Route::patch('/admin/pelanggan/{id}/verifikasi', [VerifikasiController::class, 'verifikasiPelanggan']);
    Route::patch('/admin/mitra/{id}/verifikasi', [VerifikasiController::class, 'verifikasiMitra']);
});

Route::middleware('auth:pelanggan')->group(function () {
    Route::get('/mitra', [MitraDiscoveryController::class, 'index']);
    Route::get('/mitra/{id}', [MitraDiscoveryController::class, 'show'])->whereNumber('id');
    Route::get('/mitra/{id}/ulasan', [MitraDiscoveryController::class, 'ulasan'])->whereNumber('id');

    // Detail permintaan: pemilik saja (dicek Policy).
    Route::get('/permintaan/{id}', [PermintaanController::class, 'show'])->whereNumber('id');

    // BR-06: batal hanya sebelum ada pesanan aktif.
    Route::post('/permintaan/{id}/batalkan', [PermintaanController::class, 'batalkan'])
        ->whereNumber('id');

    // Daftar penawaran untuk satu permintaan (pemilik saja).
    Route::get('/permintaan/{id}/penawaran', [PenawaranController::class, 'index'])
        ->whereNumber('id');

    // Menyetujui penawaran -> membuat pesanan aktif (BR-04).
    Route::post('/penawaran/{id}/setujui', [PenawaranController::class, 'setujui'])
        ->whereNumber('id');

    // Menolak penawaran -> tidak membuat pesanan (BR-05).
    Route::post('/penawaran/{id}/tolak', [PenawaranController::class, 'tolak'])
        ->whereNumber('id');

    // Pesanan aktif pelanggan.
    Route::get('/pesanan', [PesananController::class, 'index']);
    Route::get('/pesanan/{id}', [PesananController::class, 'show'])->whereNumber('id');
});

// Aksi pelanggan yang butuh akun terverifikasi (BR-01).
Route::middleware(['auth:pelanggan', 'verified.pelanggan'])->group(function () {
    Route::post('/permintaan', [PermintaanController::class, 'store']);
});
