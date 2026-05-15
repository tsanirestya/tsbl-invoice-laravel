---
title: Feature Planning — Reservation System
status: APPROVED
created: 2026-05-13
phase: 10
---

# Feature Planning — Reservation System (Phase 10)

> Kembali ke: [[MASTER-PLAN]]
> Related: [[FEATURE-BOOKING-PASS]], [[FEATURE-FRAUD-DETECTION]]

---

## Latar Belakang

TSBL membutuhkan sistem reservasi terpadu untuk mengelola booking customer sebelum kedatangan. Saat ini field `guest_name`, `visit_date`, `booking_pass_no` di tabel invoices hanya menyimpan data minimal. Sistem reservasi baru akan menjadi sumber utama data booking yang kemudian di-link ke invoice via `source_type` + `source_id`.

### Tujuan Utama
- Booking No. per reservasi (satuan)
- Reservasi bisa dilakukan oleh: **Partner (via token link)**, **Tim Internal**, **Customer (self-service QR)**
- Limitasi hari sebelum kedatangan (configurable)
- Pilih produk, tanggal kedatangan, qty
- Output = Booking Pass (default/custom template)
- Tracking lokasi setiap reservasi (GPS + IP)
- Integrasi penuh dengan sistem invoice & DSI export existing

---

## Keputusan Desain

| Keputusan | Pilihan | Alasan |
|---|---|---|
| Partner access | **A+B Hybrid**: Token link (self-service) + Tim internal input | Token = capture GPS partner asli untuk fraud detection. Internal = fallback untuk partner non-tech |
| Booking No. format | `RES-YYYYMMDD-XXXX` | Konsisten dengan pola existing (CP-YYYYMM-XXX, MP-YYYYMM-XXX) |
| Link ke Invoice | `invoices.source_type` + `source_id` | Field sudah ada di tabel invoices, tidak perlu migrasi baru |
| Limitasi hari | Via Settings model (existing) | Konsisten dengan pattern credit_warning_threshold dll |
| Payment method | 3 opsi: Transfer Gross, Transfer Nett, On the Spot | Masing-masing punya logic komisi berbeda |
| GPS capture | Browser Geolocation API | Tidak perlu native app, works di mobile browser |
| Self-service auth | QR token harian (tanpa login) | Customer tidak punya akun, QR di-reset tiap hari |
| Booking pass storage | File upload + generated PDF (DomPDF) | Konsisten dengan PDF system existing |

---

## Reservation Types

| Type | Siapa | Login? | Danger Zone Check? | GPS Capture? |
|---|---|---|---|---|
| `PARTNER` | Partner via token link | Tidak (token-based) | Ya | Ya (lokasi partner) |
| `INTERNAL` | Tim TSBL (SALES/ADMIN/FINANCE) | Ya (auth existing) | Tidak (trusted) | Ya (lokasi tim) |
| `SELF_SERVICE` | Customer via QR scan | Tidak (public) | Tidak | Ya (lokasi customer) |

---

## Payment Methods & Commission Logic

| Method | Yang Bayar | Bayar Berapa | Komisi Partner? | Alur |
|---|---|---|---|---|
| `TRANSFER_GROSS` | Partner/Agent | Gross (publish rate) | Ya, dapat komisi | Partner transfer gross -> TSBL verifikasi -> komisi dikreditkan |
| `TRANSFER_NETT` | Partner/Agent | Nett (setelah potong komisi) | Tidak | Partner transfer nett -> selesai |
| `ON_THE_SPOT` | Customer langsung | Gross (publish rate) | Tidak | Customer bayar di lokasi (cash/debit/credit) |

### Kalkulasi Otomatis (dari Product model existing)
```php
$gross = $product->publish_rate * $qty;
$nett  = $product->nett_price * $qty;
$commission = $gross - $nett; // atau $product->komisi * $qty
```

---

## Struktur Data Baru

