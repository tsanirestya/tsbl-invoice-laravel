# TSBL Invoice System

A complete B2B invoice management system for **PT Tunas Satria Borneo Lestari**, built to handle multi-partner invoicing, payment tracking, partner performance analytics, and financial reporting.

---

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [User Roles & Permissions](#user-roles--permissions)
- [Module Documentation](#module-documentation)
  - [Dashboard](#dashboard)
  - [Invoice Management](#invoice-management)
  - [Payment Tracking](#payment-tracking)
  - [Partner Management](#partner-management)
  - [Product Catalog](#product-catalog)
  - [Financial Reports](#financial-reports)
  - [Settings](#settings)
  - [User Management](#user-management)
- [Database Schema](#database-schema)
- [PDF Generation](#pdf-generation)
- [Audit Logging](#audit-logging)
- [Development Conventions](#development-conventions)

---

## Overview

TSBL Invoice System is a Laravel-based internal web application that manages the full invoicing lifecycle — from creation to payment collection. It supports partners across three types (Hotel, Travel Agent, Tour Desk), tracks payment status automatically, generates branded PDF invoices, and provides per-partner performance scorecards with risk grading.

**Live URL (local):** `http://localhost/tsbl-invoice-laravel/public`

---

## Features

| Category | Features |
|---|---|
| **Invoicing** | Create, edit, finalize, duplicate invoices · Auto-generate invoice numbers (INV-YYYY-XXXX) · Multi-item line entries (pax-based pricing) · Deposit deduction · PDF export |
| **Payments** | Record partial and full payments · Multiple payment methods · Proof file upload · Auto status recalculation (UNPAID → PARTIAL → PAID) |
| **Partners** | Full partner profile with documents · Contract expiry tracking · Credit limit monitoring · Performance scorecard (A–D risk grades) |
| **Products** | Product catalog with DSI codes · Per-unit pricing, publish rate, nett price, commission |
| **Reports** | Invoice reports with multi-filter · CSV and PDF export · Per-partner summary |
| **Settings** | Company branding (logo, favicon, navbar logo) · Bank account details · Invoice prefix/number configuration |
| **Users** | Role-based access control · Signature image per user · Active/inactive status |
| **Overdue Detection** | Batch overdue marking · Automatic status flags on past-due invoices |

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12.0, PHP 8.2+ |
| Frontend | Bootstrap 5.3, Bootstrap Icons (CDN) |
| Database | MySQL / MariaDB (via XAMPP) |
| PDF | barryvdh/laravel-dompdf ^3.1 |
| Build Tool | Vite 7, Laravel Vite Plugin |
| Session | File driver |
| Dev Server | XAMPP (Apache + MariaDB) |

---

## System Requirements

- PHP 8.2+
- Composer 2.x
- MySQL / MariaDB (XAMPP recommended on Windows)
- Node.js 18+ and npm
- Apache with `mod_rewrite` enabled

---

## Installation

```bash
# 1. Clone the repository
git clone https://github.com/tsanirestya/tsbl-invoice-laravel.git
cd tsbl-invoice-laravel

# 2. Install PHP dependencies
composer install

# 3. Copy environment file and configure
cp .env.example .env
php artisan key:generate

# 4. Configure .env — set DB credentials
# DB_DATABASE=tsbl_invoice
# DB_USERNAME=root
# DB_PASSWORD=

# 5. Run migrations (guards prevent re-creation on existing tables)
php artisan migrate

# 6. Seed default data (settings, admin user)
php artisan db:seed

# 7. Install frontend dependencies and build assets
npm install
npm run build

# 8. Set storage permissions and create symlink
php artisan storage:link
```

> **Warning:** Never run `php artisan migrate:fresh` — existing production data will be lost. All migrations include `Schema::hasTable()` guards.

---

## User Roles & Permissions

| Role | Dashboard | Invoices | Payments | Partners | Products | Reports | Settings | Users |
|---|---|---|---|---|---|---|---|---|
| **ADMIN** | ✅ | Full | Full | Full | Full | Full | ✅ | Full |
| **FINANCE** | ✅ | Full | Full | Read | Read | ✅ | — | — |
| **SALES** | ✅ | Create/View | View | Read | Read | — | — | — |
| **VIEWER** | ✅ | View | View | View | View | View | — | — |

---

## Module Documentation

### Dashboard

Displays real-time financial summary:
- Total invoices · Unpaid · Partial · Paid · Overdue counts
- Total revenue and outstanding balance
- Recent 10 invoices with quick status view
- Active partner count

---

### Invoice Management

**Route prefix:** `/invoices`

**Invoice lifecycle:**

```
DRAFT → (finalize) → FINAL → (payments) → PARTIAL / PAID
                                        → OVERDUE (if past due date)
```

**Key behaviors:**
- Invoice number auto-generated as `INV-YYYY-NNNN` (sequential per year)
- Items priced per pax (quantity × price per pax)
- Deposit deducted from subtotal to calculate grand total
- `terbilang` (amount in words — Indonesian) auto-generated
- Only **DRAFT** invoices can be edited or deleted
- **Finalized** invoices generate a PDF stored in `storage/app/public/invoices/`
- Duplicate creates a new DRAFT copy with a new invoice number

**Status values:** `UNPAID` · `PARTIAL` · `PAID` · `OVERDUE`

---

### Payment Tracking

**Route:** `/payments` (list) · `POST /invoices/{id}/payments` (record)

- Records payment amount, date, method, reference number, and optional proof file
- Invoice status recalculates automatically after each payment:
  - `paid_total == 0` → `UNPAID`
  - `0 < paid_total < grand_total` → `PARTIAL`
  - `paid_total >= grand_total` → `PAID`
- Batch overdue detection via `POST /invoices/mark-overdue`

---

### Partner Management

**Route prefix:** `/partners`

Partner types: `HOTEL` · `TRAVEL` · `TOURDESK`

**Profile stores:**
- Company data (PT name, PIC contacts, address)
- Bank account details for payment processing
- Legal documents: Akta Pendirian, Akta Perubahan, Surat Kuasa, KTP, NIB, NPWP
- Contract dates with 30-day expiry warning
- Credit limit and payment due days

**Performance Scorecard** (`/partners/performance`):

| Metric | Description |
|---|---|
| On-time payment rate | % invoices paid before due date |
| Average days late | Mean delay across late payments |
| Credit utilization | Outstanding balance vs. credit limit |
| Total invoice value | Lifetime billing amount |

**Risk Grades:**

| Grade | Criteria |
|---|---|
| **A** | On-time ≥ 90%, utilization < 50% |
| **B** | On-time ≥ 75%, utilization < 75% |
| **C** | On-time ≥ 50% |
| **D** | On-time < 50% or high utilization |

---

### Product Catalog

**Route prefix:** `/products`

Each product stores:
- DSI code and description
- Partner type association
- Pricing tiers: `default_price`, `publish_rate`, `nett_price`, `unit_price_dsi`
- Commission amount (`komisi`)
- Unit type and payment mode
- Active/inactive toggle

---

### Financial Reports

**Route prefix:** `/reports`

**Filter options:** Status · Partner · Date range · Finalized status

**Export formats:**
- `GET /reports/export-csv` — Downloads filtered invoice data as CSV
- `GET /reports/export-pdf` — Downloads formatted PDF report with summary

Report includes per-partner breakdown: invoice count, total billed, total paid, outstanding balance.

---

### Settings

**Route:** `GET/PUT /settings` (ADMIN only)

Configurable items:
- Company name, address, phone, email, website
- Invoice number prefix and starting sequence
- Default payment due days
- Bank account name, number, bank name
- Logo (main, favicon, navbar) — uploaded to `public/uploads/`

---

### User Management

**Route prefix:** `/users` (ADMIN only)

- Create users with role assignment (ADMIN / FINANCE / SALES / VIEWER)
- Upload signature image (used on PDF invoices)
- Activate / deactivate accounts
- Admin cannot delete their own account

---

## Database Schema

```
users
├── id, full_name, email, phone, password
├── user_status (ADMIN|FINANCE|SALES|VIEWER)
├── signature_image, position_name, is_active
└── remember_token, timestamps

partners
├── id, partner_type (HOTEL|TRAVEL|TOURDESK)
├── nama_partner, category, channel, nama_pt
├── pic_tsbl, pic_partner, pic_partner_phone, pic_partner_email
├── address, bank_name, bank_account_no, bank_account_name
├── npwp, payment_type, payment_due_days, limit_credit
├── contract_start, contract_end
├── doc_akta_pendirian, doc_akta_perubahan, doc_surat_kuasa
├── doc_ktp, doc_nib, doc_npwp
├── notes, is_active, created_by, updated_by, timestamps

products
├── id, product_name, partner_type, dsi_code, description
├── default_price, publish_rate, komisi, nett_price, unit_price_dsi
├── unit, payment_mode, is_active, created_by, timestamps

invoices
├── id, invoice_no, partner_id, guest_name
├── visit_date, booking_pass_no, invoice_date, due_date
├── dsi_transaction_no, subtotal, deposit, grand_total, terbilang
├── payment_status (UNPAID|PARTIAL|PAID|OVERDUE)
├── pdf_path, is_finalized, notes
├── created_by, updated_by, timestamps

invoice_items
├── id, invoice_id, product_id, product_name
├── pax, price_per_pax, amount, sort_order, timestamps

payments
├── id, invoice_id, amount, payment_date
├── payment_method, reference_no, proof_file
├── notes, created_by, created_at

invoice_logs
├── id, invoice_id, action
├── (CREATED|UPDATED|FINALIZED|PAYMENT_ADDED|PAYMENT_DELETED|OVERDUE)
├── description, old_value, new_value
├── created_by, created_at

settings
└── id, key, value, label
```

---

## PDF Generation

Uses `barryvdh/laravel-dompdf`. Invoice PDF template: `resources/views/invoices/pdf.blade.php`

Generated files stored at: `storage/app/public/invoices/{invoice_no}.pdf`

Accessible via: `GET /invoices/{id}/pdf?action=download` or `?action=inline`

PDF includes:
- Company header with logo and address
- Partner billing information
- Line items table (product, pax, price, amount)
- Deposit deduction and grand total
- Terbilang (amount in words — Indonesian)
- Authorized signatory with signature image

---

## Audit Logging

Every invoice action is logged to `invoice_logs`:

| Action | Trigger |
|---|---|
| `CREATED` | Invoice stored |
| `UPDATED` | Invoice or items edited |
| `FINALIZED` | Invoice finalized and PDF generated |
| `PAYMENT_ADDED` | Payment recorded |
| `PAYMENT_DELETED` | Payment removed |
| `OVERDUE` | Status changed to overdue |

Logs capture `old_value` / `new_value` as JSON for change diffing.

---

## Development Conventions

### Commit Format

```
feat:     new feature
fix:      bug fix
docs:     documentation only
chore:    setup/config/tooling
refactor: code restructure without behavior change
```

### Branch Strategy

```
main                     ← always stable, production-ready
feat/phase-N-description ← per-phase feature branches
```

### Migration Rules

- Always guard with `Schema::hasTable()` before creating tables
- Never run `migrate:fresh` — existing data is production data
- Additive migrations only — no destructive column changes without backfill

---

## License

Internal use only — PT Tunas Satria Borneo Lestari.
