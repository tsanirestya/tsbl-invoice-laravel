# TODO Phase 7 — Excel Transaction Import & Validation System

**Date Created:** 2026-05-06
**Branch:** `feat/phase-7-excel-import`
**Status:** Planning

---

## Overview

System upload Excel transaksi → validasi → anomaly detection → commission calc → auto-fill invoice.

**Scope ini BUKAN rebuild** — memanfaatkan:
- Products table (sudah ada `publish_rate`, `nett_price`, `komisi`) ✅
- InvoiceLog (extend untuk audit import) ✅
- Invoice CRUD (tambah mode "from import", tidak dirombak) ✅
- Reports (tambah tab Import Summary, tidak dirombak) ✅
- Dashboard (tambah widget anomaly, tidak dirombak) ✅

---

## Dependency Baru

```bash
composer require maatwebsite/excel          # Excel read/write
composer require php-levenshtein/levenshtein  # atau pakai PHP native similar_text()
```

---

## Phase 7 Task Breakdown

---

### 7.1 — Database Migrations

- [ ] `transaction_imports` — header record per upload session
  ```
  id, uuid, filename, uploaded_by, uploaded_at,
  status (pending/processing/reviewed/done),
  total_rows, valid_rows, anomaly_rows, rejected_rows,
  processed_at, reviewed_by, reviewed_at
  ```

- [ ] `transaction_import_rows` — satu baris per row Excel
  ```
  id, import_id (FK), row_index, uuid_key,
  transaction_no, date, ticket_type, ticket_name,
  transaction_type, time, cashier, payment_method,
  payment_details, unit_price, qty, total_amount,
  remark, country, nationality,
  matched_product_id (FK nullable), match_method (exact/alias/fuzzy/none),
  publish_rate, nett_price, komisi_rate, komisi_amount,
  status (valid/anomaly/rejected),
  is_approved, approved_by, approved_at, override_reason,
  created_at
  ```

- [ ] `import_anomalies` — per baris yang anomaly, bisa 1 baris → banyak anomaly
  ```
  id, import_row_id (FK), anomaly_type (enum),
  detail, severity (warning/error),
  created_at
  ```
  Anomaly types:
  - `CATEGORY_MISMATCH`
  - `REVERSE_MISMATCH`
  - `PRODUCT_NOT_FOUND`
  - `PRICE_MISMATCH`
  - `SUSPICIOUS_PRICING`
  - `FUZZY_CANDIDATE`

- [ ] `import_rejections` — baris yang dibuang (type/prefix tidak valid)
  ```
  id, import_id (FK), row_index, raw_data (JSON),
  rejection_reason (enum: INVALID_TICKET_TYPE / NAME_PREFIX_MISMATCH),
  created_at
  ```

- [ ] `product_aliases` — mapping alias ticket_name → product_name
  ```
  id, alias_name, product_id (FK), created_by, created_at
  ```

- [ ] Semua migration pakai `Schema::hasTable()` / `Schema::hasColumn()` guards

---

### 7.2 — Models

- [ ] `TransactionImport` — belongsTo User, hasMany Rows, hasMany Rejections
- [ ] `TransactionImportRow` — belongsTo Import, belongsTo Product (nullable), hasMany Anomalies
- [ ] `ImportAnomaly` — belongsTo ImportRow
- [ ] `ImportRejection` — belongsTo Import
- [ ] `ProductAlias` — belongsTo Product

---

### 7.3 — Import Pipeline Service

File: `app/Services/ImportPipelineService.php`

Pipeline steps (dijalankan sequentially):

**Step 1 — Parse & Normalize**
- [ ] Baca file Excel/CSV via Maatwebsite
- [ ] Trim semua string values
- [ ] Uppercase: `ticket_type`, `ticket_name`
- [ ] Standardize date format → `Y-m-d`
- [ ] Standardize time format → `H:i:s`
- [ ] Simpan semua row ke `transaction_import_rows` dengan status `pending`

**Step 2 — Filter & Reject**
- [ ] Cek `ticket_type` IN [`HTL`, `TRD`, `TVL`]
- [ ] Cek 3 char pertama `ticket_name` IN [`HTL`, `TRD`, `TVL`]
- [ ] Baris gagal → pindah ke `import_rejections` dengan reason
- [ ] Row yang lolos → lanjut ke step berikutnya

**Step 3 — Product Matching (3 layer)**
- [ ] Layer 1 — Exact Match: `ticket_name == product_name` (case-insensitive) → `match_method = exact`
- [ ] Layer 2 — Alias Match: cek `product_aliases.alias_name` → `match_method = alias`
- [ ] Layer 3 — Fuzzy Match via `similar_text()` threshold ≥ 80%:
  - JANGAN auto-accept
  - Set `match_method = fuzzy`
  - Buat anomaly `FUZZY_CANDIDATE`
