# HakoStar — Panduan Pengerjaan

Dokumen pendamping `API_CONTRACT.md`. Isinya urutan kerja, apa yang **wajib** diikuti, dan apa yang **bebas** ditentukan sendiri oleh masing-masing dev. Karena backend dan frontend dikerjakan tanpa komunikasi langsung, kedua dokumen ini (kontrak + panduan) adalah satu-satunya sumber kebenaran. Kalau ada yang terasa ambigu, defaultnya: **backend mengikuti kontrak persis; frontend bebas selama konsumsi API-nya sesuai kontrak.**

---

## 0. Prinsip Pembagian Kebebasan

Yang **mengikat** (tidak boleh menyimpang tanpa update `API_CONTRACT.md` dulu):
- Nama field, tipe data, struktur JSON request/response
- Endpoint path, method, status code
- Skema tabel & nama view (§2 kontrak)
- Business rules BR-1 s/d BR-10

Yang **bebas** — sepenuhnya keputusan masing-masing dev, tidak perlu dikonfirmasi:
- **Frontend: seluruh desain visual.** Warna, tipografi, layout, komponen, animasi, framework CSS (Tailwind/Bootstrap/custom), struktur folder `css/` di dalamnya, mobile-first atau tidak, dark mode atau tidak. Kontrak API sama sekali tidak mendikte tampilan.
- **Backend: struktur kode internal.** Service class, Repository pattern, Form Request, Resource class — bebas asal output akhirnya cocok dengan kontrak.
- Urutan pengerjaan file/halaman masing-masing (asal urutan besar di §2/§3 dokumen ini tetap jadi acuan supaya tidak saling menunggu).

---

## 1. Persiapan Bersama (dilakukan sebelum pisah kerja, ±30 menit)

Ini satu-satunya titik yang idealnya disepakati bareng sebelum benar-benar kerja sendiri-sendiri, karena keduanya bergantung pada ini:

1. Sepakati **base URL dev** akan tetap `http://localhost:8000/api` (sudah tercantum di kontrak) — pastikan backend jalan di port itu, atau update kontrak kalau beda.
2. Backend dev jalankan `php artisan serve` dengan **CORS diaktifkan** untuk origin frontend (mis. `http://localhost:5500` kalau pakai Live Server). Ini wajib disiapkan di awal supaya frontend dev tidak stuck saat pertama kali fetch.
3. Sepakati bahwa **kontrak adalah sumber kebenaran final** — kalau di tengah jalan salah satu pihak merasa perlu ubah field/response, ubah dulu `API_CONTRACT.md`, baru masing-masing menyesuaikan kode. Jangan ubah diam-diam.

Setelah ini, keduanya bisa kerja total terpisah.

---

## 2. Panduan Backend Dev

### 2.1 Urutan Kerja yang Disarankan

1. **Setup project & migrasi.** Buat seluruh tabel di §2 kontrak sesuai urutan dependency: `users` → `user_profiles` → `bands` → `events` → `performance_slots` → `tickets`. Jangan lupa `deleted_at` (soft delete) di `bands` dan `users` sesuai BR-3/BR-10.
2. **Buat 3 view SQL** (`v_event_status`, `v_slot_detail`, `v_ticket_history`) lewat migration `DB::statement(...)` — SQL persis sudah ada di kontrak §2.1, tinggal pakai.
3. **Seeder**: 1 akun admin (role `admin`), beberapa band & event dummy untuk testing manual sebelum frontend terhubung.
4. **Auth** (`register`, `login`, `logout`, `me`) dulu — semua endpoint lain butuh ini untuk ditest.
5. **Endpoint publik** (`events`, `events/{id}`) — bisa langsung ditest tanpa token.
6. **Endpoint tiket** (`POST /tickets`, `history`, `download`) — implementasikan BR-1 & row lock (§2.2 kontrak) di sini.
7. **Endpoint admin** (events CRUD → slots CRUD → bands CRUD → users CRUD) — BR-2, BR-3, BR-4 paling relevan di tahap ini.
8. **Upload endpoint** (avatar, logo, poster) — paling akhir, karena tidak memblokir endpoint lain untuk ditest.