### Tabel `reservations`
```
id                    bigint PK
reservation_no        varchar(20) UNIQUE    -- RES-YYYYMMDD-XXXX
partner_id            bigint FK nullable    -- null jika self-service
guest_name            varchar(255)
guest_country         varchar(100) nullable
visit_date            date
status                enum(PENDING, CONFIRMED, CANCELLED, NO_SHOW, COMPLETED)
reservation_type      enum(PARTNER, INTERNAL, SELF_SERVICE)
payment_method        enum(TRANSFER_GROSS, TRANSFER_NETT, ON_THE_SPOT) nullable
payment_channel       enum(CASH, DEBIT, CREDIT) nullable  -- khusus ON_THE_SPOT
booking_pass_type     enum(DEFAULT, CUSTOM)
booking_pass_template_id  bigint FK nullable
booking_pass_file     varchar(255) nullable  -- path file generated/uploaded
booking_pass_data     json nullable          -- field bebas: no_reservation, custom fields
total_amount          decimal(15,2) default 0
notes                 text nullable
latitude              decimal(10,7) nullable
longitude             decimal(10,7) nullable
location_name         varchar(255) nullable  -- reverse geocode result
is_danger_zone        boolean default false
room_key_photo        varchar(255) nullable  -- khusus self-service
partner_name_input    varchar(255) nullable  -- free text, self-service
customer_origin       enum(HOTEL, TRAVEL_AGENT, WALK_IN, ONLINE_AD, OTHER) nullable
customer_origin_detail text nullable         -- nama hotel/agent
is_spot_check         boolean default false  -- random flag verifikasi manual
fraud_score_snapshot  int default 0          -- skor fraud partner saat reservasi dibuat
ip_address            varchar(45) nullable
user_agent            text nullable
device_fingerprint    varchar(255) nullable
qr_token              varchar(100) nullable  -- link ke daily_qr_codes
created_by            bigint FK nullable     -- null jika partner/self-service
created_at, updated_at
```

### Tabel `reservation_items`
```
id                    bigint PK
reservation_id        bigint FK CASCADE
product_id            bigint FK
product_name          varchar(255)          -- snapshot nama produk
qty                   int
price_per_pax         decimal(15,2)
amount                decimal(15,2)
sort_order            int default 0
```

### Tabel `reservation_payments`
```
id                    bigint PK
reservation_id        bigint FK CASCADE
payment_method        enum(TRANSFER_GROSS, TRANSFER_NETT, ON_THE_SPOT)
payment_channel       enum(CASH, DEBIT, CREDIT) nullable
gross_amount          decimal(15,2)
nett_amount           decimal(15,2)
commission_amount     decimal(15,2)
commission_rate       decimal(5,2)
is_commission_eligible boolean default false
payment_status        enum(PENDING, PAID, VERIFIED)
proof_file            varchar(255) nullable
is_commission_held    boolean default false
commission_hold_reason text nullable
commission_released_by bigint FK nullable
commission_released_at datetime nullable
verified_by           bigint FK nullable
verified_at           datetime nullable
notes                 text nullable
created_at, updated_at
```

### Perubahan Tabel `partners` (existing)
```
+ reservation_token            varchar(64) UNIQUE nullable
+ reservation_token_expires_at datetime nullable
+ known_devices                json nullable        -- list device fingerprint
+ max_devices                  int default 3
+ fraud_score                  int default 0
+ reservation_suspended        boolean default false
+ reservation_suspended_reason text nullable
```

### Settings Keys Baru (via Settings model existing)
```
reservation_max_days_before       = 30
reservation_min_days_before       = 0
reservation_max_per_day_per_partner = 20
reservation_max_per_hour_per_partner = 5
danger_zone_latitude              = -8.7908
danger_zone_longitude             = 115.1553
danger_zone_radius_meters         = 500
qr_self_service_enabled           = true
default_booking_pass_template     = default
spot_check_percentage             = 10
```

---

## Model & Relationships

```
Reservation
  belongsTo(Partner)              -- nullable
  hasMany(ReservationItem)
  hasOne(ReservationPayment)
  hasMany(ReservationAnomaly)     -- see FEATURE-FRAUD-DETECTION
  hasMany(Invoice)                -- via source_type/source_id
  belongsTo(User, 'created_by')  -- nullable
  belongsTo(BookingPassTemplate)  -- nullable

ReservationItem
  belongsTo(Reservation)
  belongsTo(Product)

ReservationPayment
  belongsTo(Reservation)
  belongsTo(User, 'verified_by')
  belongsTo(User, 'commission_released_by')
```

