# Phase 5 — Settings & Reports Summary

→ Kembali ke: [[MASTER-PLAN]]

**Date:** 2026-05-05
**Branch:** main
**Agent used:** Backend Architect

---

## What Was Built

### App\Http\Controllers\SettingsController

| Method | Route | Description |
|---|---|---|
| index | GET /settings | Form semua 13 setting keys |
| update | PUT /settings | Simpan semua keys + logo upload |

Keys yang dimanage:
`company_name`, `company_address`, `company_phone`, `company_email`, `company_npwp`,
`invoice_prefix`, `default_due_days`, `bank_name`, `bank_account_no`,
`bank_account_name`, `invoice_notes`, `terms_conditions`, `logo_path`

Logo disimpan di `storage/app/public/settings/`. Old logo auto-delete saat diganti.

### App\Http\Controllers\ReportController

| Method | Route | Description |
|---|---|---|
| index | GET /reports | Report hub dengan summary cards + filter + tabel + per-partner |
| exportCsv | GET /reports/export-csv | Download CSV (UTF-8 BOM agar Excel-friendly) |
| exportPdf | GET /reports/export-pdf | Download PDF A4 Landscape via DomPDF |

Filter yang tersedia:
- `date_from` / `date_to` — filter by invoice_date
- `status` — PAID/UNPAID/PARTIAL/OVERDUE
- `partner_id` — filter per partner
- `search` — no invoice / nama tamu / nama partner
- `finalized` — draft vs finalized

Summary cards: total invoice, revenue PAID, outstanding, overdue + count per status.

Tabs:
1. **Invoice List** — paginated 50/halaman, kolom: no, partner, tamu, tgl, jatuh tempo, grand total, dibayar, sisa, status
2. **Per Partner** — aggregasi per partner: invoice count, total billed, total paid, outstanding

Export respects current filters (query string passed as GET params).

---

## Routes Added (`routes/web.php`)

```php
// Reports (semua role)
Route::get('reports',            ...)->name('reports.index');
Route::get('reports/export-csv', ...)->name('reports.export-csv');
Route::get('reports/export-pdf', ...)->name('reports.export-pdf');

// Settings (admin only — inside role:ADMIN middleware)
Route::get('settings', ...)->name('settings.index');
Route::put('settings', ...)->name('settings.update');
```

---

## Views

| File | Purpose |
|---|---|
| settings/index.blade.php | Form 3 section: Profil Perusahaan, Bank, Konfigurasi Invoice |
| reports/index.blade.php | Summary cards + filter bar + tabs (invoice list + per partner) |
| reports/pdf.blade.php | DomPDF template A4 landscape untuk export PDF |

---

## Sidebar
- "Laporan" → `route('reports.index')` (sebelumnya `#`)
- "Pengaturan" → `route('settings.index')` (sebelumnya `#`)

---

## Commit
```
feat: phase 5 — settings module and financial reports with CSV/PDF export
```
