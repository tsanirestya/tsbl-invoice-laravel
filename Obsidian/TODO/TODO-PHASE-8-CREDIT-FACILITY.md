---
phase: 8
title: Credit Facility
status: IN_PROGRESS
created: 2026-05-07
completed:
---

# TODO — Phase 8: Credit Facility

→ Kembali ke: [[MASTER-PLAN]]
→ Planning detail: [[FEATURE-CREDIT-FACILITY]]

---

## Ringkasan

Membangun sistem manajemen kredit per partner:
- Credit class (tier kredit) — admin bisa atur
- Credit limit per partner (sudah ada di DB, diperkuat)
- Credit usage engine (kalkulasi outstanding)
- Dashboard widgets kredit
- Validasi invoice saat melebihi limit
- Panel kredit di halaman partner
- Laporan credit aging (bucket bisa diubah via Settings)

---

## Checklist

---

### Step 1 — Database & Migration

- [x] Buat migration `create_credit_classes_table` (dengan `hasTable()` guard)
  - Kolom: `id`, `name`, `color`, `min_limit`, `max_limit` (nullable), `description`, `sort_order`, `timestamps`
- [x] Buat migration `add_credit_class_id_to_partners_table` (dengan `hasColumn()` guard)
  - Tambah FK `credit_class_id` → `credit_classes.id`, `onDelete('set null')`, nullable
- [x] Buat seeder `CreditClassSeeder` — 4 default class:

  | Class | Color | Min Limit | Max Limit |
  |---|---|---|---|
  | Bronze | secondary | 0 | 24.999.999 |
  | Silver | success | 25.000.000 | 99.999.999 |
  | Gold | warning | 100.000.000 | 499.999.999 |
  | Platinum | primary | 500.000.000 | null |

- [x] Tambah settings keys via seeder/migration:
  - `credit_aging_bucket_1` = `30`
  - `credit_aging_bucket_2` = `60`
  - `credit_aging_bucket_3` = `90`
  - `credit_aging_bucket_4` = `120`
  - `credit_warning_threshold` = `80`

---

### Step 2 — Model

- [x] Buat model `CreditClass`
  - Fillable: `name`, `color`, `min_limit`, `max_limit`, `description`, `sort_order`
  - Relasi: `hasMany(Partner::class)`
  - Cast: `min_limit`, `max_limit` → `decimal:2`
  - Static method: `autoAssign(float $limitCredit): ?CreditClass` — return class yang cocok berdasarkan range limit
- [x] Update model `Partner`
  - Tambah `credit_class_id` ke `$fillable`
  - Relasi: `belongsTo(CreditClass::class)`
  - Tambah method `creditUsed(): float` — SUM `grand_total` invoice UNPAID + PARTIAL + OVERDUE
  - Tambah method `creditAvailable(): float` — `limit_credit` - `creditUsed()`
  - Tambah method `creditUtilizationPercent(): float` — (used / limit) × 100, return 0 jika limit = 0
  - Tambah method `creditStatus(): string` — `NORMAL` | `WARNING` | `OVER_LIMIT` (berdasarkan setting `credit_warning_threshold`)
  - Tambah method `creditInfo(): array` — return semua nilai di atas + formatted string (untuk view & AJAX)
  - Tambah scope `scopeCreditPartners($query)` — filter partner yang punya `limit_credit > 0`
  - Tambah scope `scopeOverCreditLimit($query)` — filter partner dengan `creditStatus() = OVER_LIMIT`

---

### Step 3 — Credit Class Admin CRUD ✅ SELESAI 2026-05-08

- [x] Buat `CreditClassController` (ADMIN only)
  - `index()` — list semua class, sortable
  - `create()` + `store()` — form tambah class baru
  - `edit($id)` + `update($id)` — edit class
  - `destroy($id)` — hapus class (cek dulu: ada partner? block jika ya, tampilkan jumlah partner)
- [x] Buat views:
  - [x] `credit-classes/index.blade.php` — tabel + badge color preview
  - [x] `credit-classes/create.blade.php` — form
  - [x] `credit-classes/edit.blade.php` — form (sama dengan create, pre-filled)
  - [x] `credit-classes/_form.blade.php` — shared partial dengan live badge preview JS
