<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBandRequest;
use App\Http\Requests\UpdateBandRequest;
use App\Http\Resources\BandResource;
use App\Models\Band;
use App\Services\BandDeletionService;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBandController extends Controller
{
    public function __construct(
        private readonly BandDeletionService $deletionService,
        private readonly FileUploadService $fileUploadService,
    ) {
    }

    /**
     * GET /api/admin/bands — §5.6 kontrak. Query page, per_page, search (LIKE name).
     * Soft-deleted bands tidak muncul (default Eloquent scope).
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $page = (int) $request->query('page', 1);

        $query = Band::query()->orderBy('name');

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => BandResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 200);
    }

    /**
     * POST /api/admin/bands — §5.6 kontrak. Multipart, logo opsional.
     */
    public function store(StoreBandRequest $request): JsonResponse
    {
        $band = Band::create([
            'name' => $request->validated('name'),
            'genre' => $request->validated('genre'),
            'description' => $request->validated('description'),
        ]);

        if ($request->hasFile('logo')) {
            $path = $this->fileUploadService->store($request->file('logo'), 'logos', $band->id);
            $band->logo_path = $path;
            $band->save();
        }

        return response()->json([
            'success' => true,
            'data' => (new BandResource($band))->resolve(),
        ], 201);
    }

    /**
     * PUT /api/admin/bands/{band} — §5.6 kontrak. Field opsional, multipart jika ganti logo.
     */
    public function update(UpdateBandRequest $request, Band $band): JsonResponse
    {
        $band->fill($request->safe()->except('logo'))->save();

        if ($request->hasFile('logo')) {
            $this->fileUploadService->deleteIfExists($band->logo_path);
            $band->logo_path = $this->fileUploadService->store($request->file('logo'), 'logos', $band->id);
            $band->save();
        }

        return response()->json([
            'success' => true,
            'data' => (new BandResource($band))->resolve(),
        ], 200);
    }

    /**
     * DELETE /api/admin/bands/{band} — §5.6 kontrak. BR-2/BR-3.
     */
    public function destroy(Band $band): JsonResponse
    {
        $result = $this->deletionService->delete($band);

        return response()->json([
            'success' => true,
            'data' => $result,
        ], 200);
    }

    /**
     * POST /api/admin/bands/{band}/logo — §5.5b kontrak. Drag & drop upload,
     * endpoint hanya terima file biasa (logic drag-drop murni di frontend).
     */
    public function uploadLogo(Request $request, Band $band): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,png', 'max:2048'],
        ]);

        $this->fileUploadService->deleteIfExists($band->logo_path);
        $path = $this->fileUploadService->store($request->file('logo'), 'logos', $band->id);

        $band->logo_path = $path;
        $band->save();

        return response()->json([
            'success' => true,
            'data' => ['logo_path' => $path],
        ], 200);
    }
}
