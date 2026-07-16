<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminUserController extends Controller
{
    /**
     * GET /api/admin/users — §5.7 kontrak. Query page, per_page, search (LIKE username/email).
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $page = (int) $request->query('page', 1);

        $query = User::query()->with('profile')->orderBy('username');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $data = collect($paginator->items())->map(fn (User $user) => [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'full_name' => $user->profile?->full_name,
            'created_at' => Carbon::parse($user->created_at)->setTimezone('Asia/Jakarta')->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 200);
    }

    /**
     * PUT /api/admin/users/{user} — §5.7 kontrak. Admin bisa perbaiki username/email.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user->fill($request->validated())->save();
        $user->loadMissing('profile');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'full_name' => $user->profile?->full_name,
                'created_at' => Carbon::parse($user->created_at)->setTimezone('Asia/Jakarta')->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * DELETE /api/admin/users/{user} — §5.7 kontrak. Soft delete (BR-10).
     * Admin tidak bisa menghapus dirinya sendiri (403).
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Admin tidak dapat menghapus akunnya sendiri',
            ], 403);
        }

        $username = $user->username;
        $user->delete();

        return response()->json([
            'success' => true,
            'data' => ['deleted_user' => $username],
        ], 200);
    }
}