### 2.2 Checklist Wajib Sebelum Anggap Selesai

- [ ] Semua response mengikuti format `{success, data}` / `{success, message, errors}` persis seperti kontrak §1.1 — termasuk untuk error 500 (jangan biarkan Laravel default error page/JSON bocor ke frontend, tangani lewat `App\Exceptions\Handler`).
- [ ] Middleware token expire 2 jam berjalan (§1.3) dan mengembalikan `401` dengan `message: "SESSION_EXPIRED"` persis (frontend akan mencocokkan string ini).
- [ ] Middleware role admin (`403` untuk user biasa yang akses `/admin/*`).
- [ ] Tidak ada `DB::raw()` dengan string concat dari input request (wajib prepared statement, §2.2).
- [ ] BR-1 s/d BR-10 sudah diimplementasi dan bisa ditunjukkan lewat Postman/Insomnia (siapkan collection sendiri untuk bukti test, tidak wajib dikirim ke frontend dev tapi sangat disarankan untuk dokumentasi pribadi).
- [ ] CORS mengizinkan origin frontend dev di environment lokal.
- [ ] File upload (avatar/logo/poster) divalidasi tipe & ukuran sesuai kontrak (jpeg/png, max 2MB).

### 2.3 Yang Backend Dev Bebas Tentukan Sendiri

- Struktur folder di dalam `app/` (Http/Controllers, Services, Repositories — terserah).
- Nama class internal, nama migration file, nama variabel.
- Cara generate `ticket_code` (asal formatnya `HKS-` + string unik, sesuai contoh di kontrak).
- Library untuk generate QR + compose JPEG (`simple-qrcode`, `endroid/qr-code`, atau GD/Imagick manual) — bebas, asal output akhirnya 1 file JPEG gabungan sesuai deskripsi endpoint `tickets/{id}/download`.
- Cara menyimpan file upload (local disk `storage/`, asal path yang dikembalikan di response konsisten dengan contoh `/storage/...`).

---

## 3. Panduan Frontend Dev

### 3.1 Yang Perlu Ditegaskan di Awal: Desain 100% Bebas

**Tidak ada batasan visual dari dokumen kontrak.** Kontrak hanya mengatur *data apa yang tersedia dan lewat endpoint mana* — bukan bagaimana data itu ditampilkan. Silakan tentukan sendiri:
- Palet warna, font, spacing, layout grid/flex
- Framework CSS (murni custom, Bootstrap, Tailwind CDN, dll) — sesuaikan dengan folder `css/` yang sudah biasa dipakai di project sebelumnya
- Gaya kartu event/tiket, animasi hover, transisi halaman
- Struktur komponen halaman admin (sidebar, tab, modal-based, dll)
- Responsif atau tidak, dan sejauh mana

Satu-satunya "batasan" adalah: **field yang ditampilkan harus ada isinya dari response API** — tapi cara menyusun, urutan visual, atau elemen tambahan (ikon, ilustrasi, dsb) sepenuhnya kreativitas sendiri.

### 3.2 Bisa Mulai Duluan dengan Data Dummy

Karena kontrak sudah punya contoh JSON lengkap di tiap endpoint, frontend dev **tidak perlu menunggu backend selesai**. Salin contoh response dari `API_CONTRACT.md` jadi file dummy, misal:

```js
// js/mock/events.mock.js — dihapus/diabaikan nanti setelah backend siap
export const mockEventList = {
  success: true,
  data: [ /* copy dari contoh GET /api/events di kontrak §5.3 */ ],
  meta: { current_page: 1, per_page: 10, total: 12, last_page: 2 }
};
```

Bangun seluruh UI dan logic terhadap struktur ini dulu. Begitu backend siap, tinggal ganti pemanggilan dari mock ke `apiRequest()` sungguhan — karena bentuk datanya sudah identik, tidak perlu refactor besar.

### 3.3 Urutan Kerja yang Disarankan