---

## Alur Bisnis

### Flow 1: Partner Self-Service (Token Link)
```
1. ADMIN generate token untuk partner (Partner detail page)
2. Partner buka: /reserve/{partner_token}
3. Sistem validasi token + expiry + device count
4. Form: guest name, country, pilih produk, tanggal, qty, payment method
5. Browser capture GPS (izin user)
6. Submit -> validasi:
   - Token valid & not expired
   - Device count <= max_devices
   - visit_date dalam range min/max days
   - Partner not suspended
7. Cek danger zone (Haversine distance vs TSB koordinat)
8. Generate RES-YYYYMMDD-XXXX
9. AnomalyDetectionService::check() -> flag jika match rules
10. Auto-select booking pass template (custom per partner / default)
11. Generate booking pass PDF
12. Status: CONFIRMED
13. Partner bisa lihat history via link yang sama
```

### Flow 2: Tim Internal Input
```
1. Tim login -> Menu Reservasi -> Create
2. Pilih partner -> Isi guest, produk, tanggal, qty, payment method
3. GPS di-capture dari browser tim
4. reservation_type = INTERNAL
5. Tidak ada danger zone check (trusted)
6. Generate booking pass -> CONFIRMED
7. Bisa langsung link ke invoice (optional)
```

### Flow 3: Self-Service Room Key (QR Harian)
```
1. ADMIN/SALES generate QR harian -> tempel di area ticketing
2. Customer scan QR -> buka form publik (tanpa login)
3. Input: nama, negara, nama partner (free text), upload foto kunci kamar
4. GPS di-capture
5. reservation_type = SELF_SERVICE
6. Auto booking pass -> tampil di layar
7. Data sync ke DSI export
```

---

## Role & Access Control

| Aksi | ADMIN | FINANCE | SALES | VIEWER | Partner (token) | Public (QR) |
|---|---|---|---|---|---|---|
| Create reservasi internal | Y | Y | Y | - | - | - |
| Create reservasi via token | - | - | - | - | Y | - |
| Create self-service (QR) | - | - | - | - | - | Y |
| View reservasi list | Y | Y | Y | Y | Own only | - |
| Cancel reservasi | Y | Y | - | - | - | - |
| Generate partner token | Y | - | - | - | - | - |
| Manage booking pass template | Y | - | - | - | - | - |
| Verify payment | Y | Y | - | - | - | - |
| Generate daily QR | Y | - | Y | - | - | - |
| Manage reservation settings | Y | - | - | - | - | - |

---

## Device Binding (Security)

```
Flow:
1. Partner buka token link pertama kali
2. Sistem generate device_fingerprint (hash: user_agent + screen + timezone)
3. Simpan di partners.known_devices (JSON array)
4. Device ke-2 buka link -> cek count -> boleh (max 3)
5. Device ke-4 -> BLOCK + notifikasi ADMIN
6. ADMIN bisa reset known_devices dari Partner detail page
```

---

## Integrasi dengan Sistem Existing

| Komponen Existing | Integrasi |
|---|---|
| `Invoice` model | `source_type = 'App\Models\Reservation'`, `source_id = reservation.id` |
| `Product` model | ReservationItem -> belongsTo(Product), pakai pricing existing |
| `Partner` model | Tambah fields token + fraud, relationship hasMany(Reservation) |
| `Settings` model | Tambah keys reservasi baru |
| `AuditLog` model | Auto-audit semua CRUD reservasi |
| `ReportController` | Tambah kolom reservasi di export CSV/Excel |
| `Dashboard` | Tambah widget stats reservasi |
| DSI Export | Tambah data reservasi di export transaksi |

---

## Implementasi Step-by-Step

