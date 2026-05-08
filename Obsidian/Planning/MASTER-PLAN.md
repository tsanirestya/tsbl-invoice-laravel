# TSBL Invoice Generator — Master Plan

## Project Overview
Mobile-first finance operational web system built di Laravel 11.

**Location:** `D:\XAMPP NEW\htdocs\tsbl-invoice-laravel`
**Stack:** Laravel 11 / Blade / Bootstrap 5 / MySQL / DomPDF
**DB:** `tsbl_invoice` (migrated dari CodeIgniter 4 — semua tables pre-exist dengan data)
**App URL:** `http://localhost/tsbl-invoice-laravel/public`

---

## Phase Roadmap

### ✅ Phase 1 — Foundation (SELESAI 2026-05-05)
→ Detail: [[TODO-PHASE-1-FOUNDATION]]
- [x] Project planning & Obsidian structure
- [x] Laravel 11 installation
- [x] .env configuration (MySQL, file session, file cache)
- [x] Laravel migrations (hasTable guards — preserve CI4 data)
- [x] Models: User, Partner, Invoice, InvoiceItem, Payment, Product, Setting, InvoiceLog
- [x] Authentication (login/logout/session/CheckRole middleware)
- [x] Base layout Bootstrap 5 mobile-first (sidebar + bottom nav)
- [x] Dashboard (stat cards + revenue + outstanding + recent invoices)
- [x] DomPDF installed
- [x] Storage symlink
- [x] Fix: login form action pakai `route('login')`

### ✅ Phase 2 — Core Modules (SELESAI 2026-05-05)
→ Summary: [[PHASE-2-SUMMARY]]
- [x] User Management (CRUD + signature upload + activate/deactivate)
- [x] Partner Management (CRUD + doc upload + credit limit + contract reminder)
- [x] Products Module (CRUD + pricing)

### ✅ Phase 3 — Invoice Engine (SELESAI 2026-05-05)
→ Summary: [[PHASE-3-SUMMARY]]
- [x] Invoice Generator (form + auto invoice no + auto due date + auto terbilang)
- [x] Invoice items (dynamic rows + product picker + auto calc)
- [x] Duplicate invoice
- [x] PDF Generation (DomPDF + watermark PAID/UNPAID + digital signature)
- [x] Invoice finalization & permanent PDF storage

### ✅ Phase 4 — Finance Operations (SELESAI 2026-05-05)
→ Summary: [[PHASE-4-SUMMARY]]
- [x] Payment Tracking (proof upload + partial payments)
- [x] Auto-overdue detection (artisan command + web button on-request)
- [x] Payment checklist view (/payments)
- [x] Invoice status auto-update dari payment (recalcStatus)

### ✅ Phase 5 — Settings & Reports (SELESAI 2026-05-05)
→ Summary: [[PHASE-5-SUMMARY]]
- [x] Settings Module (company profile, bank, invoice prefix, due days, T&C, logo upload)
- [x] Reports (outstanding, overdue, paid, revenue, partner summary) with date/status/partner filters
- [x] Export CSV (UTF-8 BOM, Excel-ready) + Export PDF (DomPDF A4 landscape)

### ✅ Phase 6 — Partner Deposit System (SELESAI 2026-05-06)
→ TODO: [[TODO-PHASE-6-PARTNER-DEPOSIT]]
→ Planning: [[FEATURE-PARTNER-DEPOSIT]]
- [x] Migration `partner_deposits` table (type TOPUP/DEDUCTION/ADJUSTMENT, immutable records)
- [x] Setting `deposit_low_threshold` seeded (default Rp 1.000.000)
- [x] Model `PartnerDeposit` + `Partner::depositBalance()` + `Partner::depositInfo()`
- [x] `PartnerDepositController` (index, create/store top-up, adjustment, AJAX balance API)
- [x] Invoice form: AJAX deposit panel (4 skenario: cukup/rendah/kosong/habis) + real-time grand total
- [x] Auto DEDUCTION record saat invoice dibuat/diedit dengan deposit
- [x] Reverse DEDUCTION otomatis jika invoice diedit atau dihapus
- [x] Server-side validation: deposit ≤ min(saldo, subtotal)
- [x] Views: riwayat deposit + top-up form + deposit card di partner detail (badge rendah/habis)
- [x] Dashboard widget: partner deposit rendah/habis
- [x] Settings: field `deposit_low_threshold` bisa diubah ADMIN
- [x] Report tab Deposit (saldo per partner, total topup, total terpakai)