- [ ] Tidak ada match → anomaly `PRODUCT_NOT_FOUND`

**Step 4 — Anomaly Detection**
- [ ] A. `CATEGORY_MISMATCH`: ticket_type HTL/TRD/TVL BUT ticket_name prefix ≠ match
- [ ] B. `REVERSE_MISMATCH`: ticket_name prefix HTL/TRD/TVL BUT ticket_type berbeda
- [ ] C. `PRODUCT_NOT_FOUND`: tidak ada exact/alias match
- [ ] D. `PRICE_MISMATCH`: unit_price ≠ publish_rate AND unit_price ≠ nett_price
- [ ] E. `SUSPICIOUS_PRICING`: unit_price < nett_price
- [ ] F. `FUZZY_CANDIDATE`: similar_text match tanpa exact

**Step 5 — Commission Calculation**
- [ ] `unit_price == publish_rate` → komisi = `product.komisi * qty`
- [ ] `unit_price == nett_price` → komisi = 0
- [ ] Lainnya → set anomaly (PRICE_MISMATCH), komisi = null pending approval
- [ ] Simpan `publish_rate`, `nett_price`, `komisi_rate`, `komisi_amount` ke row

---

### 7.4 — Controllers

- [ ] `TransactionImportController`
  - `index()` — list semua import sessions
  - `create()` — upload form
  - `store()` — terima file, jalankan pipeline, redirect ke review
  - `show()` — review page: valid/anomaly/rejected tabs
  - `destroy()` — hapus import session (jika belum di-approve)

- [ ] `ImportReviewController`
  - `approveRows(Request)` — bulk approve selected rows
  - `rejectRows(Request)` — bulk reject dari anomaly
  - `overrideRow(Request)` — approve single anomaly + simpan override_reason
  - `finalizeImport(Request)` — mark import sebagai `done`

- [ ] `ProductAliasController`
  - CRUD alias mapping
  - Linked dari Products module (tambah tab Aliases di product edit)

---

### 7.5 — Views

**Upload:**
- [ ] `resources/views/imports/index.blade.php` — list import sessions + status badge
- [ ] `resources/views/imports/create.blade.php`
  - Drag & drop upload zone (Bootstrap + JS)
  - Accepted: `.xlsx`, `.csv`
  - Preview nama file sebelum submit

**Review:**
- [ ] `resources/views/imports/show.blade.php`
  - Tab 1: **Valid Rows** (hijau) — table + total komisi
  - Tab 2: **Anomaly Rows** (merah) — per anomaly category filter
  - Tab 3: **Rejected** (abu-abu) — reason label
  - Summary bar: total / valid / anomaly / rejected counts
  - Bulk action: Select All Valid, Select All by Anomaly Type, Manual select
  - Per anomaly row: checkbox + category badge + override_reason input
  - "Finalize Import" button (disabled jika ada anomaly belum di-handle)

**Color coding:**
- Green `table-success` = valid
- Red `table-danger` = anomaly
- Grey `text-muted` = rejected

**Product Aliases:**
- [ ] `resources/views/products/aliases.blade.php` — sub-page di product detail

---

### 7.6 — Invoice Integration (EXTEND, bukan rebuild)

**Existing invoice form** — tambah mode "from import":

- [ ] Route baru: `GET /invoices/create?from_import={import_id}&transaction_no={no}`
- [ ] `InvoiceController::create()` — deteksi param `from_import`:
  - Query `transaction_import_rows` by transaction_no + import_id
  - Auto-fill: partner (dari cashier/remark mapping, atau manual), line items, subtotal, commission
  - Tetap editable sebelum confirm
- [ ] View `invoices/_form.blade.php` — tambah panel "Import Source" jika from_import mode
  - Tampilkan transaction_no DSI
  - List line items dari import (editable)
  - Komisi auto-calculated, bisa di-override dengan reason

---

### 7.7 — Dashboard Widget (EXTEND)

Di `DashboardController` dan `dashboard/index.blade.php`:

- [ ] Widget: **Import Anomalies** — count anomaly pending review
- [ ] Widget: **Pending Imports** — import sessions belum di-finalize
- [ ] Alert banner jika anomaly_rate > 20% dari import terbaru

---

### 7.8 — Reports Extension (EXTEND)

Di `ReportController` dan `reports/index.blade.php`:

- [ ] Tab baru: **Import Summary**
  - Revenue per Ticket Type (HTL/TRD/TVL)
  - Total komisi dari import
  - Local vs Foreign (dari `nationality` / `country` column)
  - Top products by transaction count
- [ ] Export anomaly report → Excel via Maatwebsite

---

