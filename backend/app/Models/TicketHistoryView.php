<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model read-only untuk view v_ticket_history (§2.1 kontrak).
 * Riwayat pembelian user, join penuh — dipakai endpoint GET /api/tickets/history
 * agar terhindar dari N+1 query.
 */
class TicketHistoryView extends Model
{
    protected $table = 'v_ticket_history';

    protected $primaryKey = 'ticket_id';

    public $timestamps = false;

    protected $guarded = ['*'];

    public function save(array $options = [])
    {
        throw new \RuntimeException('v_ticket_history adalah view read-only.');
    }

    public function delete()
    {
        throw new \RuntimeException('v_ticket_history adalah view read-only.');
    }
}
