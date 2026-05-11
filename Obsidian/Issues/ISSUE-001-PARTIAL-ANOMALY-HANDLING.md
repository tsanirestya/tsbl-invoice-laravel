# Issue #001: Handle Partial Anomalies in Invoicing Workflow

**Type**: Feature / UX Improvement
**Status**: Open
**Priority**: High

## Problem
When a transaction number has multiple items where some are `valid` (approved) and some are `anomaly` (unapproved), the transaction appears in the "Pending Invoices" queue but only shows the approved items. This leads to:
1. Users creating incomplete invoices.
2. Confusion regarding why certain items are missing from a transaction.
3. High friction in switching between "Invoice Create" and "Import Review" screens.

## Proposed Solution
- [ ] Update `PendingInvoiceController` to detect unapproved siblings.
- [ ] Add warning icon in `pending-invoices.index` view.
- [ ] Load unapproved anomalies in `invoices.create` view.
- [ ] Add "Direct Action" link from `invoices.create` to `import-review.override`.

## Technical Notes
- Controller needs to pass `has_unhandled_anomalies` boolean to the queue view.
- `InvoiceController@create` should fetch `TransactionImportRow` without the `is_approved` constraint for the display list, but maintain it for the actual billable calculation.
