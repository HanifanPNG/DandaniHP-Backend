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
use App\Http\Controllers\Api\Mitra\PermintaanTerbukaController;
use App\Http\Controllers\Api\Mitra\PenawaranMitraController;
use App\Http\Controllers\Api\Mitra\PesananMitraController;
use App\Http\Controllers\Api\Mitra\PembayaranMitraController;
use App\Http\Controllers\Api\Pelanggan\UlasanController;


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

    // Riwayat service (semua permintaan milik pelanggan).
    Route::get('/riwayat', [RiwayatController::class, 'index']);
    Route::get('/riwayat/{id}', [RiwayatController::class, 'show'])->whereNumber('id');
});

// Aksi pelanggan yang butuh akun terverifikasi (BR-01).
Route::middleware(['auth:pelanggan', 'verified.pelanggan'])->group(function () {
    Route::post('/permintaan', [PermintaanController::class, 'store']);
    Route::post('/pesanan/{id}/ulasan', [UlasanController::class, 'store'])
        ->whereNumber('id');
});

// Aksi mitra terverifikasi.
Route::middleware(['auth:mitra', 'verified.mitra'])->group(function () {
    Route::get('/mitra/permintaan', [PermintaanTerbukaController::class, 'index']);
    Route::post('/mitra/permintaan/{id}/penawaran', [PenawaranMitraController::class, 'store'])
        ->whereNumber('id');

    // Pesanan yang sedang dikerjakan mitra.
    Route::get('/mitra/pesanan', [PesananMitraController::class, 'index']);
    Route::post('/mitra/pesanan/{id}/status', [PesananMitraController::class, 'ubahStatus'])
        ->whereNumber('id');

    // Konfirmasi pembayaran tunai (BR-08).
    Route::post('/mitra/pesanan/{id}/pembayaran', [PembayaranMitraController::class, 'konfirmasi'])
        ->whereNumber('id');
});
