<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * §5.3 kontrak — item di GET /api/events (list, dari v_event_status join slot_count).
 * Untuk GET /api/admin/events, controller menambahkan created_at (§5.5, "plus field
 * tambahan created_at") lewat properti tambahan yang di-set di controller sebelum
 * resource dibuat (lihat AdminEventController).
 */
class EventResource extends JsonResource
{
    public function __construct($resource, private readonly bool $withCreatedAt = false)
    {
        parent::__construct($resource);
    }

    public static function adminCollection($resource)
    {
        return $resource->map(fn ($item) => new self($item, true));
    }

    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'event_date' => $this->formatDate($this->event_date),
            'venue_note' => $this->venue_note,
            'poster_path' => $this->poster_path,
            'status' => $this->computed_status,
            'slot_count' => (int) $this->slot_count,
        ];

        if ($this->withCreatedAt) {
            $data['created_at'] = $this->formatDateTime($this->created_at);
        }

        return $data;
    }

    private function formatDate($value): ?string
    {
        if (! $value) {
            return null;
        }

        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : (string) $value;
    }

    private function formatDateTime($value): ?string
    {
        if (! $value) {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($value)->setTimezone('Asia/Jakarta')->toIso8601String();
    }
}
