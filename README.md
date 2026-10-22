# Helpdesk IT — Aplikasi Tiket Bantuan IT Sederhana

Mencatat dan mengelola permintaan bantuan IT (internet bermasalah, printer rusak, aplikasi error, dll).
Terdiri dari **halaman web**, **REST API**, dan **proses otomatis terjadwal**. Zona waktu: **Asia/Jakarta (WIB)**.

## Teknologi yang digunakan

| Bagian | Teknologi |
|---|---|
| Bahasa / runtime | PHP **8.3+** |
| Framework | **Laravel 13** (skeleton resmi `laravel/laravel`) |
| Tampilan | **Bootstrap 5.3.3** + Blade Components Global + CSS pelengkap (tanpa build step / npm) |
| Database | **MySQL 8** (MariaDB 10.6+ juga seharusnya bisa) |
| Penyimpanan File | Private disk (`storage/app/private`), diakses via rute terproteksi |
| REST API | Laravel API routes + API Resource + autentikasi **API key** (header `X-API-Key`) |
| Validasi | Form Request (server-side) dengan pesan Bahasa Indonesia spesifik per file |
| Penanda Terlambat | Kolom tersimpan di DB (`is_overdue`) + diperbarui berkala via Scheduler |
| Proses terjadwal | Laravel Scheduler + Artisan Command (`tickets:check-overdue`) |
| Test | PHPUnit 12 (SQLite in-memory) |
| Dependency manager | Composer |

---

## Prasyarat & Pengaturan php.ini

Prasyarat: PHP 8.3+, Composer, MySQL.

### Ekstensi PHP yang wajib aktif:
- `pdo_mysql` (koneksi database utama)
- `fileinfo` (**wajib** untuk verifikasi isi MIME type file lampiran di server)
- `pdo_sqlite` / `sqlite3` (**wajib** untuk menjalankan test suite PHPUnit in-memory)
- `mbstring`, `xml`, `ctype`, `json`, `openssl`, `tokenizer`

### Pengaturan `php.ini` penting untuk Upload File:
Aplikasi mengizinkan hingga **5 file** lampiran per tiket dengan batas **5 MB per file** (maksimum ~25 MB payload total per request). Pastikan nilai `php.ini` Anda memenuhi batas ini:

```ini
; Mengizinkan upload minimal 5-10 MB per file tunggal
upload_max_filesize = 10M

; Mengizinkan pengiriman 5 file x 5 MB sekaligus ditambah form data
post_max_size = 35M

; Memori eksekusi PHP yang memadai
memory_limit = 128M

; Jumlah file maksimum dalam 1 kali upload
max_file_uploads = 20
```

---

## Cara menjalankan

```bash
composer install
cp .env.example .env
php artisan key:generate

# 1) buat database kosong di MySQL
mysql -u root -p -e "CREATE DATABASE helpdesk_it CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2) edit .env: DB_USERNAME, DB_PASSWORD, dan TICKET_API_KEY (isi string acak panjang)
#    contoh membuat key:  php -r "echo bin2hex(random_bytes(24));"

php artisan migrate --seed     # menjalankan migrasi (termasuk is_overdue & lampiran) + data contoh
php artisan serve              # http://localhost:8000
```

Halaman web: <http://localhost:8000> (otomatis ke `/tickets`).

---

## Proses Terjadwal (Scheduler) & Keterlambatan Tiket

Penanda keterlambatan disimpan pada kolom database `is_overdue` (boolean). Kolom ini dijaga konsisten oleh aplikasi saat ada aksi pengguna (buat tiket / ubah status) dan diperbarui secara otomatis oleh proses terjadwal akibat berjalannya waktu.

### Menjalankan Scheduler

Command utama: `tickets:check-overdue`
Jadwal: **Setiap 5 menit** (`everyFiveMinutes()`) dengan proteksi `withoutOverlapping()` pada zona waktu `Asia/Jakarta`.

1. **Di Lingkungan Lokal (Pengembangan):**
   ```bash
   php artisan schedule:work
   ```
   Perintah ini akan menjalankan worker scheduler di foreground yang mengevaluasi jadwal setiap menit.
   
   - Melihat daftar tugas terjadwal:
     ```bash
     php artisan schedule:list
     ```
   - Menguji manual eksekusi command sekali jalan:
     ```bash
     php artisan tickets:check-overdue
     ```
     *(Catatan: command lama `tickets:mark-overdue` tetap tersedia sebagai alias deprecated yang meneruskan ke command ini).*