- [x] Tambah routes (ADMIN only) via `Route::resource('credit-classes', ...)`
- [x] Tambah link ke sidebar (menu ADMIN) — icon `bi-award-fill`

---

### Step 4 — Auto-Assign Credit Class ke Partner ✅ SELESAI 2026-05-08

- [x] Update `PartnerController::store()` — setelah simpan, jalankan `CreditClass::autoAssign($partner->limit_credit)` → set `credit_class_id`
- [x] Update `PartnerController::update()` — sama, re-assign setiap kali `limit_credit` berubah
- [x] Tambah field `credit_class_id` di form partner (dropdown manual override)
  - Label: "Credit Class (auto-assign berdasarkan limit, bisa override)"
  - Default: kosong (akan diisi otomatis saat simpan)
- [x] Tampilkan badge credit class di `partners/index.blade.php` (kolom baru)
- [x] Tampilkan badge + credit info di `partners/show.blade.php`

---

### Step 5 — Partner Credit Panel (Show Page) ✅ SELESAI 2026-05-08

Di `partners/show.blade.php`, tambah section "Credit Information":

- [x] Card: Credit Limit | Credit Used | Credit Available
- [x] Progress bar utilization: hijau (<threshold), kuning (≥threshold), merah (>100%)
- [x] Badge status: NORMAL / WARNING / OVER LIMIT
- [x] Badge credit class (dengan warna class)
- [x] Tabel outstanding invoices (UNPAID + PARTIAL + OVERDUE) — no, tanggal, grand_total, due_date, hari jatuh tempo
- [x] AJAX endpoint: `GET /api/partners/{id}/credit-info` → return `creditInfo()` array (untuk invoice form)

---

### Step 6 — Invoice Credit Validation ✅ SELESAI 2026-05-08

Saat buat/edit invoice untuk partner yang punya `limit_credit > 0`:

- [x] Update `InvoiceController::store()` — validasi credit sebelum simpan:
  - Hitung: `credit_after = creditUsed() + grand_total_invoice_baru`
  - Jika `credit_after / limit_credit * 100 ≥ threshold` → flash warning, lanjut simpan
  - Jika `credit_after > limit_credit` → require field `credit_override_reason` (tidak kosong)
- [x] Update `InvoiceController::update()` — validasi sama (exclude grand_total invoice ini sendiri dari hitungan)
- [x] Tambah field `credit_override_reason` ke tabel `invoices` (migration, nullable, string)
- [x] Update Invoice model — tambah `credit_override_reason` ke `$fillable`
- [x] Tambah `warning` flash support ke layout
- [x] Di invoice form:
  - [x] Panel "Credit Status Partner" tampil jika partner punya credit limit (via AJAX saat partner dipilih)
  - [x] Tampilkan: limit, used (saat ini), proyeksi setelah invoice ini
  - [x] Progress bar utilization: hijau/kuning/merah
  - [x] Warning banner kuning jika utilization > threshold setelah invoice ini ditambah
  - [x] Error banner merah + textarea override reason jika over 100%
  - [x] Override reason wajib diisi sebelum submit (JS validation + server-side)

---

### Step 7 — Dashboard Widgets ✅ SELESAI 2026-05-08

Tambah 4 widget baru di dashboard (setelah widget deposit existing):

- [x] **Widget: Total Credit Outstanding**
  - SUM `grand_total` semua invoice UNPAID + PARTIAL + OVERDUE milik credit partner
  - Tampil sebagai stat card (rupiah)
- [x] **Widget: Partner Over Limit**
  - Hitung jumlah partner dengan `creditStatus() = OVER_LIMIT`
  - Tampil count + list dropdown (klik expand → lihat nama partner)
  - Warna merah jika ada ≥1
- [x] **Widget: Credit Breakdown per Class**
  - Bar atau tabel: tiap class → total outstanding-nya
  - Tampil dengan badge warna class
- [x] **Widget: Top 5 Highest Outstanding**
  - Tabel: nama partner, class, outstanding, limit, utilization %
  - Sort by outstanding DESC