### 7.9 — Audit & Fraud Flags (EXTEND InvoiceLog)

- [ ] Log setiap anomaly approval ke `invoice_logs` (atau tabel baru `import_audit_logs`)
  - `action`, `user_id`, `import_row_id`, `override_reason`, `created_at`
- [ ] Flag otomatis di dashboard jika:
  - Partner/cashier terlalu banyak transaksi `unit_price == nett_price` (komisi = 0)
  - Unit price di bawah nett_price (SUSPICIOUS_PRICING count tinggi)

---

### 7.10 — Notifications

- [ ] Flash message setelah upload: "X rows valid, Y anomalies, Z rejected"
- [ ] Anomaly breakdown per category di review page header
- [ ] Export Anomaly Report button → Excel download

---

## DB Impact Summary

| Table | Action |
|---|---|
| `products` | REUSE — sudah ada publish_rate, nett_price, komisi |
| `invoice_logs` | EXTEND — tambah import-related actions |
| `invoices` | EXTEND — tambah nullable `import_id` FK |
| `transaction_imports` | NEW |
| `transaction_import_rows` | NEW |
| `import_anomalies` | NEW |
| `import_rejections` | NEW |
| `product_aliases` | NEW |

---

## Routes Summary

```
GET    /imports                    imports.index
GET    /imports/create             imports.create
POST   /imports                    imports.store
GET    /imports/{id}               imports.show
DELETE /imports/{id}               imports.destroy

POST   /imports/{id}/approve-rows  import-review.approve
POST   /imports/{id}/reject-rows   import-review.reject
POST   /imports/{id}/override-row  import-review.override
POST   /imports/{id}/finalize      import-review.finalize

GET    /products/{id}/aliases      product-aliases.index
POST   /products/{id}/aliases      product-aliases.store
DELETE /products/{id}/aliases/{aid} product-aliases.destroy
```

---

## Agent untuk Phase ini

```
Agent: Backend Architect
File: ../agency-agents/engineering/backend-architect.md
Reason: Pipeline processing + DB schema + service layer design

Agent: Frontend Developer
File: ../agency-agents/engineering/frontend-developer.md
Reason: Drag-drop upload UI + color-coded review table + bulk checklist
```

---

## Progress Tracker

- [ ] 7.1 Migrations (5 tables)
- [ ] 7.2 Models (5 models)
- [ ] 7.3 ImportPipelineService
- [ ] 7.4 Controllers (3 controllers)
- [ ] 7.5 Views (upload + review + aliases)
- [ ] 7.6 Invoice integration extension
- [ ] 7.7 Dashboard widget extension
- [ ] 7.8 Reports tab extension
- [ ] 7.9 Audit log extension
- [ ] 7.10 Notifications

---

## Additional — Products Category Column

**Date:** 2026-05-11
**GitHub:** tsanirestya/tsbl-invoice-laravel#17

### Spec
- Kolom baru `category` di tabel `products`
- Nilai = 3 karakter kiri dari `dsi_code`
- Jika `dsi_code = '0'` → `category = '0'`
- Jika `dsi_code = NULL` → `category = NULL` (isi manual)
- **Existing products**: backfill otomatis via migration (`LEFT(dsi_code, 3)`)
- **Import pipeline**: auto-update category saat row valid + product matched
- **Edit form**: field manual agar admin bisa override

### Tasks
- [x] Migration `add_category_to_products_table` + backfill query — **DONE 2026-05-11**
- [x] `Product.$fillable` + tambah `category` — **DONE 2026-05-11**
- [x] `ImportPipelineService`: set category pada valid row match — **DONE 2026-05-11**
- [x] `products/_form.blade.php`: input `category` (manual override) — **DONE 2026-05-11**
- [x] `ProductController`: validasi + store/update `category`, filter by category — **DONE 2026-05-11**
- [x] `products/index.blade.php`: tampilkan DSI Code, Category, Publish Rate, Komisi, Nett Price, % Komisi, Payment Mode — **DONE 2026-05-11**

### Notes
- % Komisi = `komisi / publish_rate × 100`
- Category badge color-coded: HTL biru, TRD kuning, TVL hijau
- Search diperluas: product_name + dsi_code
- Filter dropdown Kategori auto-populate dari data existing
- Existing data di-backfill otomatis via migration `LEFT(dsi_code, 3)`

---

## Notes

- JANGAN auto-correct anomaly — semua non-exact match WAJIB user confirmation
- Fuzzy threshold: `similar_text() >= 80` (configurable via Setting nanti)
- `transaction_import_rows.uuid_key` = UUID v4, untuk keunikan row antar import
- Import yang sudah `done` tidak bisa diedit, hanya bisa dilihat
- `migrate:fresh` DILARANG — pakai hasTable guards di semua migration
