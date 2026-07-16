<?php

namespace App\Services;

use App\Models\PerformanceSlot;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class SlotDeletionService
{
    /**
     * Menghapus slot ikut menghapus tiket yang sudah terjual untuk slot itu
     * (konsekuensi ini dijelaskan di modal konfirmasi frontend, BR-5).
     *
     * @return array{deleted_slot_id: int, deleted_tickets_count: int}
     */
    public function delete(PerformanceSlot $slot): array
    {
        return DB::transaction(function () use ($slot) {
            $ticketsCount = Ticket::where('performance_slot_id', $slot->id)->count();
            $slotId = $slot->id;

            // Hard delete slot -> cascade ke tickets (FK cascadeOnDelete di migration).
            $slot->delete();

            return [
                'deleted_slot_id' => $slotId,
                'deleted_tickets_count' => $ticketsCount,
            ];
        });
    }
}