---

### Step 8 — Credit Report & Aging ✅ SELESAI 2026-05-08

- [x] Tambah tab "Kredit" di halaman `/reports`
- [x] **Tabel Credit Summary per Partner:**
  - Kolom: nama partner, class, limit, used, available, utilization %, status
  - Filter: by class, by status (NORMAL/WARNING/OVER_LIMIT)
  - Sort by utilization DESC (default)
- [x] **Tabel Credit Aging:**
  - Kolom partner (bisa collapse per partner)
  - Bucket: Current | 1–N1 hari | N1+1–N2 | N2+1–N3 | N3+1–N4 | >N4 hari
  - Nilai bucket diambil dari Settings (`credit_aging_bucket_1..4`)
  - Dihitung dari: `today - due_date` invoice yang belum lunas
  - Total per bucket, total per partner, grand total
- [x] **Export:**
  - [x] Export CSV — credit summary + aging dalam 2 sheet/section
  - [x] Export PDF (DomPDF A4 landscape)

---

### Step 9 — Settings Integration ✅ SELESAI 2026-05-08

Di halaman `/settings` (tab baru atau section baru "Kredit"):

- [x] Field: **Credit Warning Threshold** (`credit_warning_threshold`)
  - Input: number, suffix "%"
  - Default: 80
  - Validasi: 1–100
- [x] Field: **Aging Bucket 1** (`credit_aging_bucket_1`)
  - Input: number, suffix "hari"
  - Default: 30
- [x] Field: **Aging Bucket 2** (`credit_aging_bucket_2`) — default: 60
- [x] Field: **Aging Bucket 3** (`credit_aging_bucket_3`) — default: 90
- [x] Field: **Aging Bucket 4** (`credit_aging_bucket_4`) — default: 120
- [x] Validasi: bucket_1 < bucket_2 < bucket_3 < bucket_4 (server-side + JS live preview)
- [x] Label helper: "Aging akan tampil sebagai: Current | 1–30 | 31–60 | 61–90 | 91–120 | >120 hari"

---

### Step 10 — Testing Manual

**Credit Classes:**
- [x] Buat class Platinum, Gold, Silver, Bronze lewat UI
- [x] Hapus class yang masih punya partner → harus diblok
- [x] Edit warna class → badge berubah di semua tampilan

**Partner Credit:**
- [x] Set limit_credit partner → auto-assign class sesuai range
- [x] Override class manual → tetap tersimpan (tidak di-overwrite)
- [x] Partner show: credit panel tampil dengan data benar
- [x] Partner list: kolom credit class muncul dengan badge

**Invoice Validation:**
- [x] Pilih partner credit di form invoice → panel credit status muncul via AJAX
- [x] Isi grand total sampai 85% dari limit → warning kuning muncul, bisa submit tanpa reason
- [x] Isi grand total sampai 105% dari limit → error merah muncul, submit gagal tanpa override reason
- [x] Isi override reason → bisa submit, tersimpan di `invoices.credit_override_reason`

**Dashboard:**
- [x] 4 widget muncul dan akurat
- [x] Partner over limit tampil count yang benar
- [x] Widget breakdown per class sesuai data

**Report & Aging:**
- [x] Tab Kredit muncul di /reports
- [x] Ubah aging buckets di Settings → tabel aging langsung pakai nilai baru
- [x] Export CSV akurat
- [x] Export PDF layout rapi

---

## Estimasi Kompleksitas

| Task | Effort |
|---|---|
| Step 1 — Migration + Seeder | Kecil |
| Step 2 — Model update | Kecil–Sedang |
| Step 3 — Credit Class CRUD | Sedang |
| Step 4 — Auto-assign + Partner UI update | Sedang |
| Step 5 — Partner Credit Panel | Sedang |
| Step 6 — Invoice Validation + AJAX | Sedang–Besar |
| Step 7 — Dashboard Widgets | Sedang |
| Step 8 — Credit Report + Aging | Besar |
| Step 9 — Settings Integration | Kecil |
| Step 10 — Testing | Sedang |

**Total estimasi:** 3–4 sesi kerja
