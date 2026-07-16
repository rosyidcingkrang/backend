<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventDetailResource;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\EventStatusView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    /**
     * GET /api/events — §5.3 kontrak. Publik. Filter `search` (LIKE title),
     * `status` (live|upcoming|past dari computed_status), pagination page/per_page.
     * Wajib pakai view v_event_status (§2.2), bukan hitung status manual di PHP.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $page = (int) $request->query('page', 1);

        $query = EventStatusView::query()
            ->select('v_event_status.*')
            ->selectSub(
                DB::table('performance_slots')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('performance_slots.event_id', 'v_event_status.id'),
                'slot_count'
            )
            ->orderBy('event_date', 'asc');

        if ($search = $request->query('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($status = $request->query('status')) {
            $query->where('computed_status', $status);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => EventResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 200);
    }

    /**
     * GET /api/events/{id} — §5.3 kontrak. Publik. 404 jika tidak ada.
     * Pakai Eloquent (bukan view) supaya bisa eager-load slots.band.
     */
    public function show(int $id): JsonResponse
    {
        $event = Event::with(['slots' => fn ($q) => $q->orderBy('start_time'), 'slots.band'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => (new EventDetailResource($event))->resolve(),
        ], 200);
    }
}
