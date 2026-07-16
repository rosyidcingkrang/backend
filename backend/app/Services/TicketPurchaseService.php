<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\TransactionLimitExceededException;
use App\Models\PerformanceSlot;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketPurchaseService
{
    private const MAX_TRANSACTION_TOTAL = 800000;

    /**
     * Beli tiket — 1 request = 1 transaksi untuk 1 slot, langsung 'paid' (§5.4 kontrak).
     *
     * Urutan validasi wajib (§5.4, catatan implementasi):
     * 1. slot exist & event belum lewat
     * 2. hitung total, tolak jika > 800K (sebelum lock row)
     * 3. baru masuk DB::transaction untuk lock stok & insert
     */
    public function purchase(User $user, int $slotId, int $quantity): Ticket
    {
        $slot = PerformanceSlot::with(['event', 'band'])->find($slotId);

        if (! $slot) {
            throw ValidationException::withMessages([
                'performance_slot_id' => ['Slot pertunjukan tidak ditemukan.'],
            ]);
        }

        if ($slot->event->event_date->toDateString() < now()->toDateString()) {
            throw ValidationException::withMessages([
                'performance_slot_id' => ['Event untuk slot ini sudah lewat.'],
            ]);
        }

        $total = bcmul((string) $slot->price, (string) $quantity, 2);

        // BR-1: total harga per transaksi tidak boleh melebihi Rp 800.000
        if ((float) $total > self::MAX_TRANSACTION_TOTAL) {
            throw new TransactionLimitExceededException((float) $total);
        }

        return DB::transaction(function () use ($slot, $quantity, $user, $total) {
            /** @var PerformanceSlot $lockedSlot */
            $lockedSlot = PerformanceSlot::where('id', $slot->id)->lockForUpdate()->first();

            $available = $lockedSlot->stock_total - $lockedSlot->stock_sold;

            if ($available < $quantity) {
                throw new InsufficientStockException($available);
            }

            $lockedSlot->increment('stock_sold', $quantity);

            return Ticket::create([
                'ticket_code' => Ticket::generateTicketCode(),
                'user_id' => $user->id,
                'performance_slot_id' => $lockedSlot->id,
                'quantity' => $quantity,
                'unit_price' => $slot->price,
                'total_price' => $total,
                'status' => 'paid',
                'purchased_at' => now(),
            ]);
        });
    }
}
