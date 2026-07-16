<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * POST /api/register — §5.1 kontrak. Tidak langsung login.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'username' => $request->validated('username'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
            'role' => 'user',
        ]);

        $user->profile()->create([]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ], 201);
    }

    /**
     * POST /api/login — §5.1 kontrak. `login` menerima username ATAU email.
     * Token expire 2 jam fix (§1.3), dihitung dari created_at token oleh
     * CheckTokenExpiry middleware; expires_at di response murni informatif untuk UI.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $login = $request->validated('login');
        $password = $request->validated('password');

        $user = User::where('username', $login)
            ->orWhere('email', $login)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Username/email atau password salah',
            ], 401);
        }

        $token = $user->createToken('api-token');
        $expiresAt = $token->accessToken->created_at->copy()->addHours(2);

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token->plainTextToken,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'expires_at' => $expiresAt->setTimezone('Asia/Jakarta')->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * POST /api/logout — hapus token yang sedang dipakai.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'data' => null,
        ], 200);
    }

    /**
     * GET /api/me — §5.2 kontrak.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('profile');

        return response()->json([
            'success' => true,
            'data' => (new UserResource($user))->resolve(),
        ], 200);
    }
}
