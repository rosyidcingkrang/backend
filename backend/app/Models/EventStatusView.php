<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model read-only untuk view v_event_status (§2.1 kontrak).
 * Dipakai untuk listing & filter event tanpa hitung status di PHP (BR-6).
 * JANGAN dipakai untuk insert/update/delete — view tidak writable secara wajar
 * karena computed_status adalah hasil CASE, bukan kolom asli.
 */
class EventStatusView extends Model
{
    protected $table = 'v_event_status';

    public $timestamps = false;

    protected $guarded = ['*'];

    public function save(array $options = [])
    {
        throw new \RuntimeException('v_event_status adalah view read-only.');
    }

    public function delete()
    {
        throw new \RuntimeException('v_event_status adalah view read-only.');
    }
}
