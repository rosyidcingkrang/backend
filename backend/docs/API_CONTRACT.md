# HakoStar — API Contract (Backend ⇄ Frontend)

Versi: 1.0
Base URL (dev): `http://localhost:8000/api`
Auth: Laravel Sanctum (Bearer Token, bukan cookie session)
Timezone: seluruh waktu di server & database disimpan dan dihitung dalam **UTC+7 (Asia/Jakarta)**. Tidak ada konversi timezone di frontend — backend selalu mengembalikan waktu yang sudah final untuk ditampilkan apa adanya.

> Dokumen ini adalah **kontrak final**. Backend dev mengimplementasikan persis field & response ini. Frontend dev membangun UI berdasarkan ini tanpa perlu bertanya ke backend dev. Jika ada perubahan, dokumen ini yang diedit lebih dulu, baru kode menyesuaikan.

---

## 1. Prinsip Umum

### 1.1 Format Response Standar

Sukses:
```json
{
  "success": true,
  "data": { }
}
```

Sukses list dengan pagination:
```json
{
  "success": true,
  "data": [ ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 42,
    "last_page": 5
  }
}
```

Gagal:
```json
{
  "success": false,
  "message": "Pesan error yang bisa ditampilkan ke user",
  "errors": {
    "field_name": ["Pesan validasi spesifik"]
  }
}
```
`errors` hanya muncul untuk error validasi (HTTP 422). Untuk error lain (401/403/404/409/500), cukup `success` + `message`.

### 1.2 HTTP Status Code yang Dipakai

| Kode | Arti | Kapan dipakai |
|---|---|---|
| 200 | OK | Read/update berhasil |
| 201 | Created | Create berhasil |
| 401 | Unauthorized | Token tidak ada / invalid / expired |
| 403 | Forbidden | Token valid tapi bukan role yang diizinkan (mis. user akses endpoint admin) |
| 404 | Not Found | Resource tidak ditemukan |
| 409 | Conflict | Stok habis, band masih tampil hari ini saat dihapus, dsb (business logic conflict) |
| 422 | Unprocessable Entity | Validasi gagal |
| 500 | Server Error | Error tak terduga |

### 1.3 Autentikasi

- Semua endpoint privat butuh header: `Authorization: Bearer {token}`
- Token dihasilkan Sanctum saat login, disimpan frontend di `localStorage`.
- **Token expire setelah 2 jam** dari waktu login (bukan sliding/rolling expiration — 2 jam fix sejak issued). Backend mengecek `created_at` token terhadap waktu sekarang di middleware; jika lewat, hapus token tsb dan balas `401` dengan `message: "SESSION_EXPIRED"`.
- Frontend: `requireAuth()` di `js/api.js` mengecek keberadaan token di localStorage saja (cek ringan di client). Validasi sesungguhnya tetap di backend setiap request — kalau backend balas 401, frontend **wajib** hapus token dari localStorage dan redirect ke `login.html`.
- Tidak ada refresh token. Setelah expired, user login ulang.

### 1.4 Role

Ada dua role: `user` dan `admin`. Hanya ada **1 akun admin**, dibuat manual lewat seeder Laravel (tidak ada endpoint register admin). Role disimpan di tabel `users`, dikembalikan di response login/me, dan dicek lewat middleware terpisah (`role:admin`) untuk semua route `/api/admin/*`.

---

## 2. Skema Database (ringkas, acuan untuk backend)

Normalisasi minimal 3NF. Panjang string ditekan sesuai kebutuhan riil field.

