<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSlotRequest;
use App\Http\Requests\UpdateSlotRequest;
use App\Http\Resources\SlotResource;
use App\Models\Event;
use App\Models\PerformanceSlot;
use App\Services\SlotDeletionService;
use Illuminate\Http\JsonResponse;

/**
 * Route param names sengaja `{event}` dan `{slot}` (implicit route-model binding)
 * supaya cocok dengan StoreSlotRequest::route('event') dan
 * UpdateSlotRequest::route('slot') yang sudah ditulis lebih dulu.
 */
class AdminSlotController extends Controller
{
    public function __construct(private readonly SlotDeletionService $deletionService)
    {
    }

    /**
     * POST /api/admin/events/{event}/slots — §5.5 kontrak. Tambah slot baru ke event.
     */
    public function store(StoreSlotRequest $request, Event $event): JsonResponse
    {
        $slot = PerformanceSlot::create([
            'event_id' => $event->id,
            'band_id' => $request->validated('band_id'),
            'start_time' => $request->validated('start_time'),
            'end_time' => $request->validated('end_time'),
            'price' => $request->validated('price'),
            'stock_total' => $request->validated('stock_total'),
            'stock_sold' => 0,
        ]);

        $slot->load('band');

        return response()->json([
            'success' => true,
            'data' => (new SlotResource($slot))->resolve(),
        ], 201);
    }

    /**
     * PUT /api/admin/slots/{slot} — §5.5 kontrak. Field opsional.
     */
    public function update(UpdateSlotRequest $request, PerformanceSlot $slot): JsonResponse
    {
        $slot->fill($request->validated())->save();
        $slot->load('band');

        return response()->json([
            'success' => true,
            'data' => (new SlotResource($slot))->resolve(),
        ], 200);
    }

    /**
     * DELETE /api/admin/slots/{slot} — §5.5 kontrak. Hapus slot ikut menghapus
     * tiket yang sudah terjual untuk slot itu.
     */
    public function destroy(PerformanceSlot $slot): JsonResponse
    {
        $result = $this->deletionService->delete($slot);

        return response()->json([
            'success' => true,
            'data' => $result,
        ], 200);
    }
}
