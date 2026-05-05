# Laravel Architecture — TSBL Invoice System

→ Kembali ke: [[MASTER-PLAN]]
→ Database detail: [[SCHEMA]]

---

## Module → Controller Map

| Module | Controller | Route Prefix | Status |
|---|---|---|---|
| Auth | AuthController | /login, /logout | ✅ Done |
| Dashboard | DashboardController | /dashboard | ✅ Done |
| Users | UserController | /users | 🔲 Phase 2 |
| Partners | PartnerController | /partners | 🔲 Phase 2 |
| Products | ProductController | /products | 🔲 Phase 2 |
| Invoices | InvoiceController | /invoices | 🔲 Phase 3 |
| Payments | PaymentController | /invoices/{id}/payments | 🔲 Phase 4 |
| Reports | ReportController | /reports | 🔲 Phase 5 |
| Settings | SettingController | /settings | 🔲 Phase 5 |

---

## Middleware
| Middleware | Class | Fungsi |
|---|---|---|
| `auth` | Laravel built-in | Must be logged in |
| `guest` | Laravel built-in | Redirect if logged in |
| `role:ADMIN` | CheckRole | Status-based access |
| `role:FINANCE` | CheckRole | Finance only routes |

---

## Model List & Relationships

```
User
  └─ hasMany Invoice (created_by)

Partner
  └─ hasMany Invoice

Invoice
  ├─ belongsTo Partner
  ├─ belongsTo User (creator)
  ├─ hasMany InvoiceItem (ordered by sort_order)
  ├─ hasMany Payment
  └─ hasMany InvoiceLog

InvoiceItem
  ├─ belongsTo Invoice
  └─ belongsTo Product (nullable — bisa free-text)

Payment
  └─ belongsTo Invoice

InvoiceLog
  └─ belongsTo Invoice

Product (standalone — dipakai di InvoiceItem)

Setting (key-value store — static helpers: get/set)
```

---

## Service Classes (Phase 3+)
| Service | Fungsi |
|---|---|
| `InvoiceNumberService` | Auto generate invoice number (prefix + year + sequence) |
| `TerbilangService` | IDR amount → teks Indonesia ("Satu Juta Rupiah") |
| `OverdueService` | Auto-mark invoice OVERDUE jika due_date < today |
| `PdfService` | DomPDF wrapper — generate + store invoice PDF |

---

## Route Groups Structure
```php
// Guest only
Route::middleware('guest')->group(function () {
    GET  /login  → AuthController@showLogin
    POST /login  → AuthController@login
});

// Authenticated — semua role
Route::middleware('auth')->group(function () {
    POST /logout
    GET  /dashboard
    resource /invoices  (Phase 3)
    resource /partners  (Phase 2)
    resource /products  (Phase 2)
    resource /payments  (Phase 4)
    resource /reports   (Phase 5)
});

// Admin only
Route::middleware(['auth', 'role:ADMIN'])->group(function () {
    resource /users     (Phase 2)
    resource /settings  (Phase 5)
});
```

---

## Storage Structure
```
storage/app/public/
  ├── signatures/       ← user digital signatures (PNG transparent)
  ├── partners/         ← partner legal documents
  │     ├── akta/
  │     ├── ktp/
  │     ├── nib/
  │     └── npwp/
  ├── invoices/         ← generated PDF invoices (permanent)
  └── payments/         ← payment proof uploads
```

---

## Key Config
- `APP_URL` = `http://localhost/tsbl-invoice-laravel/public`
- Session: `file` driver (bukan database — hindari MariaDB compat issue)
- Cache: `file` driver
- Queue: `sync` (no queue worker needed)

---

## Related Notes
- [[MASTER-PLAN]] — project roadmap
- [[SCHEMA]] — database schema
- [[DESIGN-SYSTEM]] — UI/UX guidelines
- [[TODO-PHASE-1-FOUNDATION]] — phase 1 detail