```
users
├─ id                  BIGINT UNSIGNED PK AUTO_INCREMENT
├─ username             VARCHAR(20)  UNIQUE NOT NULL
├─ email                VARCHAR(100) UNIQUE NOT NULL
├─ password              VARCHAR(255) NOT NULL   -- hashed (bcrypt)
├─ role                 ENUM('user','admin') NOT NULL DEFAULT 'user'
├─ created_at, updated_at TIMESTAMP

user_profiles
├─ id                   BIGINT UNSIGNED PK AUTO_INCREMENT
├─ user_id              BIGINT UNSIGNED FK -> users.id UNIQUE NOT NULL  -- 1-1
├─ full_name            VARCHAR(50) NULL
├─ phone                VARCHAR(15) NULL
├─ avatar_path          VARCHAR(255) NULL
├─ created_at, updated_at TIMESTAMP

bands
├─ id                   BIGINT UNSIGNED PK AUTO_INCREMENT
├─ name                 VARCHAR(15) NOT NULL         -- ditekan sesuai instruksi (10-15 char)
├─ genre                VARCHAR(30) NULL
├─ description          VARCHAR(500) NULL
├─ logo_path            VARCHAR(255) NULL
├─ created_at, updated_at TIMESTAMP

events                                     -- 1 baris = 1 hari livehouse buka
├─ id                   BIGINT UNSIGNED PK AUTO_INCREMENT
├─ title                VARCHAR(50) NOT NULL
├─ event_date           DATE NOT NULL
├─ venue_note           VARCHAR(100) NULL      -- mis. "Hall B, Lt 2" (livehouse tunggal, jadi opsional)
├─ poster_path          VARCHAR(255) NULL
├─ created_at, updated_at TIMESTAMP
INDEX (event_date)

performance_slots                          -- band + jam tampil dalam 1 event, tiket dijual per slot ini
├─ id                   BIGINT UNSIGNED PK AUTO_INCREMENT
├─ event_id             BIGINT UNSIGNED FK -> events.id NOT NULL
├─ band_id              BIGINT UNSIGNED FK -> bands.id NOT NULL
├─ start_time            TIME NOT NULL
├─ end_time              TIME NOT NULL
├─ price                DECIMAL(10,2) NOT NULL     -- harga per tiket, khusus slot ini
├─ stock_total           SMALLINT UNSIGNED NOT NULL
├─ stock_sold            SMALLINT UNSIGNED NOT NULL DEFAULT 0
├─ created_at, updated_at TIMESTAMP
UNIQUE (event_id, band_id, start_time)          -- band tidak dobel di jam sama pada event sama
INDEX (event_id)

tickets                                    -- 1 baris = 1 transaksi pembelian (bisa multi qty)
├─ id                   BIGINT UNSIGNED PK AUTO_INCREMENT
├─ ticket_code           VARCHAR(20) UNIQUE NOT NULL     -- kode unik utk QR, mis. HKS-8F2K9X1A
├─ user_id              BIGINT UNSIGNED FK -> users.id NOT NULL
├─ performance_slot_id    BIGINT UNSIGNED FK -> performance_slots.id NOT NULL
├─ quantity             TINYINT UNSIGNED NOT NULL
├─ unit_price            DECIMAL(10,2) NOT NULL      -- snapshot harga saat beli (histori harga, walau slot dihapus/edit)
├─ total_price           DECIMAL(10,2) NOT NULL      -- quantity * unit_price, max 800000
├─ status               ENUM('paid') NOT NULL DEFAULT 'paid'   -- simplifikasi: begitu dibuat = paid
├─ purchased_at          TIMESTAMP NOT NULL
├─ created_at, updated_at TIMESTAMP
INDEX (user_id)
INDEX (performance_slot_id)
```

> Catatan desain: `status` di `tickets` sengaja tetap dibuat sebagai `ENUM('paid')` (bukan boolean) supaya kalau nanti sistem pembayaran dummy ini diperluas (`pending`/`cancelled`/`expired`), tidak perlu migrasi ubah tipe kolom — cukup tambah value enum. Untuk sekarang, satu-satunya cara ticket row ada = sudah paid (create ticket = create pembayaran, atomic).

### 2.1 View yang Dibuat di Database

