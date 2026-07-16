<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model read-only untuk view v_slot_detail (§2.1 kontrak).
 * Join slot + band + event, dipakai untuk detail event/listing slot
 * agar terhindar dari N+1 query.
 */
class SlotDetailView extends Model
{
    protected $table = 'v_slot_detail';

    protected $primaryKey = 'slot_id';

    public $timestamps = false;

    protected $guarded = ['*'];

    public function save(array $options = [])
    {
        throw new \RuntimeException('v_slot_detail adalah view read-only.');
    }

    public function delete()
    {
        throw new \RuntimeException('v_slot_detail adalah view read-only.');
    }
}
