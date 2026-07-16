<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventDetailResource;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\EventStatusView;
use App\Models\PerformanceSlot;
use App\Services\EventDeletionService;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminEventController extends Controller
{
    public function __construct(
        private readonly EventDeletionService $deletionService,
        private readonly FileUploadService $fileUploadService,
    ) {
    }

    /**
     * GET /api/admin/events — §5.5 kontrak. Sama seperti GET /api/events tapi
     * tanpa batasan status (termasuk past), plus field created_at.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $page = (int) $request->query('page', 1);

        $query = EventStatusView::query()
            ->select('v_event_status.*', 'events.created_at as created_at')
            ->join('events', 'events.id', '=', 'v_event_status.id')
            ->selectSub(
                DB::table('performance_slots')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('performance_slots.event_id', 'v_event_status.id'),
                'slot_count'
            )
            ->orderBy('event_date', 'desc');

        if ($search = $request->query('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => EventResource::adminCollection(collect($paginator->items())),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 200);
    }

    /**
     * POST /api/admin/events — §5.5 kontrak. Create event + slots sekaligus,
     * poster opsional lewat multipart field `poster` di request yang sama.
     */
    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = DB::transaction(function () use ($request) {
            $event = Event::create([
                'title' => $request->validated('title'),
                'event_date' => $request->validated('event_date'),
                'venue_note' => $request->validated('venue_note'),
            ]);

            foreach ($request->validated('slots') as $slotData) {
                PerformanceSlot::create([
                    'event_id' => $event->id,
                    'band_id' => $slotData['band_id'],
                    'start_time' => $slotData['start_time'],
                    'end_time' => $slotData['end_time'],
                    'price' => $slotData['price'],
                    'stock_total' => $slotData['stock_total'],
                    'stock_sold' => 0,
                ]);
            }

            if ($request->hasFile('poster')) {
                $path = $this->fileUploadService->store($request->file('poster'), 'posters', $event->id);
                $event->poster_path = $path;
                $event->save();
            }

            return $event;
        });

        $event->load(['slots' => fn ($q) => $q->orderBy('start_time'), 'slots.band']);

        return response()->json([
            'success' => true,
            'data' => (new EventDetailResource($event))->resolve(),
        ], 201);
    }

    /**
     * PUT /api/admin/events/{id} — §5.5 kontrak. Update title/event_date/venue_note saja.
     * Slots dikelola lewat endpoint terpisah.
     */
    public function update(UpdateEventRequest $request, int $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $event->fill($request->validated())->save();
        $event->load(['slots' => fn ($q) => $q->orderBy('start_time'), 'slots.band']);

        return response()->json([
            'success' => true,
            'data' => (new EventDetailResource($event))->resolve(),
        ], 200);
    }

    /**
     * DELETE /api/admin/events/{id} — §5.5 kontrak. Hard delete + cascade slot+tiket.
     * BR-4: tidak memengaruhi tabel bands.
     */
    public function destroy(int $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $result = $this->deletionService->delete($event);

        return response()->json([
            'success' => true,
            'data' => $result,
        ], 200);
    }

    /**
     * POST /api/admin/events/{id}/poster — §5.5b kontrak.
     */
    public function uploadPoster(Request $request, int $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        $request->validate([
            'poster' => ['required', 'image', 'mimes:jpeg,png', 'max:2048'],
        ]);

        $this->fileUploadService->deleteIfExists($event->poster_path);
        $path = $this->fileUploadService->store($request->file('poster'), 'posters', $event->id);

        $event->poster_path = $path;
        $event->save();

        return response()->json([
            'success' => true,
            'data' => ['poster_path' => $path],
        ], 200);
    }
}
