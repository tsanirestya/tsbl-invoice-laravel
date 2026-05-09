# Billing System — Workflow Architecture

## 1. Prepaid Flow (Standard)
The typical flow for agents paying before guest arrival.

```mermaid
sequenceDiagram
    participant Sales
    participant Finance
    participant Partner
    participant System

    Sales->>System: Create Reservation (PENDING)
    Sales->>System: Confirm Reservation (CONFIRMED)
    Sales->>System: Issue Proforma Invoice
    System-->>Partner: Proforma Generated
    Partner->>Finance: Payment (Transfer/Cash)
    Finance->>System: Record Payment (UNVERIFIED)
    Finance->>System: Verify Payment (VERIFIED)
    System->>System: Auto-allocate to Proforma
    System->>System: Update Proforma -> PAID
    Partner->>System: Guest Visits (DSI Import)
    System->>System: Match DSI to Reservation
    System->>System: Reconciliation (Proforma vs DSI)
    Finance->>System: Approve Reconciliation
    System->>System: Generate FINAL Invoice
    System->>System: Transfer Allocation (Proforma -> Final)
    System->>System: Update Final -> PAID
```

## 2. Pay-Later / Credit Flow
The flow for corporate partners with credit terms.

```mermaid
sequenceDiagram
    participant System
    participant Finance
    participant Partner

    Partner->>System: Guest Visits (DSI Import)
    System->>System: Match DSI to existing Reservation
    System->>System: Auto-generate Proforma (if missing)
    System->>System: Reconciliation
    Finance->>System: Approve Reconciliation
    System->>System: Generate FINAL Invoice (UNPAID)
    System-->>Partner: Final Invoice Sent
    Partner->>Finance: Payment (Batch)
    Finance->>System: Record Batch Payment
    Finance->>System: Verify & Allocate to multiple Final Invoices
```

## 3. Reconciliation Engine Logic
The `ReconciliationEngine` performs the following checks:

1. **Amount Match**: `Proforma Total` == `DSI Total`.
2. **Delta Calculation**: `DSI` - `Proforma`.
3. **No-Show Policy**: If guest didn't visit but reservation exists, apply penalty per `NoShowPolicyService`.
4. **Status Assignment**:
   - `MATCHED`: Delta is 0.
   - `ANOMALY`: Delta exists (Under/Over).
   - `NO_SHOW`: DSI missing for confirmed reservation.
