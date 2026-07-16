<?php

namespace App\Services;

use App\Exceptions\BandStillLiveException;
use App\Models\Band;
use App\Models\PerformanceSlot;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class BandDeletionService
{
    /**
     * BR-2: band hanya bisa dihapus jika tidak sedang tampil di jam berjalan saat ini.
     * BR-3: menghapus band ikut menghapus slot & tiket masa depan; slot/tiket lampau
     *       tetap disimpan sebagai histori. Band sendiri di-soft-delete (bukan hard delete)
     *       supaya histori slot lampau tetap bisa join nama band.
     *
     * @return array{deleted_band: string, deleted_future_slots_count: int, deleted_future_tickets_count: int}
     */
    public function delete(Band $band): array
    {
        if ($this->isLiveNow($band)) {
            throw new BandStillLiveException($band->name);
        }

        return DB::transaction(function () use ($band) {
            $today = now()->toDateString();

            $futureSlotIds = PerformanceSlot::where('band_id', $band->id)
                ->whereHas('event', fn ($q) => $q->where('event_date', '>=', $today))
                ->pluck('id');

            $ticketsCount = Ticket::whereIn('performance_slot_id', $futureSlotIds)->count();

            // Hapus tiket dulu secara eksplisit, lalu slot (FK cascade juga akan
            // menghapus tiket sisa jika ada, tapi eksplisit lebih jelas untuk dibaca).
            Ticket::whereIn('performance_slot_id', $futureSlotIds)->delete();
            PerformanceSlot::whereIn('id', $futureSlotIds)->delete();

            $bandName = $band->name;
            $band->delete(); // soft delete (BR-3)

            return [
                'deleted_band' => $bandName,
                'deleted_future_slots_count' => $futureSlotIds->count(),
                'deleted_future_tickets_count' => $ticketsCount,
            ];
        });
    }

    /**
     * BR-2: cek apakah ada slot untuk band ini hari ini dengan NOW() di antara
     * start_time-end_time.
     */
    private function isLiveNow(Band $band): bool
    {
        $today = now()->toDateString();

        return PerformanceSlot::where('band_id', $band->id)
            ->whereHas('event', fn ($q) => $q->where('event_date', $today))
            ->get()
            ->contains(function (PerformanceSlot $slot) use ($today) {
                $start = "{$today} {$slot->start_time}";
                $end = "{$today} {$slot->end_time}";

                return now()->between($start, $end);
            });
    }
}
