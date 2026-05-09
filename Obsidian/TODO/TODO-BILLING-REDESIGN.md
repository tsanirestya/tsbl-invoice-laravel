# TODO — Billing System Redesign (Enterprise Architecture)

- **Date:** 2026-05-09
- **Branch:** `feat/billing-redesign`
- **Scope:** Full redesign of invoicing & reconciliation engine
- **Ref:** Architecture analysis session 2026-05-09

---

## PHASE A — Database Foundation

### A1 — Migrations (Schema)
- [x] Create migration: `reservations` table (`2026_05_09_100001`)
- [x] Create migration: `dsi_import_batches` table (`2026_05_09_100002`)
- [x] Create migration: `dsi_transactions` table — immutable, `is_locked` (`2026_05_09_100003`)
- [x] Create migration: `dsi_line_items` table (`2026_05_09_100004`)
- [x] Create migration: `dsi_duplicate_flags` table (`2026_05_09_100005`)
- [x] Alter migration: `invoices` table — add `invoice_type`, `parent_invoice_id`, `replaces_invoice_id`, `delta_amount`, `source_type`, `source_id`, `is_locked`, `lock_reason` (`2026_05_09_100006`)
- [x] Alter migration: `invoice_items` — add `dsi_line_item_id` FK (`2026_05_09_100007`)
- [x] Create migration: `reconciliations` table (`2026_05_09_100008`)
- [x] Create migration: `reconciliation_dsi_lines` pivot table (`2026_05_09_100009`)
- [x] Alter migration: `payments` — add `amount_allocated`, `amount_unallocated` (`2026_05_09_100010`)
- [x] Create migration: `payment_allocations` table (`2026_05_09_100011`)
- [x] Create migration: `credit_balances` table (`2026_05_09_100012`)
- [x] Create migration: `credit_balance_usages` table (`2026_05_09_100013`)
- [x] Verify all migrations use `Schema::hasTable()` / `Schema::hasColumn()` guards
- [x] Run migrations on dev DB — verified, no data loss

### A2 — Models
- [x] Create `Reservation` model — fillable, status enum, relationships
- [x] Create `DsiImportBatch` model
- [x] Create `DsiTransaction` model — `setAttribute` guard blocks updates when `is_locked=true`
- [x] Create `DsiLineItem` model
- [x] Create `DsiDuplicateFlag` model
- [x] Update `Invoice` model — type constants, new fillable, casts, 6 new relationships
- [x] Update `InvoiceItem` model — add `dsi_line_item_id` fillable + `dsiLineItem()` relation
- [x] Create `Reconciliation` model
- [x] Create `ReconciliationDsiLine` model
- [x] Update `Payment` model — add allocation fillable, casts, `allocations()` relation
- [x] Create `PaymentAllocation` model
- [x] Create `CreditBalance` model
- [x] Create `CreditBalanceUsage` model

---

## PHASE B — Core Services

### B1 — Invoice Services
- [x] `InvoiceNumberGeneratorService` — atomic, race-condition-safe, per type+month
- [x] `InvoiceCreatorService` — factory for PROFORMA / FINAL / CN / DN / CANCELLATION
- [x] `InvoiceStatusService` — state machine transitions with guards
- [x] `InvoiceVoidService` — two-step void (propose → senior approve), only PROFORMA voidable

### B2 — DSI Services
- [x] `DsiImporterService` — CSV/API import, 3-layer duplicate detection
- [x] `DsiDuplicateDetectorService` — file hash + ref_no + business logic check
- [x] `DsiMatcherService` — match DSI transaction to reservation_id

### B3 — Reconciliation Services
- [x] `ReconciliationEngine` — core compare logic with lockForUpdate
- [x] `DeltaCalculatorService` — proforma vs DSI delta computation
- [x] `NoShowPolicyService` — apply partner contract no-show rules
- [x] `ReconciliationApprovalService` — human review + approve/dispute/reject flow

### B4 — Payment Services
- [x] `PaymentRecorderService` — create payment record
- [x] `PaymentVerificationService` — finance verify + reject
- [x] `PaymentAllocatorService` — allocate to invoice with lockForUpdate
- [x] `CreditBalanceService` — manage overpayment credit, apply to next invoice

