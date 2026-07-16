<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * §1.3 kontrak: token expire 2 jam FIX sejak issued (created_at token),
 * bukan sliding/rolling expiration. Jika lewat, hapus token tsb dan balas
 * 401 dengan message: "SESSION_EXPIRED" persis (frontend mencocokkan string ini).
 *
 * Dijalankan SETELAH auth:sanctum di route group, supaya $request->user()
 * dan currentAccessToken() sudah tersedia.
 */
class CheckTokenExpiry
{
    private const EXPIRY_HOURS = 2;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if ($user && $token) {
            $issuedAt = $token->created_at;

            if ($issuedAt && $issuedAt->addHours(self::EXPIRY_HOURS)->isPast()) {
                $token->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'SESSION_EXPIRED',
                ], 401);
            }
        }

        return $next($request);
    }
}
