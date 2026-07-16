<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'event_date',
        'venue_note',
        'poster_path',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    public function slots(): HasMany
    {
        return $this->hasMany(PerformanceSlot::class);
    }

    /**
     * BR-6: status dihitung, bukan disimpan. Dipakai di luar query view
     * (mis. setelah create/update) untuk response langsung tanpa re-query view.
     */
    public function computedStatus(): string
    {
        $today = now()->toDateString();

        if ($this->event_date->toDateString() < $today) {
            return 'past';
        }

        if ($this->event_date->toDateString() > $today) {
            return 'upcoming';
        }

        $minStart = $this->slots()->min('start_time');
        $maxEnd = $this->slots()->max('end_time');

        if (! $minStart || ! $maxEnd) {
            return 'upcoming';
        }

        $start = "{$today} {$minStart}";
        $end = "{$today} {$maxEnd}";

        return now()->between($start, $end) ? 'live' : 'past';
    }
}