1. **`js/api.js`** — bangun `apiRequest()` dan `requireAuth()` dulu, karena semua halaman lain bergantung ke sini.
2. **Landing page + auth** (`index.html`, `login.html`, `register.html`) — alur paling dasar, bisa ditest independen dengan mock login response.
3. **`concerts.html`** — list + filter (`search`, `status`) + pagination, pakai mock `GET /events` dulu.
4. **`concert-detail.html`** — tampilkan slot per band + form beli tiket (qty), validasi 800K juga bisa dicek ringan di frontend (UX saja — backend tetap validasi ulang, §BR-1).
5. **`profile.html`**, **`ticket-history.html`** — halaman privat, pasang `requireAuth()`.
6. **Admin pages** — paling terakhir karena paling kompleks (form CRUD + modal konfirmasi delete + drag&drop upload).

### 3.4 Checklist Wajib (Fungsional, Bukan Visual)

- [ ] `apiRequest()` otomatis menyisipkan header `Authorization: Bearer {token}` dari `localStorage` jika ada.
- [ ] Setiap response `401` → hapus token dari `localStorage`, redirect ke `login.html` (termasuk kasus `SESSION_EXPIRED`).
- [ ] `requireAuth()` dipasang di semua halaman privat (profile, ticket-history, admin/*) — cek token ada sebelum render, redirect kalau kosong.
- [ ] Halaman admin dicek role: kalau `role !== 'admin'` (dari `GET /me`) redirect keluar, jangan cuma disembunyikan lewat CSS.
- [ ] Setiap tombol **delete** (event, band, user, slot) memicu modal konfirmasi berisi detail spesifik item (nama + tanggal untuk event, dsb — sesuai BR-5) sebelum kirim request.
- [ ] Pagination & filter (`search`, `status`) terhubung ke query params sesuai kontrak §5.3, bukan filter manual di frontend dari data yang sudah diambil semua.
- [ ] Form beli tiket menampilkan estimasi total harga (`price * quantity`) secara real-time sebagai bantuan UX, dan menampilkan pesan error dari backend apa adanya kalau tetap lolos ke server (409/422).
- [ ] Drag & drop upload logo/poster — boleh pakai library ringan (mis. Dropzone.js) atau native `dragover`/`drop` event handler; endpoint backend tetap terima file biasa lewat multipart (§5.5b kontrak), jadi drag-drop murni logic sisi frontend.
- [ ] Download tiket (`GET /tickets/{id}/download`) di-handle sebagai file binary (`blob`), bukan `fetch().json()` — pastikan `apiRequest()` punya opsi untuk response non-JSON.

### 3.5 Yang Frontend Dev Bebas Tentukan Sendiri

- Semua hal di §3.1.
- Struktur file JS tambahan selain `api.js` (boleh satu file per halaman, boleh digabung, bebas).
- Library tambahan (chart, carousel, icon set, dll) selama tidak butuh backend baru.
- Pesan/copy teks UI (asal makna sama dengan `message` yang dikirim backend saat menampilkan error).

---

## 4. Titik Temu (Integrasi)

Setelah keduanya selesai kerja terpisah, satu-satunya langkah gabungan:

1. Frontend ganti seluruh pemanggilan mock → `apiRequest()` sungguhan, arahkan ke base URL backend yang jalan.
2. Test manual tiap alur: register → login → lihat konser → beli tiket → cek riwayat → download JPEG → (sisi admin) CRUD event/band/user + delete dengan modal konfirmasi.
3. Kalau ada mismatch (field hilang, tipe beda), **cek `API_CONTRACT.md` dulu** — pihak yang menyimpang dari kontraklah yang harus menyesuaikan, bukan tebak-tebakan siapa yang "benar".

---

## 5. Ringkasan Cepat

| | Backend | Frontend |
|---|---|---|
| Wajib ikuti | Skema tabel, view SQL, semua endpoint & response persis kontrak, BR-1–BR-10 | Struktur request/response, alur auth token, checklist §3.4 |
| Bebas tentukan | Struktur kode internal, library server-side | **Seluruh desain visual**, struktur file JS/CSS, library UI |
| Mulai dari | Migrasi & seeder → auth → publik → tiket → admin → upload | `api.js` → auth pages → publik → tiket → privat → admin |
| Tidak perlu tunggu satu sama lain karena | Kontrak sudah lengkap dengan contoh JSON di tiap endpoint | Bisa mock data dari contoh di kontrak sampai backend siap |