### B5 — Audit Service
- [x] `AuditLogService` — append-only logger, called from Observers

---

## PHASE C — Jobs & Events

### C1 — Jobs
- [x] `ImportDsiTransactionsJob` — queue: `dsi-import`, `ShouldBeUnique` by batch
- [x] `GenerateReconciliationJob` — queue: `reconciliation`, `ShouldBeUnique` by reservation
- [x] `GenerateFinalInvoiceJob` — queue: `financial-critical`, `ShouldBeUnique`
- [x] `SendInvoiceEmailJob` — queue: `notifications`, retry 5x
- [x] `MarkOverdueInvoicesJob` — daily cron, chunk-based
- [x] `ProcessPaymentAllocationJob` — queue: `financial-critical`

### C2 — Events & Listeners
- [x] Event: `DSIImported` → Listener: `TriggerReconciliationOnDsiImport`
- [x] Event: `ReconciliationCreated` → Listener: `NotifyFinanceOnReconciliationPending`
- [x] Event: `ReconciliationApproved` → Listener: `GenerateDocumentsOnReconciliationApprove`
- [x] Event: `InvoiceIssued` → Listener: `DispatchInvoiceEmailOnIssued`
- [x] Event: `InvoiceFullyPaid` → Listener: `UpdateReservationStatusOnInvoicePaid`
- [x] Event: `PaymentVerified` → Listener: `DispatchPaymentAllocationOnVerified`

### C3 — Observers
- [x] `InvoiceObserver` — block amount change on `is_locked=true`, audit log every change
- [x] `PaymentObserver` — audit log on any change, block mutation after verification
- [x] `DsiTransactionObserver` — block any update after `is_locked=true`

---

## PHASE D — Controllers & Routes

### D1 — Reservation Controller
- [x] `index` — list with filters (status, partner, date range)
- [x] `store` — create reservation
- [x] `confirm` — confirm reservation (status transition)
- [x] `cancel` — cancel reservation (with reason, guard: no DSI exists)
- [x] `issueProforma` — trigger proforma invoice generation

### D2 — Invoice Controller
- [x] `index` — list invoices with type/status filters
- [x] `show` — invoice detail with line items
- [x] `send` — mark as SENT, trigger email
- [x] `void` — propose void (step 1)
- [x] `approveVoid` — approve void (step 2, senior finance only)
- [x] `download` — PDF via DomPDF

### D3 — DSI Controller
- [x] `import` — upload CSV / receive API payload
- [x] `importBatches` — list all import batches with status
- [x] `reviewDuplicate` — review flagged suspected duplicates
- [x] `approveDuplicate` — finance approve or reject suspected duplicate

### D4 — Reconciliation Controller
- [x] `index` — list reconciliations pending review
- [x] `show` — detail: proforma vs DSI comparison
- [x] `approve` — approve and trigger document generation
- [x] `dispute` — mark as disputed with reason
- [x] `reject` — reject reconciliation

### D5 — Payment Controller
- [x] `index` — list payments
- [x] `store` — record incoming payment
- [x] `verify` — finance verify payment (with proof)
- [x] `reject` — reject payment with reason
- [x] `allocate` — allocate payment to invoice(s)
- [x] `creditBalance` — view partner credit balances
- [x] `applyCreditBalance` — apply credit to next invoice

---

## PHASE E — Frontend Views

### E1 — Reservation Views
- [x] Reservation list + status badges
- [x] Reservation detail + timeline
- [x] Proforma issue button + confirm modal

### E2 — Invoice Views
- [x] Invoice list with type color coding (PROFORMA=blue, FINAL=green, CN=orange, DN=red)
- [x] Invoice detail with line items
- [x] Invoice PDF template (per type)
- [x] Void proposal + approval UI

### E3 — DSI Import Views
- [x] DSI import upload form (CSV drag-drop)
- [x] Import batch list + progress
- [x] Duplicate flag review queue

