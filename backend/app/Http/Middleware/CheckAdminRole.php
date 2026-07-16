<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * §1.4 kontrak: middleware role:admin untuk semua route /api/admin/*.
 * 403 untuk token valid tapi bukan role admin.
 */
class CheckAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk melakukan aksi ini',
            ], 403);
        }

        return $next($request);
    }
}
