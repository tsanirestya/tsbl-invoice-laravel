# Database Schema — tsbl_invoice

→ Kembali ke: [[MASTER-PLAN]]
→ Lihat juga: [[LARAVEL-STRUCTURE]] untuk model relationships

**Engine:** MySQL/MariaDB | **Charset:** utf8mb4_unicode_ci
**Source:** Migrated dari CodeIgniter 4 — semua tables pre-exist dengan data
**Laravel migrations:** 8 file dengan `Schema::hasTable()` guards (data preserved)
**CI4 backup:** `ci_migrations_backup` table (rename dari `migrations`)

---

## users ✅
| Column | Type | Notes |
|---|---|---|
| id | int unsigned PK AI | |
| full_name | varchar(150) | |
| email | varchar(150) UNIQUE | login identifier |
| phone | varchar(30) | nullable |
| password | varchar(255) | bcrypt |
| user_status | enum(ADMIN,FINANCE,SALES,VIEWER) | default VIEWER |
| signature_image | varchar(255) | path ke transparent PNG di storage |
| position_name | varchar(100) | nullable |
| is_active | tinyint(1) | default 1 — inactive = cannot login |
| created_at / updated_at | datetime | |

**Data:** 1 user (admin@tsbl.com, ADMIN)

---

## partners ✅
| Column | Type | Notes |
|---|---|---|
| id | int unsigned PK AI | |
| partner_type | enum(HOTEL,TRAVEL,TOURDESK) | |
| nama_partner | varchar(200) | nama display |
| category / channel | varchar(100) | nullable |
| nama_pt | varchar(200) | nama legal entity |
| pic_tsbl | varchar(150) | PIC internal TSBL |
| pic_partner | varchar(150) | PIC di sisi partner |
| pic_partner_phone / email | varchar | nullable |
| address | text | nullable |
| bank_name / bank_account_no / bank_account_name | varchar | nullable |
| npwp | varchar(30) | nullable |
| payment_type | varchar(50) | nullable |
| payment_due_days | int | default 14 |
| limit_credit | decimal(15,2) | default 0 |
| contract_start / contract_end | date | nullable |
| doc_akta_pendirian / akta_perubahan / surat_kuasa / ktp / nib / npwp | varchar(255) | file paths di storage |
| notes | text | nullable |
| is_active | tinyint(1) | |
| created_by / updated_by | int unsigned FK users | nullable |

**Data:** 0 partners

---

## products ✅
| Column | Type | Notes |
|---|---|---|
| id | int unsigned PK AI | |
| product_name | varchar(200) | |
| description | text | nullable |
| default_price | decimal(15,2) | default 0 |
| unit | varchar(30) | default 'Pax' |
| is_active | tinyint(1) | |
| created_by | int unsigned | nullable |

**Data:** 5 products pre-loaded

---

## invoices ✅
| Column | Type | Notes |
|---|---|---|
| id | int unsigned PK AI | |
| invoice_no | varchar(50) UNIQUE | format: INV-YYYY-NNNN |
| partner_id | int unsigned FK partners | |
| guest_name | varchar(200) | nullable |
| visit_date | date | nullable |
| booking_pass_no | varchar(100) | nullable |
| invoice_date | date | required |
| due_date | date | auto dari invoice_date + payment_due_days |
| dsi_transaction_no | varchar(100) | nullable |
| subtotal | decimal(15,2) | sum of invoice_items.amount |
| deposit | decimal(15,2) | default 0 |
| grand_total | decimal(15,2) | subtotal - deposit |
| terbilang | text | auto-generate dari grand_total |
| payment_status | enum(UNPAID,PARTIAL,PAID,OVERDUE) | default UNPAID |
| notes | text | nullable |
| pdf_path | varchar(255) | path permanent PDF — JANGAN digenerate ulang |
| is_finalized | tinyint(1) | 0=draft, 1=final (PDF di-lock) |
| created_by / updated_by | int unsigned FK users | |

**Data:** 0 invoices

---

## invoice_items ✅
| Column | Type | Notes |
|---|---|---|
| id | int unsigned PK AI | |
| invoice_id | int unsigned FK invoices | |
| product_id | int unsigned FK products | nullable — bisa free-text |
| product_name | varchar(200) | snapshot nama saat invoice dibuat |
| pax | int | default 1 |
| price_per_pax | decimal(15,2) | |
| amount | decimal(15,2) | = pax × price_per_pax |
| sort_order | int | default 0 |

---

## payments ✅
| Column | Type | Notes |
|---|---|---|
| id | int unsigned PK AI | |
| invoice_id | int unsigned FK invoices | |
| amount | decimal(15,2) | |
| payment_date | date | |
| payment_method | varchar(50) | nullable |
| reference_no | varchar(100) | nullable |
| proof_file | varchar(255) | path file bukti bayar |
| notes | text | nullable |
| created_by | int unsigned FK users | nullable |
| created_at | datetime | |

---

## settings ✅
| Column | Type | Notes |
|---|---|---|
| id | int unsigned PK AI | |
| key | varchar(100) UNIQUE | |
| value | text | nullable |
| label | varchar(150) | human-readable label |
| updated_at | datetime | |

**Data:** 13 keys pre-loaded:
`company_name`, `company_address`, `company_phone`, `company_email`, `company_npwp`,
`invoice_prefix` (=INV), `default_due_days` (=14), `bank_name`, `bank_account_no`,
`bank_account_name`, `invoice_notes`, `terms_conditions`, `logo_path`

---

## invoice_logs ✅
| Column | Type | Notes |
|---|---|---|
| id | int unsigned PK AI | |
| invoice_id | int unsigned FK invoices | |
| action | varchar(100) | e.g. CREATED, UPDATED, FINALIZED, PAYMENT_ADDED |
| description | text | nullable |
| old_value / new_value | text | nullable — JSON atau plain text |
| created_by | int unsigned FK users | nullable |
| created_at | datetime | |

---

## Tabel Lain (Non-Laravel)
| Table | Keterangan |
|---|---|
| ci_migrations_backup | Backup dari CI4 migrations table (jangan disentuh) |
| ci_sessions | CI4 sessions (tidak dipakai Laravel) |

---

## Migration Status
Semua 8 migrations sudah `DONE` di Laravel `migrations` table.
Karena hasTable guards, semua SKIP create (tables sudah ada) — hanya di-track di Laravel.
