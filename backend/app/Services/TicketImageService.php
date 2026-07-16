<?php

namespace App\Services;

use App\Models\Ticket;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Generate 1 file JPEG berisi QR code + detail tiket tergabung dalam satu gambar
 * (§5.4 kontrak, endpoint GET /api/tickets/{id}/download).
 *
 * Library QR yang dipakai bebas (§2.3 panduan) — di sini pakai simple-qrcode.
 * composer require simplesoftwareio/simple-qrcode
 *
 * Composing gambar akhir pakai GD (ext-gd, built-in PHP) supaya tidak menambah
 * dependency lain di luar QR generator.
 */
class TicketImageService
{
    private const CANVAS_WIDTH = 700;
    private const CANVAS_HEIGHT = 900;
    private const QR_SIZE = 400;

    public function generate(Ticket $ticket): string
    {
        $ticket->loadMissing('performanceSlot.event', 'performanceSlot.band');
        $slot = $ticket->performanceSlot;
        $event = $slot->event;
        $band = $slot->band;

        $canvas = imagecreatetruecolor(self::CANVAS_WIDTH, self::CANVAS_HEIGHT);

        $white = imagecolorallocate($canvas, 255, 255, 255);
        $black = imagecolorallocate($canvas, 20, 20, 20);
        $gray = imagecolorallocate($canvas, 110, 110, 110);
        imagefill($canvas, 0, 0, $white);

        // QR code berisi ticket_code, untuk verifikasi di pintu masuk
        $qrPng = QrCode::format('png')->size(self::QR_SIZE)->margin(1)->generate($ticket->ticket_code);
        $qrImage = imagecreatefromstring($qrPng);
        $qrX = (int) ((self::CANVAS_WIDTH - self::QR_SIZE) / 2);
        imagecopy($canvas, $qrImage, $qrX, 40, 0, 0, self::QR_SIZE, self::QR_SIZE);
        imagedestroy($qrImage);

        $lines = [
            ['HAKOSTAR TICKET', 5, $black],
            [$ticket->ticket_code, 4, $black],
            ['', 3, $black],
            [$event->title, 5, $black],
            ['Band: ' . $band->name, 4, $gray],
            ['Tanggal: ' . $event->event_date->format('d M Y'), 4, $gray],
            ['Jam: ' . $slot->start_time . ' - ' . $slot->end_time, 4, $gray],
            ['Qty: ' . $ticket->quantity . ' tiket', 4, $gray],
            ['Total: Rp ' . number_format((float) $ticket->total_price, 0, ',', '.'), 4, $black],
        ];

        $y = self::QR_SIZE + 70;
        foreach ($lines as [$text, $fontSize, $color]) {
            if ($text === '') {
                $y += 15;
                continue;
            }

            $textWidth = imagefontwidth($fontSize) * strlen($text);
            $x = (int) ((self::CANVAS_WIDTH - $textWidth) / 2);
            imagestring($canvas, $fontSize, $x, $y, $text, $color);
            $y += imagefontheight($fontSize) + 14;
        }

        ob_start();
        imagejpeg($canvas, null, 90);
        $binary = ob_get_clean();
        imagedestroy($canvas);

        return $binary;
    }
}