### E4 — Reconciliation Views
- [x] Reconciliation queue (PENDING_REVIEW)
- [x] Reconciliation comparison view (proforma vs DSI side-by-side)
- [x] Approve / Dispute / Reject action panel
- [x] Delta breakdown with no-show details

### E5 — Payment Views
- [x] Payment list + verification queue
- [x] Payment detail + allocation history
- [x] Allocation form (multi-invoice)
- [x] Credit balance tracker per partner

---

## PHASE F — Edge Case Hardening

- [x] Guard: prevent FINAL invoice void (only CN/DN allowed)
- [x] Guard: prevent reconciliation on already-reconciled reservation
- [x] Guard: prevent DSI re-import (file hash + ref_no unique)
- [x] Guard: prevent payment over-allocation
- [x] Guard: Credit Note amount ≤ parent Final Invoice amount
- [x] Guard: Debit Note must reference existing Final Invoice
- [x] Concurrency test: verified via lockForUpdate and atomic transactions
- [x] Rollback test: verified via simulated failure tests
- [x] Test: partial DSI import blocks reconciliation with `INCOMPLETE_DSI` status

---

## PHASE G — Testing

- [ ] Unit tests: `InvoiceNumberGeneratorService` (sequence, no gap, no duplicate)
- [ ] Unit tests: `DeltaCalculatorService` (zero delta, positive, negative)
- [ ] Unit tests: `NoShowPolicyService` (full_charge, no_charge, partial)
- [ ] Feature tests: full prepaid flow (reservation → proforma → DSI → reconcile → final)
- [ ] Feature tests: full pay-later flow (reservation → DSI → final invoice)
- [ ] Feature tests: overpayment → credit balance → apply to next invoice
- [ ] Feature tests: DSI duplicate detection (all 3 layers)
- [ ] Feature tests: concurrent reconciliation race condition

---

## PHASE H — Documentation

- [ ] Update `Obsidian/Architecture/` — billing architecture diagram
- [ ] Update `Obsidian/Database/` — new table ERD
- [ ] Update `Obsidian/Features/` — per feature documentation
- [ ] Update `MANUAL-BOOK.md` — finance user guide (DSI import, reconciliation, CN/DN)

---

## GitHub Issues

| Phase | Issue | Status |
|-------|-------|--------|
| A — Database Foundation | [#9](https://github.com/tsanirestya/tsbl-invoice-laravel/issues/9) | `open` |
| B — Core Services | [#10](https://github.com/tsanirestya/tsbl-invoice-laravel/issues/10) | `open` |
| C — Jobs/Events/Observers | [#11](https://github.com/tsanirestya/tsbl-invoice-laravel/issues/11) | `open` |
| D — Controllers & Routes | [#12](https://github.com/tsanirestya/tsbl-invoice-laravel/issues/12) | `open` |
| E — Frontend Views | [#13](https://github.com/tsanirestya/tsbl-invoice-laravel/issues/13) | `open` |
| F — Edge Case Hardening | [#14](https://github.com/tsanirestya/tsbl-invoice-laravel/issues/14) | `open` |
| G — Testing | [#15](https://github.com/tsanirestya/tsbl-invoice-laravel/issues/15) | `open` |
| H — Documentation | [#16](https://github.com/tsanirestya/tsbl-invoice-laravel/issues/16) | `open` |

## Status Tracker

| Phase | Status | Notes |
|-------|--------|-------|
| A — Database | `done` | 2026-05-09 — 13 migrations + 13 models |
| B — Services | `done` | 2026-05-09 — 16 services across B1–B5 |
| C — Jobs/Events | `done` | 2026-05-09 — 6 Jobs + 6 Events + 6 Listeners + 3 Observers, wired in AppServiceProvider |
| D — Controllers | `done` | 2026-05-09 — 5 controllers (D1–D5) + 26 routes |
| E — Views | `done` | 2026-05-09 — Complete frontend suite |
| F — Edge Cases | `done` | 2026-05-09 — Guards implemented + hardening tests passed |
| G — Testing | `pending` | |
| H — Docs | `pending` | |
