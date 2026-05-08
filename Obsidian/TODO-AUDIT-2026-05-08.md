# TODO — Audit Remediation 2026-05-08

**Sumber:** [[AUDIT-REPORT-2026-05-08]]
**Dibuat:** 2026-05-08
**Total Finding:** 28 (4 CRITICAL, 9 HIGH, 9 MEDIUM, 6 LOW)
**Health Score Awal:** 31 / 100

---

## 🔴 IMMEDIATE — Selesaikan Hari Ini

| # | Finding | Task | File | Status |
|---|---------|------|------|--------|
| 1 | FINDING-001 | Hapus `/setup-production` route (RCE publik) | `routes/web.php` L26-42 | ✅ 2026-05-08 |
| 2 | FINDING-002 | Tambah `role:FINANCE,ADMIN` middleware ke semua financial write routes | `routes/web.php` L57-84 | ✅ 2026-05-08 |
| 3 | FINDING-003 | `PaymentController::store` — tambah `is_finalized` check + max amount cap vs remaining balance | `app/Http/Controllers/PaymentController.php` L56-83 | ✅ 2026-05-08 |
| 4 | FINDING-004 | `PartnerDepositController::adjustment` — tambah amount constraint + gate `role:ADMIN,FINANCE` | `app/Http/Controllers/PartnerDepositController.php` L49-65 | ✅ 2026-05-08 |

---

## 🟠 THIS WEEK — Selesaikan Minggu Ini

| # | Finding | Task | File | Status |
|---|---------|------|------|--------|
| 5 | FINDING-005 | Set `APP_DEBUG=false` + `APP_ENV=production` di `.env` | `.env` | ✅ 2026-05-08 |
| 6 | FINDING-006 | Hapus `$invoice->logs()->delete()` dari `InvoiceController::destroy` — preserve audit trail | `app/Http/Controllers/InvoiceController.php` L496 | ✅ 2026-05-08 |
| 7 | FINDING-007 | Tambah `'deposit'` ke exclusion list di `InvoiceController::duplicate()`; set `$newInvoice->deposit = 0` | `app/Http/Controllers/InvoiceController.php` L367 | ✅ 2026-05-08 |
| 8 | FINDING-008 | Tambah `throttle:5,1` middleware ke login POST route | `routes/web.php` | ✅ 2026-05-08 |
| 9 | FINDING-011 | Block payment pada draft invoice — `if (!$invoice->is_finalized) abort(403)` | `app/Http/Controllers/PaymentController.php` | ✅ 2026-05-08 (covered by F-003 fix) |
| 10 | FINDING-013 | Tambah auth check di `storage-proxy.php` — blokir akses dokumen partner tanpa login | `public/storage-proxy.php` | ✅ 2026-05-08 |
| 11 | FINDING-015 | Set `SESSION_ENCRYPT=true` + kurangi session lifetime ke 30 menit idle | `.env` / `config/session.php` | ✅ 2026-05-08 |

---

## 🟡 THIS MONTH — Selesaikan Bulan Ini

