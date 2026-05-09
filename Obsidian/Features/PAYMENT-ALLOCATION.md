# Feature: Payment Allocation & Credit Balances

## Overview
The new payment model decouples "Money Received" from "Invoices Settle". This allows for batch payments and overpayment management.

## 1. Recording Payments
- Payments are recorded as standalone entities.
- An initial "Primary Invoice" is linked for tracking, but the money is tracked in `amount_unallocated`.
- Status: **UNVERIFIED** (Notes do not contain verification tag).

## 2. Verification Flow
- Finance reviews the bank statement/proof file.
- Action: **Verify**.
- System adds a tamper-evident tag to `notes`: `[VERIFIED by #ID at DATETIME]`.
- Once verified, the payment is `locked` and ready for allocation.

## 3. Allocation Logic
- Verified payments can be allocated to any `UNPAID` or `PARTIAL` invoice.
- Multiple allocations can exist for one payment (Batch Pay).
- Multiple payments can be allocated to one invoice (Partial Pay).

## 4. Overpayments & Credit Balance
- If a payment amount exceeds the allocated invoice amount, the remainder stays in `amount_unallocated`.
- Finance can "Close" the payment and move the remainder to the **Partner Credit Balance**.
- Credit Balances can be applied as a "Virtual Payment" to future invoices.

## 5. Security Guards
- **No Void After Allocation**: Invoices with allocations cannot be voided without first reversing allocations.
- **Double Allocation Guard**: The system prevents allocating more than the current `amount_unallocated` of a payment.
- **Role Lock**: Only `FINANCE` or `ADMIN` roles can verify and allocate payments.
