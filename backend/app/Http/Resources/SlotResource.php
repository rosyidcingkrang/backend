<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * §5.3 kontrak — item di `slots[]` pada GET /api/events/{id}, dan juga dipakai
 * sebagai bentuk response tunggal di §5.5 (POST/PUT /api/admin/.../slots) karena
 * kontrak bilang "format seperti item di slots[]".
 *
 * Format:
 * {
 *   id, band: { id, name, logo_path },
 *   start_time, end_time ("HH:mm"),
 *   price, stock_total, stock_available
 * }
 *
 * Butuh relasi `band` sudah di-eager-load di controller (findOrFail/with, atau
 * $slot->load('band') setelah create/update) supaya tidak N+1.
 */
class SlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'band' => [
                'id' => $this->band->id,
                'name' => $this->band->name,
                'logo_path' => $this->band->logo_path,
            ],
            'start_time' => substr((string) $this->start_time, 0, 5),
            'end_time' => substr((string) $this->end_time, 0, 5),
            'price' => (float) $this->price,
            'stock_total' => (int) $this->stock_total,
            'stock_available' => (int) ($this->stock_total - $this->stock_sold),
        ];
    }
}
