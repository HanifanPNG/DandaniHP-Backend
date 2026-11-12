<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMitraVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $user = $request->user();

        if(!$user || $user->status_verifikasi !== 'Disetujui') {
            return response()->json([
                'message' => 'Akun belum diverifikasi'
                ], 403);
            }
        return $next($request);
        
    }
}
