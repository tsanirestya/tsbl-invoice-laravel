# TODO — Phase 1: Foundation

## Status
- ✅ Completed — 2026-05-05

## Priority
- High

## Assigned Agent
- engineering-backend-architect
- engineering-senior-developer

## Objective
Install Laravel 11, configure environment, scaffold authentication and base layout.

## Scope
- Laravel 11 installation in project root
- .env setup untuk tsbl_invoice MySQL database
- Laravel migrations wrapping existing CI4 schema (has-table guards)
- Authentication: login, logout, session, role-based middleware
- Base Blade layout Bootstrap 5 mobile-first
- Dashboard skeleton dengan stat cards

## Technical Notes
- DB pre-exists dari CodeIgniter 4 — gunakan `Schema::hasTable()` guards di semua migrations
- CI4 `migrations` table di-rename ke `ci_migrations_backup` sebelum Laravel migrate
- Auth: manual session-based (tanpa Breeze/Jetstream)
- PDF: install barryvdh/laravel-dompdf
- File storage: `storage/app/public` dengan symlink
- Login form harus pakai `route('login')` bukan hardcoded `/login` — karena app jalan di subdirectory

## UI/UX Notes
→ Lihat: [[DESIGN-SYSTEM]]
- Bootstrap 5 CDN + Bootstrap Icons CDN
- Sidebar fixed desktop, slide-in mobile
- Bottom navigation bar mobile (5 tab)
- Card-based stat dashboard
- Color system: Green=paid, Red=overdue, Orange=partial, Blue=info

## Database Impact
→ Lihat: [[SCHEMA]]
- Tidak ada destructive change — data existing preserved
- CI4 `migrations` → `ci_migrations_backup`
- Laravel buat `migrations` table baru

## Files Affected
```
.env
bootstrap/app.php
routes/web.php
app/Models/ (User, Partner, Invoice, InvoiceItem, Payment, Product, Setting, InvoiceLog)
app/Http/Controllers/Auth/AuthController.php
app/Http/Controllers/DashboardController.php
app/Http/Middleware/CheckRole.php
resources/views/layouts/app.blade.php
resources/views/auth/login.blade.php
resources/views/dashboard/index.blade.php
database/migrations/2026_05_05_000001 s/d 000008
```

## Dependencies
- PHP 8.2.12 ✅
- Composer 2.9.5 ✅
- MySQL tsbl_invoice DB ✅
- Bootstrap 5.3 CDN ✅
- barryvdh/laravel-dompdf ✅

## Expected Output
- Laravel app berjalan di `http://localhost/tsbl-invoice-laravel/public`
- Login page → Dashboard redirect
- Mobile-responsive base layout

## Checklist
- [x] Planning
- [x] Database (schema audited, CI4 backup, migrations created)
- [x] Backend (models, auth controller, middleware, routes, dashboard controller)
- [x] Frontend (login view, app layout, dashboard view)
- [x] DomPDF installed
- [x] Storage symlink created
- [x] Admin password reset: admin@tsbl.com / admin123
- [x] Documentation (Obsidian struktur dibuat)
- [x] Mobile Responsive (sidebar + bottom nav)
- [x] Obsidian Updated

## Known Issues Fixed
- Login form `action="/login"` → `action="{{ route('login') }}"` (fix 404 di subdirectory)

## Progress Log
- 2026-05-05 — Phase 1 initiated. DB schema audited. Obsidian structure created.
- 2026-05-05 — Laravel 11 installed (via temp dir karena project dir non-empty).
- 2026-05-05 — .env configured, app key generated, DomPDF installed, storage linked.
- 2026-05-05 — 8 migrations created dengan hasTable guards. CI4 migrations renamed.
- 2026-05-05 — Semua models dibuat dengan relationships.
- 2026-05-05 — AuthController, CheckRole middleware, routes selesai.
- 2026-05-05 — Base layout (Bootstrap 5 mobile-first), login view, dashboard view selesai.
- 2026-05-05 — Fix: login form action pakai `route('login')`.
- 2026-05-05 — ✅ Phase 1 COMPLETE.

## Related Notes
- [[MASTER-PLAN]] — roadmap keseluruhan
- [[LARAVEL-STRUCTURE]] — architecture & module map
- [[SCHEMA]] — database schema detail
- [[DESIGN-SYSTEM]] — UI/UX guidelines
