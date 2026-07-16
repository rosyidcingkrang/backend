<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function __construct(private readonly FileUploadService $fileUploadService)
    {
    }

    /**
     * PUT /api/me/profile — §5.2 kontrak. Semua field opsional (kirim yang mau diubah saja).
     * Field yang tidak dikirim tidak diubah (bukan di-set null).
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $profile = $request->user()->profile;

        $profile->fill($request->validated())->save();

        return response()->json([
            'success' => true,
            'data' => [
                'full_name' => $profile->full_name,
                'phone' => $profile->phone,
                'avatar_path' => $profile->avatar_path,
            ],
        ], 200);
    }

    /**
     * POST /api/me/avatar — §5.2 kontrak. Multipart, field `avatar`, jpeg/png max 2MB.
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'avatar' => ['required', 'image', 'mimes:jpeg,png', 'max:2048'],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        $user = $request->user();
        $profile = $user->profile;

        $this->fileUploadService->deleteIfExists($profile->avatar_path);
        $path = $this->fileUploadService->store($request->file('avatar'), 'avatars', $user->id);

        $profile->avatar_path = $path;
        $profile->save();

        return response()->json([
            'success' => true,
            'data' => ['avatar_path' => $path],
        ], 200);
    }
}
