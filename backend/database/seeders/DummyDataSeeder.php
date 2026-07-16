<?php

namespace Database\Seeders;

use App\Models\Band;
use App\Models\Event;
use App\Models\PerformanceSlot;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DummyDataSeeder extends Seeder
{
    /**
     * Dummy data untuk testing manual (Postman/Insomnia) & frontend sebelum
     * data asli tersedia — sesuai §2.1 langkah 3 panduan pengerjaan.
     *
     * Skala: 8 band, 10 konser tersebar dalam rentang 30 bulan (sebagian
     * sudah selesai / past, sebagian akan datang / upcoming), 50 user biasa.
     */
    public function run(): void
    {
        $bands = $this->seedBands();
        $events = $this->seedEvents();
        $slots = $this->seedSlots($events, $bands);
        $users = $this->seedUsers();
        $this->seedTicketsForPastEvents($slots, $events, $users);
    }

    /**
     * 8 band dengan genre berbeda-beda. name dibatasi 15 karakter (§schema bands).
     */
    private function seedBands(): array
    {
        $data = [
            ['name' => 'Kessoku Band', 'genre' => 'J-Rock', 'description' => 'Band rock sekolah yang naik lewat panggung kecil, kini jadi headliner favorit.'],
            ['name' => 'Sicks', 'genre' => 'City Pop', 'description' => 'Trio dream-pop dengan nuansa synth malam kota.'],
            ['name' => 'Neonwave', 'genre' => 'Synth Rock', 'description' => 'Synth-driven rock, energik dan penuh warna panggung.'],
            ['name' => 'Ordinary Days', 'genre' => 'Indie Pop', 'description' => 'Lirik jujur tentang keseharian, dibalut instrumen indie yang hangat.'],
            ['name' => 'Crimson Echo', 'genre' => 'Alt Rock', 'description' => 'Alternative rock dengan distorsi gitar tebal dan chorus yang menggantung lama.'],
            ['name' => 'Paper Moon', 'genre' => 'Dream Pop', 'description' => 'Dream pop lembut, cocok untuk penutup malam.'],
            ['name' => 'Static Bloom', 'genre' => 'Shoegaze', 'description' => 'Wall of sound shoegaze, vokal tenggelam di antara noise gitar.'],
            ['name' => 'Hollow Kite', 'genre' => 'Post-Punk', 'description' => 'Post-punk gelap dengan bassline yang jadi tulang punggung tiap lagu.'],
        ];

        return collect($data)->map(
            fn (array $b) => Band::firstOrCreate(['name' => $b['name']], $b)
        )->all();
    }

    /**
     * 10 konser tersebar dalam rentang 30 bulan: -24 bulan s/d +6 bulan dari
     * sekarang. 5 event pertama sudah lewat (past/selesai), 5 sisanya akan
     * datang (upcoming). title dibatasi 50 karakter (§schema events).
     */
    private function seedEvents(): array
    {
        $venues = ['Hall A', 'Hall B', 'Grand Hall', 'Outdoor Stage', 'Basement Live', 'Rooftop Arena', 'Studio Live House', 'Main Arena', 'Riverside Stage', 'Warehouse 7'];

        // offset bulan dari sekarang: 5 sudah lewat, 5 akan datang, total rentang 30 bulan (-24 s/d +6)
        $monthOffsets = [-24, -19, -14, -9, -4, 1, 2, 3, 4, 6];

        $events = [];

        foreach ($monthOffsets as $i => $offset) {
            $volume = $i + 1;
            $date = now()->addMonths($offset)->addDays(($i % 5) * 3);

            $events[] = Event::firstOrCreate(
                ['title' => "HakoStar Live Night Vol.{$volume}"],
                [
                    'event_date' => $date->toDateString(),
                    'venue_note' => $venues[$i],
                ]
            );
        }

        return $events;
    }

    /**
     * Tiap event dapat 2 slot tampil dari 2 band berbeda (rotasi dari 8 band).
     * Event yang sudah lewat diberi stock_sold > 0 supaya kelihatan ada histori
     * penjualan; event akan datang stock_sold rendah/0.
     */
    private function seedSlots(array $events, array $bands): array
    {
        $times = [
            ['start' => '18:00', 'end' => '18:40'],
            ['start' => '19:00', 'end' => '19:45'],
        ];
        $prices = [50000, 60000, 75000, 90000, 100000];

        $slots = [];
        $bandCount = count($bands);

        foreach ($events as $i => $event) {
            $isPast = $event->event_date->isPast();

            foreach ($times as $j => $time) {
                $band = $bands[($i * 2 + $j) % $bandCount];
                $stockTotal = [80, 100, 120, 150][($i + $j) % 4];
                $stockSold = $isPast
                    ? (int) round($stockTotal * (rand(60, 100) / 100)) // 60%-100% terjual utk event lampau
                    : (int) round($stockTotal * (rand(0, 30) / 100));  // 0%-30% terjual utk event akan datang

                $slots[] = PerformanceSlot::firstOrCreate(
                    [
                        'event_id' => $event->id,
                        'band_id' => $band->id,
                        'start_time' => $time['start'],
                    ],
                    [
                        'end_time' => $time['end'],
                        'price' => $prices[($i + $j) % count($prices)],
                        'stock_total' => $stockTotal,
                        'stock_sold' => min($stockSold, $stockTotal),
                    ]
                );
            }
        }

        return $slots;
    }

    /**
     * 50 user biasa (role user) + user_profiles. username max 20 karakter,
     * email max 100, phone max 15 karakter (§schema users/user_profiles).
     * Password sama untuk semua: "password123" (memudahkan testing login).
     */
    private function seedUsers(): array
    {
        $users = [];

        for ($i = 1; $i <= 50; $i++) {
            $fullName = fake('id_ID')->name();
            $suffix = (string) $i;
            $slug = Str::limit(Str::slug($fullName, ''), 20 - strlen($suffix), '');
            $username = $slug . $suffix; // dipotong dulu baru ditempel angka, supaya index tetap unik & total <= 20 char
            $email = "user{$i}@hakostar.test";

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'username' => $username,
                    'password' => Hash::make('password123'),
                    'role' => 'user',
                ]
            );

            UserProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name' => $fullName,
                    'phone' => '08' . rand(100000000, 999999999), // 11 digit, aman < 15 char
                ]
            );

            $users[] = $user;
        }

        return $users;
    }

    /**
     * Sebagian kecil tiket dummy untuk event yang sudah lewat, supaya halaman
     * ticket-history & admin ada data nyata untuk ditest (bukan cuma angka
     * stock_sold). 3-6 tiket acak per slot event lampau.
     */
    private function seedTicketsForPastEvents(array $slots, array $events, array $users): void
    {
        $pastEventIds = collect($events)->filter(fn (Event $e) => $e->event_date->isPast())->pluck('id');

        $pastSlots = collect($slots)->filter(
            fn (PerformanceSlot $s) => $pastEventIds->contains($s->event_id)
        );

        foreach ($pastSlots as $slot) {
            $ticketCount = rand(3, 6);

            for ($k = 0; $k < $ticketCount; $k++) {
                $user = $users[array_rand($users)];
                $quantity = rand(1, 3);

                Ticket::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'performance_slot_id' => $slot->id,
                    ],
                    [
                        'ticket_code' => Ticket::generateTicketCode(),
                        'quantity' => $quantity,
                        'unit_price' => $slot->price,
                        'total_price' => $slot->price * $quantity,
                        'status' => 'paid',
                        'purchased_at' => $slot->event->event_date->copy()->subDays(rand(2, 20)),
                    ]
                );
            }
        }
    }
}
