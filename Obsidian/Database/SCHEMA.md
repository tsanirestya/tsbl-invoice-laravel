# Database Schema — tsbl_invoice (Billing Redesign v2)

→ Kembali ke: [[MASTER-PLAN]]
→ Lihat juga: [[LARAVEL-STRUCTURE]] untuk model relationships

**Engine:** MySQL/MariaDB | **Charset:** utf8mb4_unicode_ci
**Laravel migrations:** 45+ migrations including Phase A-F redesign guards.

---

## users
| Column | Type | Notes |
|---|---|---|
| user_status | enum(ADMIN,FINANCE,SALES,VIEWER,ADMISSION) | Roles check via `CheckRole` middleware. |

---

## reservations (Redesigned)
| Column | Type | Notes |
|---|---|---|
| id | int unsigned PK AI | |
| reservation_no | varchar(50) UNIQUE | Format: RES-YYYYMMDD-XXXX |
| partner_id | int unsigned FK partners | |
| guest_name | varchar(200) | |
| status | enum(PENDING,CONFIRMED,CANCELLED,NO_SHOW,COMPLETED) | |
| proforma_amount | decimal(15,2) | Expected amount from booking. |
| cancel_reason | text | Required if status is CANCELLED. |
| created_by | int unsigned FK users | |

---

## invoices (Redesigned)
| Column | Type | Notes |
|---|---|---|
| invoice_type | enum(PROFORMA,FINAL,CREDIT_NOTE,DEBIT_NOTE,CANCELLATION) | |
| source_type | varchar(100) | App\Models\Reservation, etc. |
| source_id | int unsigned | ID of the source model. |
| payment_status | enum(UNPAID,PARTIAL,PAID,OVERDUE,VOID) | |
| is_locked | tinyint(1) | Locked after reconciliation or void approval. |
| delta_amount | decimal(15,2) | Difference between Proforma and Final. |
| parent_invoice_id| int unsigned FK invoices | Final → Proforma link. |
| replaces_invoice_id| int unsigned FK invoices | VOID/Correction link. |

---

## payments (Redesigned)
| Column | Type | Notes |
|---|---|---|
| amount | decimal(15,2) | Total cash/transfer amount received. |
| amount_unallocated| decimal(15,2) | Remaining amount to be allocated to invoices. |
| is_locked | tinyint(1) | Locked after verification. |
| batch_ref | varchar(100) | Optional reference for batch processing. |

---

## payment_allocations (NEW)
| Column | Type | Notes |
|---|---|---|
| id | int unsigned PK AI | |
| payment_id | int unsigned FK payments | |
| invoice_id | int unsigned FK invoices | |
| amount_allocated | decimal(15,2) | Amount applied to this specific invoice. |
| allocated_by | int unsigned FK users | |

---

## dsi_import_batches (NEW)
| Column | Type | Notes |
|---|---|---|
| id | int unsigned PK AI | |
| batch_ref | varchar(100) UNIQUE | |
| file_name | varchar(255) | |
| file_hash | varchar(64) UNIQUE | Layer 1 duplicate detection. |
| status | enum(PENDING,PROCESSING,COMPLETED,FAILED) | |

---

## dsi_transactions (NEW)
| Column | Type | Notes |
|---|---|---|
| id | int unsigned PK AI | |
| batch_id | int unsigned FK dsi_import_batches | |
| ref_no | varchar(100) | Layer 2 duplicate detection (idempotency key). |
| reservation_id | int unsigned FK reservations | Matched via DsiMatcherService. |
| transaction_date | date | |
| amount | decimal(15,2) | |
| is_locked | tinyint(1) | Locked after reconciliation. |

---

## reconciliations (NEW)
| Column | Type | Notes |
|---|---|---|
| id | int unsigned PK AI | |
| reservation_id | int unsigned FK reservations | |
| proforma_invoice_id| int unsigned FK invoices | |
| dsi_transaction_id | int unsigned FK dsi_transactions | |
| status | enum(PENDING_REVIEW,APPROVED,DISPUTED,REJECTED) | |
| delta_amount | decimal(15,2) | Difference: Proforma vs DSI. |
| no_show_policy_applied| tinyint(1) | |
| final_invoice_id | int unsigned FK invoices | Created upon approval. |

---

## credit_balances (NEW)
| Column | Type | Notes |
|---|---|---|
| id | int unsigned PK AI | |
| partner_id | int unsigned FK partners | |
| balance | decimal(15,2) | Current available credit (overpayments). |
| last_updated_at | datetime | |

---

## credit_balance_usages (NEW)
| Column | Type | Notes |
|---|---|---|
| id | int unsigned PK AI | |
| credit_balance_id | int unsigned FK credit_balances | |
| invoice_id | int unsigned FK invoices | Null if accumulation (CREDIT type). |
| type | enum(CREDIT,DEBIT) | CREDIT=accumulation, DEBIT=application. |
| amount | decimal(15,2) | |