**`v_event_status`** — dipakai untuk listing & filter event tanpa hitung status di PHP:
```sql
CREATE VIEW v_event_status AS
SELECT
  e.id, e.title, e.event_date, e.venue_note, e.poster_path,
  CASE
    WHEN e.event_date < CURDATE() THEN 'past'
    WHEN e.event_date > CURDATE() THEN 'upcoming'
    WHEN NOW() BETWEEN
      TIMESTAMP(e.event_date, (SELECT MIN(start_time) FROM performance_slots WHERE event_id = e.id))
      AND
      TIMESTAMP(e.event_date, (SELECT MAX(end_time) FROM performance_slots WHERE event_id = e.id))
    THEN 'live'
    WHEN CURDATE() = e.event_date AND NOW() < TIMESTAMP(e.event_date, (SELECT MIN(start_time) FROM performance_slots WHERE event_id = e.id))
    THEN 'upcoming'
    ELSE 'past'
  END AS computed_status
FROM events e;
```

**`v_slot_detail`** — join slot + band + event untuk listing tiket (hindari N+1 query):
```sql
CREATE VIEW v_slot_detail AS
SELECT
  ps.id AS slot_id, ps.event_id, e.title AS event_title, e.event_date,
  ps.band_id, b.name AS band_name, b.logo_path AS band_logo,
  ps.start_time, ps.end_time, ps.price,
  ps.stock_total, ps.stock_sold, (ps.stock_total - ps.stock_sold) AS stock_available
FROM performance_slots ps
JOIN events e ON e.id = ps.event_id
JOIN bands b ON b.id = ps.band_id;
```

**`v_ticket_history`** — riwayat pembelian user, join penuh (dipakai endpoint riwayat, hindari N+1):
```sql
CREATE VIEW v_ticket_history AS
SELECT
  t.id AS ticket_id, t.ticket_code, t.user_id, t.quantity, t.unit_price, t.total_price, t.purchased_at,
  ps.id AS slot_id, ps.start_time, ps.end_time,
  e.id AS event_id, e.title AS event_title, e.event_date,
  b.id AS band_id, b.name AS band_name
FROM tickets t
JOIN performance_slots ps ON ps.id = t.performance_slot_id
JOIN events e ON e.id = ps.event_id
JOIN bands b ON b.id = ps.band_id;
```

### 2.2 Query Pattern Wajib (dicatat untuk backend dev)

- Semua query yang menerima input dari request **wajib prepared statement** — pakai Eloquent/Query Builder standar (otomatis prepared), **dilarang** `DB::raw()` dengan concat string dari input user.
- Pengurangan stok saat beli tiket wajib pakai transaction + row lock:
  ```php
  DB::transaction(function () use (...) {
      $slot = PerformanceSlot::where('id', $slotId)->lockForUpdate()->first();
      if ($slot->stock_total - $slot->stock_sold < $qty) {
          throw new InsufficientStockException();
      }
      $slot->increment('stock_sold', $qty);
      Ticket::create([...]);
  });
  ```
  Ini murni proteksi race condition (dua user checkout bersamaan di detik yang sama) — bukan mekanisme lock stok sementara/countdown, karena pembayaran sudah disimpelkan jadi 1 langkah.
- Listing konser & riwayat tiket **wajib** query lewat view di atas (`v_event_status`, `v_slot_detail`, `v_ticket_history`), bukan query manual join berulang di tiap controller.

---

## 3. Business Rules (acuan implementasi backend, wajib dicek server-side — jangan percaya validasi frontend)

