<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pelanggan;
use Illuminate\Support\Facades\Hash;

class PelangganAuthController extends Controller
{
    public function register(Request $request)
    {
        // Handle registration logic here
        $data = $request->validate([
            'nama_lengkap' => 'required|string|max:100',
            'email' => 'required|string|email|max:100|unique:pelanggan,email',
            'nomor_telepon' => 'required|string|max:20|unique:pelanggan,nomor_telepon',
            'alamat' => 'required|string|max:255',
            'kata_sandi' => 'required|string|min:8|confirmed',
        ]);

        $data['status_verifikasi'] = 'Menunggu';
        $pelanggan = Pelanggan::create($data);

        return response()->json([
            'message' => 'Pelanggan registered successfully',
            'data' => $pelanggan,
            'token' => $pelanggan->createToken('mobile')->plainTextToken
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string',
            'kata_sandi' => 'required|string',
        ]);
        $pelanggan = Pelanggan::where('email', $data['login'])
            ->orWhere('nomor_telepon', $data['login'])
            ->first();

        if (!$pelanggan || !Hash::check($data['kata_sandi'], $pelanggan->kata_sandi)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        return response()->json([
            'message' => 'Login successful',
            'data' => $pelanggan,
            'token' => $pelanggan->createToken('mobile')->plainTextToken
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