2. **Di Server Produksi (Cron):**
   Pasang cron job di server yang berjalan **setiap menit**:
   ```bash
   * * * * * cd /path/ke/proyek && php artisan schedule:run >> /dev/null 2>&1
   ```
   > **Penting:** Cron server sengaja dipanggil setiap menit (`* * * * *`) agar Laravel Scheduler dapat mengecek apakah ada jadwal yang jatuh tempo. Framework Laravel sendiri yang memutuskan bahwa perintah `tickets:check-overdue` hanya dieksekusi setiap interval 5 menit.

### Format Output Konsol & Log

Command `tickets:check-overdue` mencatat informasi eksekusinya ke console dan ke file log (`storage/logs/laravel.log`) dengan format:

```text
Diperiksa 120 tiket; 3 jadi terlambat, 1 kembali tidak terlambat (total diubah 4).
```

**Arti Angka:**
- **Diperiksa:** Jumlah total seluruh baris tiket yang ada di database saat command dievaluasi (`Ticket::count()`).
- **Jadi terlambat:** Jumlah tiket belum selesai (non-done) yang batas waktunya telah terlewati (`due_at < now()`) dan kolom `is_overdue`-nya berhasil diubah dari `false` menjadi `true`.
- **Kembali tidak terlambat:** Jumlah tiket yang sebelumnya `is_overdue = true` namun saat ini seharusnya tidak terlambat (misalnya statusnya telah diubah menjadi `done`, atau batas waktunya diperpanjang sehingga `due_at >= now()`), yang berhasil dikoreksi menjadi `false`.
- **Total diubah:** Penjumlahan tiket yang diubah ke terlambat + kembali tidak terlambat. Bila dijalankan ulang tanpa ada perubahan waktu/data (idempotent), total diubah adalah **0**.

### Konsekuensi Mekanisme Kolom DB + Scheduler
- **Aksi Pengguna (Langsung Konsisten):** Ketika pengguna membuat tiket baru atau mengubah status tiket (mis. dari *Open terlambat* menjadi *Done*, atau dari *Done* dibuka kembali menjadi *Open*), nilai kolom `is_overdue` langsung dihitung dan disimpan saat itu juga (real-time).
- **Berjalannya Waktu (Maksimal ~5 Menit):** Tiket yang sedang *Open* dan melewati batas waktu di tengah hari akan ditandai terlambat pada siklus scheduler berikutnya (maksimal selisih 5 menit).

---

## Aturan Bisnis (Sumber Kebenaran Keterlambatan)

Sebuah tiket terlambat (`is_overdue = true`) **jika dan hanya jika**: batas waktu (`due_at`) sudah lewat dari waktu sekarang (WIB) **DAN** status tiket **BUKAN** `done`.

### Alur Transisi Status & Tabel Skenario

Transisi status bersifat **satu arah (forward-only)** untuk menjaga integritas proses:
- **`open`** → dapat berpindah ke `in_progress` atau `done`.
- **`in_progress`** → hanya dapat berpindah ke `done` (tidak bisa dibalikkan lagi ke `open`).
- **`done`** → status akhir; tidak dapat diubah kembali ke status manapun.
- Mengirim status yang sama tetap valid dan bersifat **idempotent**.

| Status Tiket | Kondisi Batas Waktu (`due_at`) | Nilai `is_overdue` | Keterangan & Transisi yang Diizinkan |
|---|---|---|---|
| `open` | Belum lewat (`due_at >= now()`) | `false` | Tiket baru. Bisa dipindah ke `in_progress` atau `done`. |
| `open` | Sudah lewat (`due_at < now()`) | `true` | Melewati batas waktu. Bisa dipindah ke `in_progress` atau `done`. |
| `in_progress` | Belum lewat (`due_at >= now()`) | `false` | Sedang dikerjakan. **Hanya bisa dipindah ke `done`** (tidak bisa balik ke `open`). |
| `in_progress` | Sudah lewat (`due_at < now()`) | `true` | Lewat deadline. **Hanya bisa dipindah ke `done`** (tidak bisa balik ke `open`). |
| `done` | Selesai (kapan pun) | `false` | Tiket selesai **selalu tidak terlambat** dan **tidak dapat diubah kembali**. |

