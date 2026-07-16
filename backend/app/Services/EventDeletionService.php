<?php

namespace App\Services;

use App\Models\Event;
use App\Models\PerformanceSlot;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class EventDeletionService
{
    /**
     * BR-4: menghapus event tidak memengaruhi data band sama sekali — band tetap ada,
     * hanya kehilangan slot & tiket terkait event tsb (cascade lewat FK).
     *
     * @return array{deleted_event: string, deleted_slots_count: int, deleted_tickets_count: int}
     */
    public function delete(Event $event): array
    {
        return DB::transaction(function () use ($event) {
            $slotIds = PerformanceSlot::where('event_id', $event->id)->pluck('id');
            $ticketsCount = Ticket::whereIn('performance_slot_id', $slotIds)->count();
            $slotsCount = $slotIds->count();
            $eventTitle = $event->title;

            // Hard delete event -> cascade ke performance_slots (FK cascadeOnDelete)
            // -> cascade ke tickets (FK cascadeOnDelete). Band sama sekali tidak tersentuh.
            $event->delete();

            return [
                'deleted_event' => $eventTitle,
                'deleted_slots_count' => $slotsCount,
                'deleted_tickets_count' => $ticketsCount,
            ];
        });
    }
}