| # | Aturan |
|---|---|
| BR-1 | Total harga per transaksi (`quantity * price`) **tidak boleh melebihi Rp 800.000**. Divalidasi backend saat create ticket, bukan cuma di frontend. |
| BR-2 | Band **hanya bisa dihapus jika tidak sedang tampil di jam berjalan saat ini** (dicek terhadap `performance_slots` yang match `NOW()`). Definisi disederhanakan: cek apakah ada baris `performance_slots` untuk band ini dengan `event_date = CURDATE()` dan `NOW()` di antara `start_time`–`end_time`. Jika ya → tolak (409). Jika tidak (termasuk band yang akan tampil besok/nanti) → boleh dihapus. |
| BR-3 | Menghapus band **ikut menghapus semua tiket untuk konser yang akan datang** yang melibatkan band tsb (cascade ke `performance_slots` masa depan & `tickets` terkait). Slot & tiket band tsb yang sudah lewat (`event_date < CURDATE()`) **tetap disimpan** sebagai histori — jadi bukan hard delete band, tapi *soft-delete-like cleanup* hanya untuk data masa depan. Implementasi: `band_id` di `bands` dihapus permanen (hard delete), tapi sebelum itu jalankan cascade delete `performance_slots` (dan `tickets` turunannya) yang `event_date >= CURDATE()`. Slot lampau tetap ada meski `band_id` sudah tidak valid → maka `bands.id` **tidak boleh** hard delete penuh; sebagai gantinya pakai **soft delete** (`deleted_at`) di tabel `bands` supaya histori slot lampau tetap bisa join nama band. Band yang sudah soft-deleted tidak muncul di listing publik/admin manapun kecuali di histori tiket lampau. |
| BR-4 | Menghapus **event/konser tidak memengaruhi data band** sama sekali (band tetap ada, hanya kehilangan slot & tiket terkait event tsb yang ikut terhapus). |
| BR-5 | Semua operasi delete (event, band, user, slot) **wajib** dikonfirmasi di frontend dengan modal peringatan berisi detail spesifik item yang akan dihapus (nama + tanggal untuk event, nama band, dst) sebelum request DELETE dikirim. |
| BR-6 | Status event (`live` / `upcoming` / `past`) dihitung otomatis dari `event_date` + rentang `start_time`–`end_time` seluruh slot pada event tsb, dibandingkan waktu server (UTC+7) saat itu. Tidak ada field status manual. |
| BR-7 | Tiket tidak punya nomor kursi — murni pengurangan stok (`stock_sold` increment). |
| BR-8 | 1 transaksi tiket hanya untuk 1 `performance_slot` (tidak bisa campur beberapa konser/slot dalam 1 transaksi) — mencegah calo & menyederhanakan validasi 800K. |
| BR-9 | Slot performa **tidak bisa** memiliki band yang sama pada jam yang sama di event yang sama (constraint `UNIQUE(event_id, band_id, start_time)`). |
| BR-10 | User yang dihapus admin: tiket historisnya tetap disimpan (FK `tickets.user_id` tidak cascade delete, tapi `ON DELETE SET NULL` tidak dipakai juga karena `user_id NOT NULL` — maka user **wajib soft delete**, sama seperti band, bukan hard delete). |

---

## 4. Struktur Folder Frontend (acuan, sesuai yang sudah pernah dibuat sebelumnya)

```
frontend/
├─ index.html              (landing page)
├─ login.html
├─ register.html
├─ concerts.html            (list + filter + pagination, publik)
├─ concert-detail.html       (detail slot per band + tombol beli)
├─ profile.html             (edit profil user)
├─ ticket-history.html
├─ admin/
│  ├─ dashboard.html
│  ├─ events.html
│  ├─ bands.html
│  └─ users.html
├─ css/
│  └─ (folder terpisah sesuai konvensi project sebelumnya)
├─ js/
│  ├─ api.js               (apiRequest(), requireAuth())
│  ├─ auth.js
│  ├─ concerts.js
│  ├─ ...
└─ assets/
```

---

## 5. Endpoint List

### 5.1 Auth — Publik

#### `POST /api/register`
Request:
```json
{
  "username": "yumeko123",
  "email": "yumeko@example.com",
  "password": "rahasia123",
  "password_confirmation": "rahasia123"
}
```
Validasi: `username` unik, 3-20 karakter; `email` unik, format email valid, wajib; `password` min 8 karakter.

Response 201:
```json
{
  "success": true,
  "data": {
    "id": 12,
    "username": "yumeko123",
    "email": "yumeko@example.com",
    "role": "user"
  }
}
```
> Register tidak langsung login — frontend arahkan ke `login.html` setelah sukses.

#### `POST /api/login`
Request:
```json
{ "login": "yumeko123", "password": "rahasia123" }
```
`login` menerima username **atau** email (backend cek keduanya).

