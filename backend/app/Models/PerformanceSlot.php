<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'band_id',
        'start_time',
        'end_time',
        'price',
        'stock_total',
        'stock_sold',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function band(): BelongsTo
    {
        return $this->belongsTo(Band::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'performance_slot_id');
    }

    public function stockAvailable(): int
    {
        return $this->stock_total - $this->stock_sold;
    }
}
