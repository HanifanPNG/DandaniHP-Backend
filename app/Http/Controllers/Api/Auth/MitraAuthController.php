<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MitraService;
use Illuminate\Support\Facades\Hash;

class MitraAuthController extends Controller
{
    public function register(Request $request)
    {
        // Implement registration logic for Mitra
        $data = $request->validate([
            'nama_pemilik' => 'required|string|max:100',
            'nama_usaha' => 'required|string|max:100',
            'email' => 'required|string|email|max:100|unique:mitra_service,email',
            'nomor_telepon' => 'required|string|max:20',
            'alamat_usaha' => 'required|string|max:255',
            'kata_sandi' => 'required|string|min:8|confirmed',
        ]);

        $data['status_verifikasi'] = 'Menunggu';
        $mitra = MitraService::create($data);

        return response()->json([
            'message' => 'Mitra registered successfully',
            'data' => $mitra,
            'token' => $mitra->createToken('mobile')->plainTextToken
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string',
            'kata_sandi' => 'required|string',
        ]);
        $mitra = MitraService::where('email', $data['login'])
            ->orWhere('nomor_telepon', $data['login'])
            ->first();

        if (!$mitra || !Hash::check($data['kata_sandi'], $mitra->kata_sandi)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        return response()->json([
            'message' => 'Login successful',
            'data' => $mitra,
            'token' => $mitra->createToken('mobile')->plainTextToken
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(['data' => $request->user()]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}
