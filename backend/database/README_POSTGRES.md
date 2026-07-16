# HakoStar — Database untuk PostgreSQL

Folder ini adalah versi `database/` (migrations + seeders) yang sudah disesuaikan supaya jalan di PostgreSQL, menggantikan folder `database/` versi MySQL sebelumnya.

## Apa yang berubah, apa yang tidak

| File | Status | Kenapa |
|---|---|---|
| `2026_01_01_000001` s/d `2026_01_01_000007` (semua migration tabel) | **Tidak berubah, disalin apa adanya** | Ditulis pakai Laravel Schema Builder (`Schema::create`, `$table->...`), bukan raw SQL. Laravel otomatis menerjemahkan ini ke dialek SQL yang sesuai driver aktif (`unsignedSmallInteger` → `smallint` di Postgres, `enum(...)` → `CHECK` constraint di Postgres, dst). Tidak ada bagian yang MySQL-only. |
| `2026_01_01_000008_create_views.php` | **Diubah** | Versi asli pakai `DB::statement()` dengan SQL mentah MySQL: fungsi `CURDATE()` dan `TIMESTAMP(date, time)` tidak ada di PostgreSQL. Lihat detail konversi di bawah. |
| `AdminUserSeeder.php`, `DatabaseSeeder.php`, `DummyDataSeeder.php` | **Tidak berubah, disalin apa adanya** | Murni pakai Eloquent (`Model::firstOrCreate`), tidak menyentuh SQL sama sekali — otomatis portable. |

Nama view, nama kolom, dan struktur hasil (`v_event_status`, `v_slot_detail`, `v_ticket_history`) **tetap identik** dengan `API_CONTRACT.md` §2.1 — jadi tidak ada perubahan apa pun yang dibutuhkan di `app/Models/*View.php`, Controller, atau Resource. Kontrak API ke frontend tidak terpengaruh sama sekali oleh penggantian database ini.

## Detail Konversi SQL (migration 008)

| MySQL (lama) | PostgreSQL (baru) | Keterangan |
|---|---|---|
| `CURDATE()` | `CURRENT_DATE` | Fungsi tanggal hari ini |
| `TIMESTAMP(e.event_date, x)` | `(e.event_date + x)` | Postgres tidak punya fungsi `TIMESTAMP()` untuk gabung date+time. Tapi operator `+` antara kolom `date` dan `time` di Postgres otomatis menghasilkan `timestamp` — jadi cukup dijumlahkan langsung. |
| `NOW()` | `NOW()` | Sama, tidak berubah. |
| `CREATE OR REPLACE VIEW` | `CREATE OR REPLACE VIEW` | Sama, didukung penuh di Postgres. |

## Langkah Setup

1. **Install driver PHP untuk Postgres** (kalau belum ada):
   ```bash
   sudo apt install php-pgsql   # Linux
   # pastikan extension=pgsql dan extension=pdo_pgsql aktif di php.ini
   php -m | grep pgsql          # harus muncul: pgsql, pdo_pgsql
   ```

2. **Buat database & user Postgres**:
   ```bash
   sudo -u postgres psql
   CREATE DATABASE hakostar;
   CREATE USER hakostar_user WITH ENCRYPTED PASSWORD 'password_kamu';
   GRANT ALL PRIVILEGES ON DATABASE hakostar TO hakostar_user;
   \q
   ```

3. **Set `.env`** di root project Laravel:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=hakostar
   DB_USERNAME=hakostar_user
   DB_PASSWORD=password_kamu
   ```

4. **Ganti folder `database/migrations/` dan `database/seeders/`** di project lokal kamu dengan isi folder ini (timpa yang lama).

5. **Migrate + seed seperti biasa**:
   ```bash
   php artisan migrate --seed
   ```

6. **Verifikasi 3 view sudah terbentuk**:
   ```bash
   php artisan tinker
   >>> DB::select("SELECT * FROM v_event_status LIMIT 1");
   >>> DB::select("SELECT * FROM v_slot_detail LIMIT 1");
   >>> DB::select("SELECT * FROM v_ticket_history LIMIT 1");
   ```
   Kalau ketiganya balas array (walau kosong jika belum ada data), berarti view sudah kebentuk benar. Kalau error `function ... does not exist` atau semacamnya, cek ulang bagian `CASE WHEN` di migration 008 — biasanya karena masih ada sisa sintaks MySQL yang belum terganti.

## Yang Perlu Diperhatikan Setelah Pindah ke Postgres

- **Case-sensitivity nama tabel/kolom**: Postgres default melipat identifier ke lowercase kecuali diberi tanda kutip ganda. Semua nama tabel/kolom di migration ini sudah lowercase + snake_case, jadi aman, tidak perlu tanda kutip di query manual.
- **`stock_available` di `v_slot_detail`**: hasil pengurangan integer, perilakunya sama di kedua database, tidak ada perbedaan.
- **Row lock BR-1** (`lockForUpdate()` di `TicketPurchaseService`) tidak perlu diubah — Eloquent menerjemahkannya ke `SELECT ... FOR UPDATE` yang didukung penuh di PostgreSQL dengan semantik locking yang sama.
- **Tidak ada kode error MySQL (mis. `1062` duplicate entry) yang di-hardcode** di codebase (sudah dicek), jadi tidak ada penyesuaian tambahan di `app/Exceptions/Handler.php` atau service lain yang diperlukan untuk pesan error unique constraint.
