# Phase 3 — Invoice Engine Summary

→ Kembali ke: [[MASTER-PLAN]]

**Date:** 2026-05-05
**Branch:** main
**Agent used:** Backend Architect

---

## What Was Built

### App\Helpers\Terbilang
- Converts numeric amounts to Indonesian words (e.g. `1500000` → `satu juta lima ratus ribu rupiah`)
- Handles up to miliar (billions)
- Static class, call via `Terbilang::convert(float $amount): string`

### App\Http\Controllers\InvoiceController
Full CRUD + extra actions:

| Method | Route | Description |
|---|---|---|
| index | GET /invoices | List with filters (search, status, partner, date range) |
| create | GET /invoices/create | Form with partner/product pickers |
| store | POST /invoices | Save invoice + items, auto invoice_no, auto terbilang |
| show | GET /invoices/{id} | Detail: items, payments, activity log |
| edit | GET /invoices/{id}/edit | Edit form (blocked if finalized) |
| update | PUT /invoices/{id} | Update invoice + replace items |
| duplicate | POST /invoices/{id}/duplicate | Copy as new draft, new invoice_no, today's date |
| finalize | POST /invoices/{id}/finalize | Generate PDF → storage/public/invoices/, lock |
| pdf | GET /invoices/{id}/pdf | Stream stored PDF, or draft preview if not finalized |
| destroy | DELETE /invoices/{id} | Delete if not finalized |

### Invoice Number Auto-generation
Format: `{prefix}-{YYYY}-{NNNN}` — reads `invoice_prefix` setting, finds last seq for year, increments.

### PDF Template (`resources/views/invoices/pdf.blade.php`)
- DomPDF A4 portrait
- Watermark: UNPAID/PARTIAL/PAID/OVERDUE (6% opacity, rotated)
- Company header from settings
- Partner billing block, guest info, items table
- Totals: subtotal → deposit → grand total
- Terbilang box
- Bank info from settings
- Signature block with stored signature image if available
- Notes + T&C from settings

### Views
| File | Purpose |
|---|---|
| invoices/index.blade.php | List table, filters, dropdown actions |
| invoices/show.blade.php | Detail: items, payments summary, activity log |
| invoices/create.blade.php | Create form wrapper |
| invoices/edit.blade.php | Edit form wrapper |
| invoices/_form.blade.php | Shared form partial — dynamic JS item rows |
| invoices/pdf.blade.php | PDF template for DomPDF |

### Dynamic Line Items (`_form.blade.php`)
- JS `addRow()` / `removeRow()` — no page reload
- Product picker → auto-fills name + price
- Live subtotal/grand total recalc
- Live terbilang preview (JS mirrors PHP logic)
- Auto due date from partner's `payment_due_days` when partner or invoice_date changes

---

## Routes Added (`routes/web.php`)
```php
Route::resource('invoices', InvoiceController::class);
Route::post('invoices/{invoice}/finalize',  ...)->name('invoices.finalize');
Route::post('invoices/{invoice}/duplicate', ...)->name('invoices.duplicate');
Route::get('invoices/{invoice}/pdf',        ...)->name('invoices.pdf');
```

---

## Sidebar / Bottom Nav
- Invoice sidebar link → `route('invoices.index')`
- Bottom nav + FAB → `route('invoices.create')`

---

## Commit
```
feat: phase 3 — invoice engine (CRUD, PDF, finalization, duplicate)
```
