<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Band extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'genre',
        'description',
        'logo_path',
    ];

    public function slots(): HasMany
    {
        return $this->hasMany(PerformanceSlot::class);
    }

    /**
     * BR-2: band sedang tampil sekarang jika ada slot hari ini yang
     * rentang start_time-end_time-nya mencakup waktu sekarang.
     */
    public function isLiveNow(): bool
    {
        return $this->slots()
            ->whereHas('event', fn ($q) => $q->whereDate('event_date', now()->toDateString()))
            ->get()
            ->contains(function (PerformanceSlot $slot) {
                $start = $slot->event->event_date->format('Y-m-d') . ' ' . $slot->start_time;
                $end = $slot->event->event_date->format('Y-m-d') . ' ' . $slot->end_time;

                return now()->between($start, $end);
            });
    }
}