Response 200:
```json
{
  "success": true,
  "data": {
    "token": "1|abcXYZ...",
    "user": {
      "id": 12,
      "username": "yumeko123",
      "email": "yumeko@example.com",
      "role": "user"
    },
    "expires_at": "2026-07-13T14:30:00+07:00"
  }
}
```
Response 401 (kredensial salah):
```json
{ "success": false, "message": "Username/email atau password salah" }
```

#### `POST /api/logout` 🔒
Menghapus token yang sedang dipakai. Response 200: `{ "success": true, "data": null }`

---

### 5.2 User — Profil 🔒 (role: user & admin)

#### `GET /api/me`
Response 200:
```json
{
  "success": true,
  "data": {
    "id": 12,
    "username": "yumeko123",
    "email": "yumeko@example.com",
    "role": "user",
    "profile": {
      "full_name": "Yumeko Saito",
      "phone": "081234567890",
      "avatar_path": "/storage/avatars/12.jpg"
    }
  }
}
```

#### `PUT /api/me/profile`
Request (semua field opsional, kirim yang mau diubah saja):
```json
{ "full_name": "Yumeko Saito", "phone": "081234567890" }
```
Response 200: object profile terbaru (format sama seperti `profile` di atas).

> Email & username **tidak** bisa diubah lewat endpoint ini (perubahan kredensial dianggap out of scope — sesuai keputusan "cukup username dan email" sebagai kredensial dasar, tanpa fitur ganti kredensial di v1).

#### `POST /api/me/avatar`
Multipart form-data, field `avatar` (jpeg/png, max 2MB).
Response 200: `{ "success": true, "data": { "avatar_path": "/storage/avatars/12.jpg" } }`

---

### 5.3 Konser — Publik (bisa diakses tanpa login, tapi kalau ada token tetap boleh dipakai)

#### `GET /api/events`
Query params:
- `page` (default 1), `per_page` (default 10)
- `search` — cari di `title` (LIKE)
- `status` — `live` | `upcoming` | `past` (dari `v_event_status.computed_status`)

