<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * §5.3 kontrak — GET /api/events/{id}. Dibangun dari Eloquent Event (bukan view),
 * karena butuh eager-loaded slots.band dan computedStatus(). Format:
 * { id, title, event_date, venue_note, poster_path, status, slots: [...] }
 */
class EventDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'event_date' => $this->event_date->format('Y-m-d'),
            'venue_note' => $this->venue_note,
            'poster_path' => $this->poster_path,
            'status' => $this->computedStatus(),
            'slots' => SlotResource::collection($this->slots),
        ];
    }
}
