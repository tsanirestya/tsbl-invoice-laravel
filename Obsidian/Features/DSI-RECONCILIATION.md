# Feature: DSI Import & Reconciliation

## Overview
The DSI (Daily Sales Import) system is the source of truth for actual guest consumption. Reconciliation ensures that what we billed in the Proforma matches what the guest actually used.

## 1. DSI Import Workflow
- **File Upload**: Finance uploads CSV/Excel from the destination system.
- **Layer 1 Detection**: System checks file hash to prevent re-importing the same file.
- **Layer 2 Detection**: System checks `ref_no` (Transaction ID) to prevent duplicate rows.
- **Layer 3 Detection**: Semantic check (Same guest + Same Date + Same Amount) flags suspect rows.
- **Review Queue**: Suspected duplicates are held in a review queue for manual resolution.

## 2. Automated Matching
The `DsiMatcherService` attempts to link DSI rows to existing Reservations using:
1. `booking_ref` / `reservation_no`.
2. `guest_name` + `transaction_date`.

## 3. Reconciliation States
- **PENDING_REVIEW**: Newly created reconciliation awaiting Finance approval.
- **APPROVED**: Final Invoice generated, DSI transaction locked.
- **DISPUTED**: Finance flagged an issue (e.g., price mismatch).
- **REJECTED**: Reconciliation discarded, DSI transaction unlocked for re-matching.

## 4. Final Invoice Generation
Upon approval, the system:
1. Creates a `FINAL` type invoice.
2. Uses DSI quantities/prices as the line items.
3. Transfers any payments/allocations from the `PROFORMA` to the `FINAL` invoice.
4. Calculates `delta_amount` for financial reporting.