Response 200:
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "title": "HakoStar Live Night Vol.3",
      "event_date": "2026-07-20",
      "venue_note": "Hall B",
      "poster_path": "/storage/posters/5.jpg",
      "status": "upcoming",
      "slot_count": 4
    }
  ],
  "meta": { "current_page": 1, "per_page": 10, "total": 12, "last_page": 2 }
}
```

#### `GET /api/events/{id}`
Response 200:
```json
{
  "success": true,
  "data": {
    "id": 5,
    "title": "HakoStar Live Night Vol.3",
    "event_date": "2026-07-20",
    "venue_note": "Hall B",
    "poster_path": "/storage/posters/5.jpg",
    "status": "upcoming",
    "slots": [
      {
        "id": 21,
        "band": { "id": 3, "name": "Kessoku Band", "logo_path": "/storage/logos/3.jpg" },
        "start_time": "18:00",
        "end_time": "18:40",
        "price": 75000,
        "stock_total": 100,
        "stock_available": 34
      }
    ]
  }
}
```
Response 404 jika event tidak ada.

---

### 5.4 Tiket 🔒 (role: user)

#### `POST /api/tickets`
Beli tiket — 1 request = 1 transaksi untuk 1 slot, langsung `paid`.

Request:
```json
{ "performance_slot_id": 21, "quantity": 2 }
```

Response 201:
```json
{
  "success": true,
  "data": {
    "ticket_id": 88,
    "ticket_code": "HKS-8F2K9X1A",
    "quantity": 2,
    "unit_price": 75000,
    "total_price": 150000,
    "purchased_at": "2026-07-13T10:15:00+07:00",
    "event_title": "HakoStar Live Night Vol.3",
    "band_name": "Kessoku Band"
  }
}
```

Response 409 (stok tidak cukup):
```json
{ "success": false, "message": "Stok tiket tidak mencukupi. Sisa 1 tiket." }
```

Response 422 (melebihi batas 800K):
```json
{
  "success": false,
  "message": "Total transaksi melebihi batas maksimum Rp 800.000",
  "errors": { "quantity": ["Total harga (Rp 900.000) melebihi batas Rp 800.000 per transaksi"] }
}
```

> Validasi urutan di backend: (1) slot exist & event belum lewat, (2) hitung `total = price * quantity`, tolak jika `> 800000`, (3) baru masuk `DB::transaction` untuk lock stok & insert. Urutan ini supaya error 800K tidak perlu lock row dulu.

#### `GET /api/tickets/history`
Query params: `page`, `per_page`.

Response 200:
```json
{
  "success": true,
  "data": [
    {
      "ticket_id": 88,
      "ticket_code": "HKS-8F2K9X1A",
      "event_title": "HakoStar Live Night Vol.3",
      "event_date": "2026-07-20",
      "band_name": "Kessoku Band",
      "start_time": "18:00",
      "quantity": 2,
      "total_price": 150000,
      "purchased_at": "2026-07-13T10:15:00+07:00"
    }
  ],
  "meta": { "current_page": 1, "per_page": 10, "total": 3, "last_page": 1 }
}
```

#### `GET /api/tickets/{id}/download`
Menghasilkan **1 file JPEG** berisi QR code + detail tiket (event, band, tanggal, jam, qty, kode tiket) tergabung dalam satu gambar. Response: binary JPEG (`Content-Type: image/jpeg`, `Content-Disposition: attachment; filename="HKS-8F2K9X1A.jpg"`).

Response 403 jika `ticket.user_id !== auth user id` (tidak boleh download tiket orang lain).
Response 404 jika ticket tidak ditemukan / milik user lain (boleh dipilih 403 atau 404 asal konsisten — rekomendasi: 404 supaya tidak bocorkan keberadaan ID tiket orang lain).

---

### 5.5 Admin — Manajemen Event 🔒 (role: admin)

#### `GET /api/admin/events`
Sama seperti `GET /api/events` tapi tanpa filter status dibatasi (admin lihat semua termasuk `past`), plus field tambahan `created_at`.

#### `POST /api/admin/events`
Request:
```json
{
  "title": "HakoStar Live Night Vol.4",
  "event_date": "2026-08-01",
  "venue_note": "Hall A",
  "slots": [
    { "band_id": 3, "start_time": "18:00", "end_time": "18:40", "price": 75000, "stock_total": 100 },
    { "band_id": 7, "start_time": "19:00", "end_time": "19:45", "price": 90000, "stock_total": 80 }
  ]
}
```
Poster diunggah terpisah lewat endpoint upload (lihat 5.5b) setelah event dibuat, atau bisa dikirim sebagai multipart di request ini — **keputusan teknis**: gunakan multipart form-data dengan field `poster` (opsional) di request yang sama untuk menyederhanakan alur create.

Response 201: object event lengkap dengan `slots` (format sama seperti `GET /api/events/{id}`).

Validasi: `event_date` tidak boleh di masa lalu; tiap slot: `band_id` harus band yang tidak soft-deleted; `end_time > start_time`; kombinasi `(band_id, start_time)` unik dalam array slots yang dikirim.

#### `PUT /api/admin/events/{id}`
Update `title`, `event_date`, `venue_note`. **Tidak mengubah slots** — slot dikelola lewat endpoint terpisah di bawah (supaya partial update lebih aman, tidak menghapus-buat-ulang seluruh slot tiap edit).

Request:
```json
{ "title": "HakoStar Live Night Vol.4 (Rescheduled)", "event_date": "2026-08-02" }
```
Response 200: object event terbaru.

#### `DELETE /api/admin/events/{id}`
Hard delete event + cascade slots + cascade tickets terkait (event dihapus tidak memengaruhi tabel `bands` — BR-4).

Response 200:
```json
{
  "success": true,
  "data": {
    "deleted_event": "HakoStar Live Night Vol.4",
    "deleted_slots_count": 2,
    "deleted_tickets_count": 15
  }
}
```
> Frontend wajib tampilkan modal konfirmasi berisi `title` + `event_date` sebelum memanggil endpoint ini (BR-5). Response di atas dipakai untuk toast "berhasil" yang informatif.

#### `POST /api/admin/events/{id}/slots`
Tambah slot baru ke event yang sudah ada.
Request: `{ "band_id": 9, "start_time": "20:00", "end_time": "20:40", "price": 60000, "stock_total": 50 }`
Response 201: object slot baru (format seperti item di `slots[]`).

#### `PUT /api/admin/slots/{id}`
Request (field opsional): `{ "price": 65000, "stock_total": 60 }`
> `stock_total` tidak boleh diubah lebih kecil dari `stock_sold` saat ini (validasi 422 jika dilanggar). `band_id`, `start_time`, `end_time` juga bisa diubah di sini jika perlu reschedule slot.

Response 200: object slot terbaru.

#### `DELETE /api/admin/slots/{id}`
Response 200: `{ "success": true, "data": { "deleted_slot_id": 21, "deleted_tickets_count": 4 } }`
Menghapus slot ikut menghapus tiket yang sudah terjual untuk slot itu (konsekuensi dijelaskan di modal konfirmasi frontend).

---

### 5.5b Admin — Upload Aset (Drag & Drop)

#### `POST /api/admin/bands/{id}/logo`
Multipart form-data, field `logo` (jpeg/png, max 2MB). Dipakai oleh komponen drag-and-drop di frontend (`<input type="file">` yang menerima drop event — logic drag-drop murni di frontend, endpoint ini hanya terima file biasa).

Response 200: `{ "success": true, "data": { "logo_path": "/storage/logos/3.jpg" } }`

#### `POST /api/admin/events/{id}/poster`
Sama seperti di atas, field `poster`, untuk event yang sudah dibuat (alternatif dari multipart saat create).

Response 200: `{ "success": true, "data": { "poster_path": "/storage/posters/5.jpg" } }`

---

### 5.6 Admin — Manajemen Band 🔒 (role: admin)

#### `GET /api/admin/bands`
Query: `page`, `per_page`, `search` (LIKE di `name`).
Response 200: list band + `meta` pagination. Item: `{ id, name, genre, description, logo_path }`.

#### `POST /api/admin/bands`
Request (multipart, `logo` opsional): `{ "name": "Kessoku Band", "genre": "J-Rock", "description": "..." }`
Validasi: `name` **max 15 karakter**, wajib, unik.
Response 201: object band.

#### `PUT /api/admin/bands/{id}`
Request (field opsional, multipart jika ganti logo).
Response 200: object band terbaru.

#### `DELETE /api/admin/bands/{id}`
Cek BR-2 (band tidak sedang tampil di jam berjalan) di server sebelum proses.

Response 200 (berhasil):
```json
{
  "success": true,
  "data": {
    "deleted_band": "Kessoku Band",
    "deleted_future_slots_count": 3,
    "deleted_future_tickets_count": 22
  }
}
```
Response 409 (band sedang live sekarang):
```json
{ "success": false, "message": "Kessoku Band sedang tampil saat ini dan tidak bisa dihapus." }
```
> Implementasi: soft delete (`deleted_at`) sesuai BR-3, bukan hard delete, supaya histori slot lampau tetap valid.

---

### 5.7 Admin — Manajemen User 🔒 (role: admin)

#### `GET /api/admin/users`
Query: `page`, `per_page`, `search` (LIKE di `username` atau `email`).
Response 200:
```json
{
  "success": true,
  "data": [
    { "id": 12, "username": "yumeko123", "email": "yumeko@example.com", "full_name": "Yumeko Saito", "created_at": "2026-05-01T09:00:00+07:00" }
  ],
  "meta": { "current_page": 1, "per_page": 10, "total": 40, "last_page": 4 }
}
```

#### `PUT /api/admin/users/{id}`
Request: `{ "username": "yumeko_new", "email": "new@example.com" }`
Response 200: object user terbaru. (Admin bisa perbaiki data user, mis. typo email.)

#### `DELETE /api/admin/users/{id}`
Soft delete user (BR-10). Tiket historis tetap tersimpan & tetap muncul di riwayat/laporan admin, tapi user tidak bisa login lagi.

Response 200: `{ "success": true, "data": { "deleted_user": "yumeko123" } }`
> Frontend wajib modal konfirmasi berisi username sebelum kirim request (BR-5). Admin tidak bisa menghapus dirinya sendiri sendiri — jika dicoba, backend balas 403 `{ "message": "Admin tidak dapat menghapus akunnya sendiri" }`.

---

## 6. Ringkasan Endpoint (tabel cepat)

| Method | Endpoint | Role | Keterangan |
|---|---|---|---|
| POST | `/api/register` | Publik | Daftar akun baru |
| POST | `/api/login` | Publik | Login, dapat token |
| POST | `/api/logout` | 🔒 user/admin | Hapus token aktif |
| GET | `/api/me` | 🔒 user/admin | Data akun + profil sendiri |
| PUT | `/api/me/profile` | 🔒 user/admin | Update profil |
| POST | `/api/me/avatar` | 🔒 user/admin | Upload avatar |
| GET | `/api/events` | Publik | List event + filter + pagination |
| GET | `/api/events/{id}` | Publik | Detail event + slot |
| POST | `/api/tickets` | 🔒 user | Beli tiket (langsung paid) |
| GET | `/api/tickets/history` | 🔒 user | Riwayat pembelian |
| GET | `/api/tickets/{id}/download` | 🔒 user | Download JPEG (QR+detail) |
| GET | `/api/admin/events` | 🔒 admin | List semua event |
| POST | `/api/admin/events` | 🔒 admin | Buat event + slot |
| PUT | `/api/admin/events/{id}` | 🔒 admin | Edit info event |
| DELETE | `/api/admin/events/{id}` | 🔒 admin | Hapus event (cascade slot+tiket) |
| POST | `/api/admin/events/{id}/slots` | 🔒 admin | Tambah slot ke event |
| PUT | `/api/admin/slots/{id}` | 🔒 admin | Edit slot |
| DELETE | `/api/admin/slots/{id}` | 🔒 admin | Hapus slot |
| POST | `/api/admin/events/{id}/poster` | 🔒 admin | Upload poster event |
| GET | `/api/admin/bands` | 🔒 admin | List band |
| POST | `/api/admin/bands` | 🔒 admin | Buat band |
| PUT | `/api/admin/bands/{id}` | 🔒 admin | Edit band |
| DELETE | `/api/admin/bands/{id}` | 🔒 admin | Hapus band (cek BR-2) |
| POST | `/api/admin/bands/{id}/logo` | 🔒 admin | Upload logo band (drag&drop) |
| GET | `/api/admin/users` | 🔒 admin | List user |
| PUT | `/api/admin/users/{id}` | 🔒 admin | Edit user |
| DELETE | `/api/admin/users/{id}` | 🔒 admin | Hapus (soft) user |

---

## 7. Catatan Pembagian Kerja

Karena backend & frontend dev bekerja **tanpa komunikasi**, keduanya cukup berpegang pada dokumen ini:

- **Backend dev**: implementasi persis nama field, tipe, status code, dan struktur `{success, data}` di atas. Bebas menentukan struktur internal (Service class, Repository, dll) selama kontrak response tidak berubah. Wajib pakai view SQL (§2.1) dan prepared statement (Eloquent/Query Builder) — dilarang raw string concat query.
- **Frontend dev**: bisa mulai membangun UI sekarang dengan data dummy/mock yang bentuknya identik dengan contoh JSON di atas, lalu tinggal ganti base URL saat backend siap. `apiRequest()` di `js/api.js` harus otomatis menyisipkan header `Authorization` jika token ada di localStorage, dan menangani 401 dengan auto-redirect ke `login.html`.
- Field yang bertanda opsional di request boleh tidak dikirim sama sekali (bukan dikirim `null`).
- Semua tanggal/waktu dalam response berformat ISO 8601 dengan offset `+07:00` (mis. `2026-07-13T10:15:00+07:00`), kecuali field murni `DATE` (`event_date`: `YYYY-MM-DD`) atau `TIME` (`start_time`: `HH:mm`).
