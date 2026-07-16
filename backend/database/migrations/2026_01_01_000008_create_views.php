<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // v_event_status — status event dihitung di DB, hindari hitung status di PHP (BR-6)
        DB::statement("
            CREATE OR REPLACE VIEW v_event_status AS
            SELECT
              e.id, e.title, e.event_date, e.venue_note, e.poster_path,
              CASE
                WHEN e.event_date < CURRENT_DATE THEN 'past'
                WHEN e.event_date > CURRENT_DATE THEN 'upcoming'
                WHEN NOW() BETWEEN
                  (e.event_date + (SELECT MIN(start_time) FROM performance_slots WHERE event_id = e.id))
                  AND
                  (e.event_date + (SELECT MAX(end_time) FROM performance_slots WHERE event_id = e.id))
                THEN 'live'
                WHEN CURRENT_DATE = e.event_date AND NOW() < (e.event_date + (SELECT MIN(start_time) FROM performance_slots WHERE event_id = e.id))
                THEN 'upcoming'
                ELSE 'past'
              END AS computed_status
            FROM events e
        ");

        // v_slot_detail — join slot + band + event, hindari N+1 query saat listing tiket
        DB::statement("
            CREATE OR REPLACE VIEW v_slot_detail AS
            SELECT
              ps.id AS slot_id, ps.event_id, e.title AS event_title, e.event_date,
              ps.band_id, b.name AS band_name, b.logo_path AS band_logo,
              ps.start_time, ps.end_time, ps.price,
              ps.stock_total, ps.stock_sold, (ps.stock_total - ps.stock_sold) AS stock_available
            FROM performance_slots ps
            JOIN events e ON e.id = ps.event_id
            JOIN bands b ON b.id = ps.band_id
        ");

        // v_ticket_history — riwayat pembelian user, join penuh, hindari N+1
        DB::statement("
            CREATE OR REPLACE VIEW v_ticket_history AS
            SELECT
              t.id AS ticket_id, t.ticket_code, t.user_id, t.quantity, t.unit_price, t.total_price, t.purchased_at,
              ps.id AS slot_id, ps.start_time, ps.end_time,
              e.id AS event_id, e.title AS event_title, e.event_date,
              b.id AS band_id, b.name AS band_name
            FROM tickets t
            JOIN performance_slots ps ON ps.id = t.performance_slot_id
            JOIN events e ON e.id = ps.event_id
            JOIN bands b ON b.id = ps.band_id
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_ticket_history');
        DB::statement('DROP VIEW IF EXISTS v_slot_detail');
        DB::statement('DROP VIEW IF EXISTS v_event_status');
    }
};