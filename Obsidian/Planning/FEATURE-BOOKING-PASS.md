---
title: Feature Planning — Booking Pass System
status: APPROVED
created: 2026-05-13
phase: 10c
---

# Feature Planning — Booking Pass System (Phase 10c)

> Kembali ke: [[FEATURE-RESERVATION-SYSTEM]]
> Related: [[FEATURE-FRAUD-DETECTION]]

---

## Latar Belakang

Setiap reservasi yang berhasil harus menghasilkan Booking Pass — dokumen yang dibawa customer saat datang ke lokasi. Booking Pass bisa berupa:
- **Default**: Template standar TSBL (generated PDF via DomPDF)
- **Custom**: Template khusus per partner (untuk kerjasama branding)

User juga harus bisa menaruh data bebas di booking pass (no. reservation, custom fields, dll).

---

## Keputusan Desain

| Keputusan | Pilihan | Alasan |
|---|---|---|
| PDF engine | DomPDF (existing) | Sudah terinstall, konsisten dengan invoice PDF |
| Template storage | File upload + DB metadata | Fleksibel, admin bisa manage |
| Custom fields | JSON column `booking_pass_data` | Unlimited flexibility tanpa alter schema |
| Template per partner | `booking_pass_templates` tabel | Satu partner bisa punya >1 template |
| Default template | Blade view (hardcoded layout) | Cepat, maintainable, bisa di-customize developer |
| Booking pass output | PDF download + on-screen preview | Customer bisa screenshot atau print |

---

## Struktur Data

### Tabel `booking_pass_templates`
```
id                    bigint PK
partner_id            bigint FK nullable     -- null = default template (semua partner)
template_name         varchar(255)
template_file         varchar(255) nullable  -- path file upload (gambar/PDF background)
field_mapping         json nullable          -- mapping data reservasi ke posisi di template
is_active             boolean default true
created_by            bigint FK
created_at, updated_at
```

### Field Mapping JSON Structure
```json
{
  "fields": [
    {"key": "reservation_no", "label": "No. Reservation", "position": "top-right"},
    {"key": "guest_name", "label": "Guest Name", "position": "center"},
    {"key": "visit_date", "label": "Visit Date", "position": "center"},
    {"key": "partner_name", "label": "Partner", "position": "bottom-left"},
    {"key": "custom_1", "label": "Voucher Code", "position": "bottom-right"}
  ]
}
```

### Booking Pass Data (di `reservations.booking_pass_data`)
```json
{
  "no_reservation": "RSV-EXT-12345",
  "voucher_code": "HOTEL-PROMO-2026",
  "special_notes": "VIP Guest",
  "custom_field_1": "any value user wants"
}
```

---

## Model

```
BookingPassTemplate
  belongsTo(Partner)              -- nullable (null = default)
  belongsTo(User, 'created_by')
  hasMany(Reservation)
```

---

## Alur Booking Pass

### Generate Default Booking Pass
```
1. Reservasi CONFIRMED
2. Sistem cek: partner punya custom template?
   - Ya -> pakai template custom
   - Tidak -> pakai default template
3. Render Blade view dengan data:
   - reservation_no, guest_name, visit_date
   - products list (nama + qty)
   - partner name
   - booking_pass_data (custom fields)
   - QR code berisi reservation_no (untuk scan di lokasi)
4. Generate PDF via DomPDF
5. Simpan ke storage, update reservations.booking_pass_file
6. Tampilkan preview di layar + tombol download
```

### Generate Custom Booking Pass
```
1. Admin upload template background (gambar/PDF) untuk partner tertentu
2. Define field_mapping: posisi setiap data di template
3. Saat reservasi CONFIRMED:
   - Load template background
   - Overlay data reservasi sesuai field_mapping
   - Generate final PDF
4. Simpan + tampilkan
```

### Upload Manual Booking Pass
```
1. Untuk case tertentu, user bisa upload booking pass manual (file image/PDF)
2. Override generated booking pass
3. File disimpan di reservations.booking_pass_file
```

---

## Default Booking Pass Layout (PDF)