### Phase 10a: Core Reservation (Step 1-8)
```
Agent: Backend Architect
File: agency-agents/engineering/engineering-backend-architect.md
Reason: Schema design, model relationships, API architecture
```
1. Migration `2026_05_13_100001_create_reservations_table`
2. Migration `2026_05_13_100002_create_reservation_items_table`
3. Migration `2026_05_13_100003_create_reservation_payments_table`
4. Migration `2026_05_13_100004_add_reservation_fields_to_partners_table`
5. Migration `2026_05_13_100005_seed_reservation_settings`
6. Model `Reservation` + relationships + `generateReservationNo()`
7. Model `ReservationItem` + `ReservationPayment`
8. `ReservationController` CRUD (internal, authenticated)
9. Views: `reservations/index`, `create`, `show`

### Phase 10b: Partner Portal (Step 10-14)
```
Agent: Frontend Developer
File: agency-agents/engineering/engineering-frontend-developer.md
Reason: Public-facing form, responsive mobile-first, accessible UX
```
```
Agent: UX Architect
File: agency-agents/design/design-ux-architect.md
Reason: CSS system, form patterns, touch targets, accessibility
```
10. `PartnerReservationController` (public, token-based)
11. Partner token generate/reset di `PartnerController`
12. Middleware `ValidateReservationToken`
13. Views: `partner-reserve/form`, `history`, `booking-pass`
14. Device binding logic + rate limiting

### Phase 10c: Booking Pass System (Step 15-19)
> Lihat: [[FEATURE-BOOKING-PASS]]

### Phase 10d: Self-Service QR (Step 20-24)
```
Agent: Frontend Developer
File: agency-agents/engineering/engineering-frontend-developer.md
Reason: Public form, camera capture, QR rendering, mobile UX
```
20. Migration `create_daily_qr_codes_table`
21. Model `DailyQrCode`
22. `SelfServiceController` (public)
23. Views: `self-service/scan`, `form`, `booking-pass`
24. QR generation route (admin) + daily reset logic

### Phase 10e: Geolocation & Danger Zone (Step 25-27)
```
Agent: Backend Architect
File: agency-agents/engineering/engineering-backend-architect.md
Reason: Haversine formula, coordinate calculation, spatial logic
```
25. `DangerZoneService` — Haversine distance + radius check
26. JavaScript Geolocation API integration di semua form reservasi
27. Auto-flag `is_danger_zone` + `location_name` reverse geocode

### Phase 10f: Anomaly & Fraud Prevention (Step 28-33)
> Lihat: [[FEATURE-FRAUD-DETECTION]]

### Phase 10g: Integration & Reports (Step 34-37)
```
Agent: Senior Developer
File: agency-agents/engineering/engineering-senior-developer.md
Reason: Dashboard widget, export enhancement, sidebar update
```
34. Update `layouts/app.blade.php` — sidebar menu Reservasi
35. Update `dashboard/index.blade.php` — reservation stats widget
36. Update `ReportController` — tambah kolom reservasi di export
37. Update `partners/show.blade.php` — tab reservasi partner

---

## UI/UX Requirements

Mengacu pada `ui-ux-pro-max-skill`:

### Accessibility (CRITICAL)
- Color contrast minimum 4.5:1 (WCAG AA)
- Touch target 44x44pt minimum (semua button, input)
- Keyboard navigation full support (tab order = visual order)
- ARIA labels pada icon-only buttons
- Form labels selalu visible (bukan placeholder-only)
- Error message = sebab + cara fix

### Layout & Responsive
- Mobile-first -> scale up desktop
- Breakpoints: 375 / 768 / 1024 / 1440
- Body text minimum 16px
- 8pt spacing grid
- Container max-width konsisten

### Forms & Feedback
- Inline validation on blur (bukan per-keystroke)
- Required fields: asterisk (*)
- Submit: disable button + spinner saat loading
- Toast auto-dismiss 3-5 detik
- Confirmation dialog sebelum cancel reservation
- Empty state: pesan helpful + action button

### Self-Service (Public Form)
- Input height >= 44px
- Camera upload: `accept="image/*" capture="environment"`
- Progress indicator multi-step
- Success state jelas -> langsung tampil booking pass
- Works offline-first (show error gracefully jika no connection)

---

## Migration Safety

Semua migration wajib:
```php
if (!Schema::hasTable('reservations')) {
    Schema::create('reservations', function (Blueprint $table) {
        // ...
    });
}
```
Tidak boleh `migrate:fresh`. Data existing harus preserved.
