<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_code',
        'user_id',
        'performance_slot_id',
        'quantity',
        'unit_price',
        'total_price',
        'status',
        'purchased_at',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'purchased_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function performanceSlot(): BelongsTo
    {
        return $this->belongsTo(PerformanceSlot::class);
    }

    /**
     * Generate ticket_code format HKS-XXXXXXXX (§2.3 panduan: bebas cara generate,
     * asal format HKS- + string unik).
     */
    public static function generateTicketCode(): string
    {
        do {
            $code = 'HKS-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 8));
        } while (self::where('ticket_code', $code)->exists());

        return $code;
    }
}
