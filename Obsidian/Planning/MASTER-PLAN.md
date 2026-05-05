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

### 🔲 Phase 2 — Core Modules (NEXT)
→ TODO: [[TODO-PHASE-2-CORE-MODULES]]
- [ ] User Management (CRUD + signature upload + activate/deactivate)
- [ ] Partner Management (CRUD + doc upload + credit limit + contract reminder)
- [ ] Products Module (CRUD + pricing)

### 🔲 Phase 3 — Invoice Engine
→ TODO: [[TODO-PHASE-3-INVOICE-ENGINE]]
- [ ] Invoice Generator (form + auto invoice no + auto due date + auto terbilang)
- [ ] Invoice items (dynamic rows + product picker + auto calc)
- [ ] Duplicate invoice
- [ ] PDF Generation (DomPDF + watermark PAID/UNPAID + digital signature)
- [ ] Invoice finalization & permanent PDF storage

### 🔲 Phase 4 — Finance Operations
→ TODO: [[TODO-PHASE-4-FINANCE]]
- [ ] Payment Tracking (proof upload + partial payments)
- [ ] Auto-overdue detection (cron / on-request)
- [ ] Payment checklist view
- [ ] Invoice status auto-update dari payment

### 🔲 Phase 5 — Settings & Reports
→ TODO: [[TODO-PHASE-5-SETTINGS-REPORTS]]
- [ ] Settings Module (company profile, bank, invoice prefix, due days, T&C)
- [ ] Reports (outstanding, overdue, paid, revenue, partner transactions)
- [ ] Export Excel + PDF

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
