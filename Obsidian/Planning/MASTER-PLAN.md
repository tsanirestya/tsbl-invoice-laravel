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