### ✅ Phase 7 — Excel Transaction Import System (SELESAI 2026-05-06)
→ TODO: [[TODO-PHASE-7-EXCEL-IMPORT]]
- [x] Migration: `transaction_imports`, `transaction_import_rows`, `import_anomalies`, `import_rejections`, `product_aliases`
- [x] Models: TransactionImport, TransactionImportRow, ImportAnomaly, ImportRejection, ProductAlias
- [x] ImportPipelineService: parse → normalize → filter → match → anomaly detect → commission calc
- [x] Product matching: 3-layer (exact / alias / fuzzy — fuzzy TIDAK auto-accept)
- [x] Anomaly types: CATEGORY_MISMATCH, REVERSE_MISMATCH, PRODUCT_NOT_FOUND, PRICE_MISMATCH, SUSPICIOUS_PRICING, FUZZY_CANDIDATE
- [x] Review UI: color-coded table (green/red/grey), bulk checklist, override reason
- [x] Invoice integration: from_import mode auto-fill (extend existing)
- [x] Reports extension: tab Import Summary + anomaly export Excel (PhpSpreadsheet)
- [x] Dashboard extension: widget pending imports + anomaly rate alert (>20%)

### ✅ Phase 8 — Credit Facility (SELESAI 2026-05-08)
→ TODO: [[TODO-PHASE-8-CREDIT-FACILITY]]
→ Planning: [[FEATURE-CREDIT-FACILITY]]
- [x] Migration: `credit_classes` table + `credit_class_id` FK di `partners`
- [x] Settings keys: aging buckets (4 bucket editable) + credit warning threshold
- [x] Model `CreditClass` + auto-assign logic berdasarkan `limit_credit`
- [x] Partner model: `creditUsed()`, `creditAvailable()`, `creditUtilizationPercent()`, `creditStatus()`, `creditInfo()`
- [x] Admin CRUD: Credit Class management
- [x] Partner list + show: badge class + credit panel (limit/used/available/bar) + outstanding invoices table
- [x] AJAX endpoint `GET /api/partners/{id}/credit-info`
- [x] Invoice form: AJAX credit panel + warning/error banner + override reason
- [x] Invoice controller: soft-block validation (warning >threshold%, error+reason >100%)
- [x] Dashboard: 4 widget kredit (outstanding, over limit, per class, top 5)
- [x] Report tab Kredit: credit summary + credit aging (bucket editable dari Settings)
- [x] Settings: field warning threshold + 4 aging bucket (validasi urutan)

### 📋 Phase 9 — Batch Credit Payment + Memo Tagihan (PLANNING 2026-05-08)

#### 9a — Batch Credit Payment
→ Planning: [[FEATURE-BATCH-CREDIT-PAYMENT]]
- [ ] Migration: `credit_payments` table + `credit_payment_id` kolom di `payments`
- [ ] Migration: kolom `is_voided`, `voided_at`, `voided_by` di `credit_payments`
- [ ] Model `CreditPayment` + relasi ke Partner, Payment, PartnerDeposit
- [ ] Update model `Payment` + `Partner` (tambah relasi)
- [ ] `CreditPaymentController`: index, create, store (DB transaction), show, destroy (void)
- [ ] AJAX endpoint: `GET /api/partners/{partner}/outstanding-invoices`
- [ ] Views: index, create (FIFO JS + live summary), show (detail + void badge)
- [ ] Nomor batch otomatis format `CP-{YYYYMM}-{seq}`
- [ ] Logic sisa → deposit otomatis (TOPUP ke `partner_deposits`)
- [ ] Void: set is_voided + rollback Payment + recalcStatus + reverse deposit
- [ ] Menu tambah di layout: "Pembayaran Credit"