---

## Fitur Web

| Fitur | Keterangan |
|---|---|
| Daftar tiket | Judul, pelapor, prioritas, status, batas waktu, penanda terlambat (membaca `is_overdue`), dan penanda jumlah lampiran (mis. `📎 2 file`). Pagination 10 per halaman. |
| Tambah tiket | Judul, nama pelapor, deskripsi, prioritas, batas waktu, dan lampiran opsional (maksimal 5 file, PDF/foto JPG, PNG, WebP, GIF, BMP, maks 5 MB per file). Status otomatis `open` dan `is_overdue` dihitung konsisten. |
| Detail tiket | Seluruh informasi tiket, status, batas waktu, status `is_overdue`, serta daftar lampiran lengkap beserta thumbnail foto. |
| Unduh lampiran | Route khusus `GET /tickets/{ticket}/attachments/{attachment}` yang membaca dari disk private dengan header `X-Content-Type-Options: nosniff`. |
| Ubah status | Tombol Open / In progress / Done dengan validasi satu arah. Status `in_progress` dan `done` tidak dapat dibalikkan lagi. |
| Pencarian & filter | Cari judul tiket dan filter status. |

---

## REST API

Semua endpoint **wajib** header `X-API-Key: <nilai TICKET_API_KEY>`. Format JSON untuk data dan file stream untuk unduhan lampiran.

| Method & path | Fungsi | Sukses |
|---|---|---|
| `GET /api/tickets` | Daftar tiket (mendukung filter `?status=` dan `?overdue=true|false` berbasis index kolom `is_overdue`) | 200 |
| `GET /api/tickets/{id}` | Detail tiket beserta lampiran dan flag `is_overdue` | 200 |
| `POST /api/tickets` | Buat tiket baru (status otomatis `open`, `is_overdue` otomatis `false`) | 201 + header `Location` |
| `PATCH /api/tickets/{id}/status` | Ubah status tiket (`is_overdue` langsung diperbarui konsisten) | 200 |
| `GET /api/tickets/{ticket}/attachments/{attachment}` | Unduh lampiran dari disk private | 200 (file stream) |
| `POST /api/tickets/{ticket}/attachments` | Tambah lampiran ke tiket yang ada (maksimal akumulatif 5 file) | 201 |

### Contoh Format JSON Respons Tiket

```json
{
  "data": {
    "id": 1,
    "title": "Printer lantai 3 mati",
    "reporter_name": "Rina",
    "description": "Tidak bisa mencetak.",
    "priority": "high",
    "status": "open",
    "due_at": "2026-10-22T10:00:00+07:00",
    "is_overdue": true,
    "overdue_marked_at": "2026-10-22T10:05:00+07:00",
    "done_at": null,
    "created_at": "2026-10-22T08:00:00+07:00",
    "updated_at": "2026-10-22T10:05:00+07:00",
    "attachments": []
  }
}
```

---

## Menjalankan Test

```bash
php artisan test
```

Test suite Feature (PHPUnit 12, SQLite in-memory) mencakup:
- **`CheckOverdueCommandTest`:**
  - Open lewat batas waktu → berubah jadi terlambat setelah command dijalankan.
  - In progress sebelum batas waktu → tetap tidak terlambat.
  - Done lewat batas waktu → tetap tidak terlambat.
  - Done dibuka kembali setelah batas waktu lewat → langsung `is_overdue = true` tanpa menunggu command.
  - Done dibuka kembali sebelum batas waktu → tetap `false`.
  - Open terlambat lalu di-Done lewat web dan API → `is_overdue = false`.
  - Data tidak konsisten (status Done tapi flag true) → dikoreksi otomatis oleh command.
  - Idempotensi command (dijalankan berulang kali menghasilkan 0 perubahan).
  - Format output console dan log command memuat jumlah diperiksa dan diubah.
  - Tiket baru dengan due_at masa depan → `is_overdue = false` dan filter API `?overdue=true|false` akurat.
- **`TicketAttachmentTest`:** pengujian upload PDF/foto (JPG, PNG, WebP, GIF), batas 5 file, batas 5 MB, rute proteksi, dan pembersihan file fisik.
- **`TicketApiTest` & `TicketWebTest`:** pengujian endpoint REST API dan halaman web Blade.