| #   | Finding     | Task                                                                                                                     | File                                                                            | Status       |
| --- | ----------- | ------------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------- | ------------ |
| 12  | FINDING-009 | Implement password reset — artisan command `user:reset-password` atau Laravel forgot-password flow                       | `routes/web.php`, new controller                                                | ✅ 2026-05-08 |
| 13  | FINDING-010 | Tambah `->lockForUpdate()` ke sequence query invoice/deposit/memo/batch — fix race condition                             | `InvoiceController`, `DepositInvoiceController`, `PaymentMemo`, `CreditPayment` | ✅ 2026-05-08 |
| 14  | FINDING-012 | Pindahkan credit limit check ke dalam `DB::transaction` setelah `Partner::lockForUpdate()` — fix TOCTOU                  | `app/Http/Controllers/InvoiceController.php`                                    | ✅ 2026-05-08 |
| 15  | FINDING-014 | Tambah HTTP security headers — CSP, X-Frame-Options, HSTS, X-Content-Type-Options via middleware atau `.htaccess`        | `app/Http/Middleware/` atau `.htaccess`                                         | ✅ 2026-05-08 |
| 16  | FINDING-016 | Gate `deposits.adjustment` route behind `role:ADMIN,FINANCE` (combined dengan FINDING-002)                               | `routes/web.php`                                                                | 🔲           |
| 17  | FINDING-017 | Pindahkan `markOverdue` dari `DashboardController` ke scheduled artisan command + log perubahan status ke `invoice_logs` | `app/Console/Commands/`, `routes/console.php`                                   | 🔲           |
| 18  | FINDING-018 | Cache `Setting::get()` — `Cache::remember('settings_all', 300, ...)` untuk eliminasi 28+ DB hit per request              | `app/Models/Setting.php`                                                        | 🔲           |
| 19  | FINDING-019 | Fix null komisi — treat null sebagai 0 dalam agregasi; require explicit komisi saat override approval                    | `app/Http/Controllers/ImportController.php`                                     | 🔲           |
| 20  | FINDING-020 | Hitung `PaymentMemo.sisa_tagihan` secara dinamis saat view — `max(0, grand_total - payments sum)`                        | `app/Models/PaymentMemo.php` atau view                                          | 🔲           |
| 21  | FINDING-021 | Gate `CreditPaymentController::destroy` behind `role:ADMIN`; implement two-step void (FINANCE propose → ADMIN approve)   | `app/Http/Controllers/CreditPaymentController.php`                              | 🔲           |
| 22  | FINDING-023 | `PartnerController::destroy` — cek `$partner->invoices()->exists()` sebelum delete; tambah soft deletes                  | `app/Http/Controllers/PartnerController.php`                                    | 🔲           |
| 23  | FINDING-024 | Tambah UNIQUE constraint pada `invoices.import_row_id` — cegah double-invoicing                                          | new migration                                                                   | 🔲           |
| 24  | FINDING-026 | Schedule `invoices:mark-overdue` di `routes/console.php` — `->dailyAt('00:01')`                                          | `routes/console.php`                                                            | 🔲           |

---

## ⚪ LONG TERM — Backlog

| # | Finding | Task | File | Status |
|---|---------|------|------|--------|
| 25 | FINDING-022 | Buat tabel `invoice_sequences` keyed by `(prefix, year)` — fix sequence collision saat prefix berubah | new migration + model | 🔲 |
| 26 | FINDING-025 | Naikkan minimum password ke 12 karakter + tambah complexity rules | `app/Http/Controllers/UserController.php` | 🔲 |
| 27 | FINDING-027 | Tambah SRI integrity hash ke Bootstrap CDN tags; atau self-host via Vite | `resources/views/layouts/` | 🔲 |
| 28 | FINDING-028 | Tambah MIME whitelist di `storage-proxy.php`; reject file tidak dalam whitelist | `public/storage-proxy.php` | 🔲 |

---

## Progress Summary

| Tier | Total | Done | Remaining |
|------|-------|------|-----------|
| 🔴 IMMEDIATE | 4 | 4 | 0 |
| 🟠 THIS WEEK | 7 | 7 | 0 |
| 🟡 THIS MONTH | 13 | 4 | 9 |
| ⚪ LONG TERM | 4 | 0 | 4 |
| **TOTAL** | **28** | **15** | **13** |

---

## Urutan Eksekusi Rekomendasi

1. **F-001** — 5 menit, eliminasi RCE publik, zero risk
2. **F-005** — 2 menit, matikan debug mode
3. **F-006** — 2 menit, preserve audit trail
4. **F-007** — 2 menit, fix deposit duplicate bug
5. **F-008** — 2 menit, throttle login
6. **F-015** — 2 menit, encrypt session
7. **F-002** — 30 menit, RBAC semua financial routes (termasuk F-016)
8. **F-003 + F-011** — 25 menit, payment guard
9. **F-004** — 20 menit, deposit adjustment constraint
10. **F-013** — 30 menit, auth di storage proxy
