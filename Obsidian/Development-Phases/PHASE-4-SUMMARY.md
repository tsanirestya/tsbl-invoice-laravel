# Phase 4 — Finance Operations Summary

→ Kembali ke: [[MASTER-PLAN]]

**Date:** 2026-05-05
**Branch:** main
**Agent used:** Backend Architect

---

## What Was Built

### Invoice Model — `recalcStatus()`
Auto-recalculates `payment_status` after any payment change:
- `PAID` — totalPaid >= grand_total
- `PARTIAL` — 0 < totalPaid < grand_total
- `OVERDUE` — totalPaid == 0 AND due_date.isPast()
- `UNPAID` — totalPaid == 0 AND not overdue

### App\Http\Controllers\PaymentController

| Method | Route | Description |
|---|---|---|
| index | GET /payments | Checklist semua finalized invoice + outstanding summary |
| store | POST /invoices/{invoice}/payments | Tambah pembayaran (proof upload, auto recalc status) |
| destroy | DELETE /invoices/{invoice}/payments/{payment} | Hapus pembayaran (delete proof file, auto recalc status) |

### App\Console\Commands\MarkOverdueInvoices
Artisan command: `php artisan invoices:mark-overdue`
- Cari finalized invoices: status UNPAID + due_date < today
- Update ke OVERDUE + buat InvoiceLog entry
- Bisa dijadwalkan via cron atau dijalankan manual

### InvoiceController — `markOverdue()`
Web action via `POST /invoices/mark-overdue` (tombol di halaman Pembayaran).
Same logic sebagai artisan command — untuk on-demand dari browser.

### Views

| File | Purpose |
|---|---|
| payments/index.blade.php | Checklist semua finalized invoices, filter status/partner/search, outstanding summary cards, tombol Update Overdue |
| invoices/show.blade.php (update) | Add payment form (amount, date, method, reference, proof upload), riwayat pembayaran dengan tombol hapus + lihat bukti |

---

## Routes Added (`routes/web.php`)
```php
Route::post('invoices/mark-overdue',                    ...)->name('invoices.mark-overdue');
Route::get('payments',                                  ...)->name('payments.index');
Route::post('invoices/{invoice}/payments',              ...)->name('payments.store');
Route::delete('invoices/{invoice}/payments/{payment}',  ...)->name('payments.destroy');
```

Note: `mark-overdue` registered BEFORE `Route::resource('invoices')` agar tidak diambil oleh `{invoice}` binding.

---

## File Storage
Proof files disimpan di `storage/app/public/payments/{invoice_id}/`.
Dihapus otomatis saat payment didelete.

---

## Sidebar
- "Pembayaran" link di sidebar sekarang pointing ke `route('payments.index')`

---

## Commit
```
feat: phase 4 — finance operations (payment tracking, overdue detection, checklist)
```