---

## Komponen UI & Desain Tampilan

Aplikasi menggunakan **Bootstrap 5.3.3** yang dimuat melalui CDN jsDelivr dengan Subresource Integrity (SRI), dilengkapi dukungan Dark Mode (`data-bs-theme`) dan palet warna kustom via `public/css/app.css` (aksen teal `#0b6e6e` & merah kustom `#b42318`). Seluruh view Blade telah dimodernisasi menggunakan **Blade Components global** tanpa build step (Vite/Node/npm).

### Cara Memuat Bootstrap: CDN vs. Lokal

#### 1. Pilihan Default: CDN jsDelivr (Aktif Saat Ini)
Secara default, aset Bootstrap 5.3.3 dipin dengan hash Subresource Integrity (SRI) dan `crossorigin="anonymous"` di [app.blade.php](file:///c:/Users/imanv/OneDrive/Documents/kantor/it%20ticket/resources/views/components/layouts/app.blade.php):
- **CSS:**
  ```html
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  ```
- **JS Bundle (Termasuk Popper):**
  ```html
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  ```
*Alasan memilih CDN sebagai default:* Setup instan tanpa mengotori repositori git dengan binary/vendor, browser caching yang cepat dan terdistribusi secara global, serta perlindungan integritas berkas via SRI.

#### 2. Opsi Offline: Mengunduh ke `public/vendor/bootstrap/`
Jika aplikasi digunakan pada intranet atau kantor tanpa akses internet publik:
1. Unduh `bootstrap.min.css` dan `bootstrap.bundle.min.js` versi 5.3.3 dari situs resmi Bootstrap.
2. Letakkan berkas di folder proyek:
   ```
   public/vendor/bootstrap/
     ├── css/bootstrap.min.css
     └── js/bootstrap.bundle.min.js
   ```
3. Ubah referensi di [resources/views/components/layouts/app.blade.php](file:///c:/Users/imanv/OneDrive/Documents/kantor/it%20ticket/resources/views/components/layouts/app.blade.php):
   ```html
   <!-- Ganti link CDN CSS dengan: -->
   <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">

   <!-- Ganti script CDN JS dengan: -->
   <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
   ```

---

### Daftar Blade Components

Semua komponen terletak di `resources/views/components/` sebagai anonymous components yang menggabungkan class Bootstrap via `$attributes->merge()`:

| Komponen | Tag Blade | Props Utama | Contoh Pemakaian |
|---|---|---|---|
| **Layout Utama** | `<x-layouts.app>` | `title` | `<x-layouts.app title="Daftar Tiket">...</x-layouts.app>` |
| **Page Header** | `<x-page-header>` | `title`, `description` | `<x-page-header title="Tiket #1"><x-slot:actions><x-button .../></x-slot></x-page-header>` |
| **Kartu Konten** | `<x-card>` | `title`, `class` | `<x-card title="Informasi Tiket">isi konten</x-card>` |
| **Tombol / Link** | `<x-button>` | `variant` (primary/secondary/danger/outline-primary/outline-secondary/outline-danger), `size` (sm/md/lg), `href`, `disabled`, `type` | `<x-button variant="primary" href="{{ route('tickets.create') }}">Buat Tiket</x-button>` |
| **Badge Umum** | `<x-badge>` | `variant` (primary/secondary/success/warning/danger/info/teal), `pill` (bool) | `<x-badge variant="teal">Label</x-badge>` |
| **Badge Status** | `<x-status-badge>` | `:status` (instance `TicketStatus` atau string 'open'/'in_progress'/'done') | `<x-status-badge :status="$ticket->status" />` |
| **Badge Prioritas** | `<x-priority-badge>` | `:priority` (instance `TicketPriority` atau string 'low'/'medium'/'high') | `<x-priority-badge :priority="$ticket->priority" />` |
| **Badge Terlambat** | `<x-overdue-badge>` | - | `<x-overdue-badge />` (menampilkan label teks "Terlambat") |
| **Alert / Notifikasi** | `<x-alert>` | `type` (success/danger/warning/info), `dismissible` (bool) | `<x-alert type="success">Tiket berhasil disimpan!</x-alert>` |
| **Form Field Wrapper** | `<x-form.field>` | `name`, `label`, `required` (bool), `hint`, `id` | `<x-form.field name="title" label="Judul Tiket" required><x-form.input .../></x-form.field>` |
| **Form Input** | `<x-form.input>` | `name`, `type`, `value`, `placeholder`, `required` | `<x-form.input name="title" :value="old('title')" required />` |
| **Form Textarea** | `<x-form.textarea>` | `name`, `rows`, `value`, `required` | `<x-form.textarea name="description" rows="4">{{ old('description') }}</x-form.textarea>` |
| **Form Select** | `<x-form.select>` | `name`, `required` | `<x-form.select name="priority">...</x-form.select>` |
| **Filter Tabs** | `<x-filter-tabs>` | - | Mengelompokkan item `<x-tab>` dalam `.nav .nav-pills` |
| **Tab Item** | `<x-tab>` | `href`, `active` (bool), `count` (int/optional) | `<x-tab :href="route('tickets.index')" :active="true" :count="5">Semua</x-tab>` |
| **Empty State** | `<x-empty-state>` | `title`, `description`, `actionText`, `actionHref` | `<x-empty-state title="Belum ada tiket" actionText="Buat Tiket" ... />` |
| **Description List** | `<x-description-list>` | - | Bungkus `<dl class="row">` untuk detail informasi |
| **Description Item** | `<x-description-item>`| `label`, `col` (default 'sm-4') | `<x-description-item label="Pelapor">{{ $ticket->reporter_name }}</x-description-item>` |
| **Pagination** | `pagination/default` | Dipanggil otomatis via Laravel | `{{ $tickets->links('pagination.default') }}` |

---

## Struktur Kode Penting

```
app/Enums/                     TicketStatus, TicketPriority
app/Models/Ticket.php          computeOverdue, createOpen, changeStatus, scopeOverdue, relasi attachments
app/Models/TicketAttachment.php model lampiran & pembersihan berkas
app/Services/TicketAttachmentService.php penyimpanan atomik di private storage
app/Console/Commands/          CheckOverdueTickets (utama), MarkOverdueTickets (deprecated alias)
app/Http/Controllers/          TicketController, TicketAttachmentController (web)
app/Http/Controllers/Api/      TicketController, TicketAttachmentController (REST)
app/Http/Requests/             StoreTicketRequest, AddTicketAttachmentsRequest, UpdateTicketStatusRequest, Api/ListTicketsRequest
app/Http/Resources/            TicketResource
routes/                        web.php, api.php, console.php (scheduler)
database/migrations/           create_tickets, create_ticket_attachments, add_is_overdue_to_tickets
tests/Feature/                 CheckOverdueCommandTest, TicketAttachmentTest, TicketApiTest, TicketWebTest, MarkOverdueCommandTest
```

---

## Status Penyelesaian & Hal yang Belum Diverifikasi

**Fitur Sudah Selesai Diimplementasikan:**
1. Kolom database `is_overdue` (boolean default false, dengan index gabungan `['is_overdue', 'status']`) ditambahkan melalui migrasi baru terpisah.
2. Backfill data otomatis pada migrasi baru untuk mengisi tiket yang sudah lewat batas waktu.
3. Kolom `overdue_marked_at` dipertahankan sebagai historical audit timestamp (opsi paling aman tanpa memutus kompatibilitas skema).
4. Satu sumber logika di `Ticket::computeOverdue()` digunakan di semua alur (pembuatan tiket baru, perubahan status, dan scheduler).
5. Command baru `tickets:check-overdue` set-based, bebas race condition, idempotent, dan mencatat log jumlah diperiksa dan diubah ke `storage/logs/laravel.log`.
6. Command lama `tickets:mark-overdue` dijadikan alias deprecated yang aman.
7. Seluruh tampilan web, API resource, dan filter query telah disesuaikan membaca kolom `is_overdue`.

**Catatan yang perlu diperhatikan / belum diverifikasi di lingkungan lokal:**
1. **Migrasi Baru Database MySQL:** Jalankan perintah `php artisan migrate` di terminal untuk menambahkan kolom `is_overdue` beserta index dan backfill data pada database MySQL lokal Anda.
2. **Driver SQLite CLI untuk PHPUnit:** Pengujian `php artisan test` berjalan di SQLite in-memory. Pastikan ekstensi `pdo_sqlite` aktif di `php.ini` CLI Windows Anda jika ingin menjalankan test suite otomatis.