#### 9b — Memo Pengajuan Pembayaran ✅ SELESAI 2026-05-08
→ Planning: [[FEATURE-PAYMENT-MEMO]]
- [x] Migration: `payment_memos` table + `payment_memo_invoices` pivot (dengan snapshot nilai)
- [x] Model `PaymentMemo` + `PaymentMemoInvoice`
- [x] `PaymentMemoController`: index, create, store, show, pdf, destroy
- [x] AJAX: load outstanding invoices per partner
- [x] Views: index, create (checklist invoice + auto batas bayar +7 hari), show (snapshot vs status kini)
- [x] PDF template DomPDF — "MEMO OUTSTANDING PAYMENT" A4 portrait
- [x] Nomor memo otomatis format `MP-{YYYYMM}-{seq}`
- [x] Dashboard widget: "Partner Perlu Ditagih" (OVERDUE dulu, lalu hampir jatuh tempo)
- [x] Shortcut tombol "Buat Memo" dari halaman partner show
- [x] Menu tambah di layout: "Memo Tagihan"

---

## Architecture Overview
→ Detail: [[LARAVEL-STRUCTURE]]

## Database Schema
→ Detail: [[SCHEMA]]

## UI/UX Design System
→ Detail: [[DESIGN-SYSTEM]]

---

## Architecture Decisions

| Decision | Choice | Reason |
|---|---|---|
| Auth system | Laravel built-in + manual | Simple status-based, no RBAC |
| PDF | DomPDF via barryvdh/laravel-dompdf | Laravel native integration |
| Frontend | Blade + Bootstrap 5 | Familiar, mobile-first |
| File storage | `storage/app/public` | Laravel standard |
| Migration strategy | Has-table guards | DB pre-exists CI4 data |
| Session/Cache | File driver | Avoid MariaDB `session_status` bug |
| PDF storage | Permanent (never regenerate) | Audit trail requirement |

---

## User Roles
| Status | Access |
|---|---|
| ADMIN | Full system — semua module + settings + user management |
| FINANCE | Invoice + payment + reports |
| SALES | Invoice create/view only |
| VIEWER | View only, no create/edit |

---

## Known Issues & Notes
- `php artisan db:show` fail di MariaDB XAMPP ini (performance_schema.session_status tidak ada) — **normal**, DB ops lain jalan fine
- App jalan di subdirectory — semua URL harus pakai `route()` atau `url()` helper, bukan hardcoded `/path`
- Admin credential: `admin@tsbl.com` / `admin123`

---

## Dev Log
- 2026-05-05 — Project initiated. Master plan dibuat. Phase 1 selesai.
- 2026-05-06 — Phase 6 (Partner Deposit) selesai.
- 2026-05-06 — Phase 7 (Excel Import System) selesai. Tables: transaction_imports, transaction_import_rows, import_anomalies, import_rejections, product_aliases. Pipeline: parse→normalize→filter→match→anomaly→commission. PhpSpreadsheet (require-dev sudah ada).
- 2026-05-07 — Phase 8 (Credit Facility) direncanakan.
- 2026-05-08 — Phase 9 (Batch Credit Payment) direncanakan. Keputusan: sisa → deposit, void → rollback penuh, akses = FINANCE. Scope: credit classes, credit engine per partner, dashboard widgets, invoice soft-block validation, credit aging editable via settings.
- 2026-05-08 — Phase 9b (Memo Tagihan) selesai. Tables: payment_memos, payment_memo_invoices. Features: CRUD memo, PDF DomPDF, AJAX invoice loader, dashboard widget "Partner Perlu Ditagih", shortcut di partner show.
