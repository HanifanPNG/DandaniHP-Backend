<?php 

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\PelangganAuthController;

Route::get('/ping', function () {
    return response()->json(['message' => 'API is working!']);
});

// register
Route::post('/auth/register', [App\Http\Controllers\Api\Auth\PelangganAuthController::class, 'register']);
// login
Route::post('/auth/login', [App\Http\Controllers\Api\Auth\PelangganAuthController::class, 'login']);