<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Administrator;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    //
    public function login(Request $request)
    {
        // Implement login logic for Admin
        $data = $request->validate([
            'email' => 'required|string|email',
            'kata_sandi' => 'required|string',
        ]);

        $admin = Administrator::where('email', $data['email'])->first();

        if (!
        $admin || !Hash::check($data['kata_sandi'], $admin->kata_sandi)) {
            return response()->json(['message' => 'Email atau kata sandi salah'], 401);
        }

        return response()->json([
            'message' => 'Login successful',
            'data' => $admin,
            'token' => $admin->createToken('mobile')->plainTextToken
        ]);
    }
}
