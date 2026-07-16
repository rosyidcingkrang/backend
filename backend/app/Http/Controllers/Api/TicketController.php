<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseTicketRequest;
use App\Http\Resources\TicketHistoryResource;
use App\Models\Ticket;
use App\Models\TicketHistoryView;
use App\Services\TicketImageService;
use App\Services\TicketPurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketPurchaseService $purchaseService,
        private readonly TicketImageService $imageService,
    ) {
    }

    /**
     * POST /api/tickets — §5.4 kontrak. Beli tiket, 1 request = 1 transaksi 1 slot.
     */
    public function store(PurchaseTicketRequest $request): JsonResponse
    {
        $ticket = $this->purchaseService->purchase(
            $request->user(),
            (int) $request->validated('performance_slot_id'),
            (int) $request->validated('quantity'),
        );

        $ticket->loadMissing('performanceSlot.event', 'performanceSlot.band');

        return response()->json([
            'success' => true,
            'data' => [
                'ticket_id' => $ticket->id,
                'ticket_code' => $ticket->ticket_code,
                'quantity' => (int) $ticket->quantity,
                'unit_price' => (float) $ticket->unit_price,
                'total_price' => (float) $ticket->total_price,
                'purchased_at' => $ticket->purchased_at->setTimezone('Asia/Jakarta')->toIso8601String(),
                'event_title' => $ticket->performanceSlot->event->title,
                'band_name' => $ticket->performanceSlot->band->name,
            ],
        ], 201);
    }

    /**
     * GET /api/tickets/history — §5.4 kontrak. Wajib pakai view v_ticket_history.
     */
    public function history(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $page = (int) $request->query('page', 1);

        $paginator = TicketHistoryView::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('purchased_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => TicketHistoryResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 200);
    }

    /**
     * GET /api/tickets/{id}/download — §5.4 kontrak. Binary JPEG (QR + detail).
     * 404 jika tiket tidak ada / milik user lain (tidak bocorkan keberadaan ID orang lain).
     */
    public function download(Request $request, int $id): Response
    {
        $ticket = Ticket::find($id);

        if (! $ticket || $ticket->user_id !== $request->user()->id) {
            throw new NotFoundHttpException('Data tidak ditemukan');
        }

        $binary = $this->imageService->generate($ticket);

        return response($binary, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => "attachment; filename=\"{$ticket->ticket_code}.jpg\"",
        ]);
    }
}