```
+--------------------------------------------------+
|  [TSBL LOGO]              BOOKING PASS            |
|                                                    |
|  Reservation No: RES-20260515-0012                 |
|  -------------------------------------------------|
|                                                    |
|  Guest Name    : John Doe                          |
|  Visit Date    : 15 May 2026                       |
|  Partner       : Hotel ABC                         |
|                                                    |
|  Products:                                         |
|  +------+---------------------------+-----+------+ |
|  | No.  | Product                   | Qty | Rate | |
|  +------+---------------------------+-----+------+ |
|  | 1    | Trans Studio Theme Park   |  2  | 250K | |
|  | 2    | Water World               |  2  | 150K | |
|  +------+---------------------------+-----+------+ |
|                                                    |
|  Total: Rp 800.000                                 |
|                                                    |
|  Custom Fields:                                    |
|  No. Reservation : RSV-EXT-12345                   |
|  Voucher Code    : HOTEL-PROMO-2026                |
|                                                    |
|  [QR CODE: RES-20260515-0012]                      |
|                                                    |
|  * Harap tunjukkan booking pass ini saat            |
|    check-in di lokasi Trans Studio Bali            |
+--------------------------------------------------+
```

---

## Controllers & Routes

### BookingPassController
```
GET  /booking-pass-templates           -> index (ADMIN)
GET  /booking-pass-templates/create    -> create (ADMIN)
POST /booking-pass-templates           -> store (ADMIN)
GET  /booking-pass-templates/{id}/edit -> edit (ADMIN)
PUT  /booking-pass-templates/{id}      -> update (ADMIN)
DELETE /booking-pass-templates/{id}    -> destroy (ADMIN)
```

### Booking Pass Generation (di ReservationController)
```
GET  /reservations/{id}/booking-pass       -> preview (authenticated)
GET  /reservations/{id}/booking-pass/pdf   -> download PDF (authenticated)
POST /reservations/{id}/booking-pass/upload -> upload manual (ADMIN/FINANCE)
```

### Public Booking Pass (untuk partner & self-service)
```
GET  /reserve/{token}/booking-pass/{reservation_no}  -> preview (token-based)
GET  /self-service/booking-pass/{reservation_no}     -> preview (public, time-limited)
```

---

## BookingPassService

```php
class BookingPassService
{
    public function generate(Reservation $reservation): string
    {
        // 1. Determine template (custom or default)
        // 2. Build data array from reservation + items + booking_pass_data
        // 3. Generate QR code (reservation_no)
        // 4. Render Blade view
        // 5. Generate PDF via DomPDF
        // 6. Store file, return path
    }

    public function getTemplate(Reservation $reservation): ?BookingPassTemplate
    {
        // Check partner-specific template first
        // Fallback to default (partner_id = null)
    }
}
```

---

## Implementasi Steps

```
Agent: Senior Developer
File: agency-agents/engineering/engineering-senior-developer.md
Reason: Laravel DomPDF, template management, file upload patterns
```

15. Migration `2026_05_13_100006_create_booking_pass_templates_table`
16. Model `BookingPassTemplate` + relationships
17. `BookingPassController` CRUD (ADMIN only)
18. `BookingPassService` — generate/template logic
19. Blade view: `booking-pass/pdf.blade.php` (default template)
20. Views: `booking-pass-templates/index`, `create`, `edit`
21. QR code generation (simple-qrcode package atau inline SVG)

---

## UI/UX Requirements

### Template Management (Admin)
- List templates dengan preview thumbnail
- Form upload: drag-and-drop area + file input fallback
- Field mapping: visual editor (stretch goal) atau JSON input (MVP)
- Preview button: lihat hasil booking pass sebelum save

### Booking Pass Preview (All Users)
- Responsive preview di browser (tidak perlu download dulu)
- Download PDF button prominent (primary CTA)
- Print button (window.print())
- Share button (copy link) untuk partner/self-service

### Accessibility
- PDF harus readable (proper font size, contrast)
- QR code cukup besar (min 100x100px) untuk scan
- Alt text pada QR code image
