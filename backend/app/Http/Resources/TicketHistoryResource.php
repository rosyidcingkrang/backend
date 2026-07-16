<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * §5.4 kontrak — GET /api/tickets/history, dibangun dari model TicketHistoryView
 * (view v_ticket_history) supaya tidak N+1. Format:
 * { ticket_id, ticket_code, event_title, event_date, band_name, start_time,
 *   quantity, total_price, purchased_at }
 */
class TicketHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ticket_id' => $this->ticket_id,
            'ticket_code' => $this->ticket_code,
            'event_title' => $this->event_title,
            'event_date' => $this->event_date instanceof \DateTimeInterface
                ? $this->event_date->format('Y-m-d')
                : (string) $this->event_date,
            'band_name' => $this->band_name,
            'start_time' => substr((string) $this->start_time, 0, 5),
            'quantity' => (int) $this->quantity,
            'total_price' => (float) $this->total_price,
            'purchased_at' => Carbon::parse($this->purchased_at)->setTimezone('Asia/Jakarta')->toIso8601String(),
        ];
    }
}
