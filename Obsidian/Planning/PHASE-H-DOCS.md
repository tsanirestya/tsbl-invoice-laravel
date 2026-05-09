# Phase H: Documentation Plan

## 1. Database Schema (`Obsidian/Database/SCHEMA.md`)
- [ ] Add new Billing System tables (Reservations, DSI, Reconciliations, Allocations, Credit Balances).
- [ ] Document key relationships (One-to-Many for Invoices-Items, Many-to-Many for Payments-Invoices via Allocations).
- [ ] Highlight status enumerations for Invoices and Reconciliations.

## 2. Architecture Diagrams (`Obsidian/Architecture/BILLING-FLOW.md`)
- [ ] Create Mermaid diagram for Prepaid Flow.
- [ ] Create Mermaid diagram for Credit/Pay-Later Flow.
- [ ] Document the Reconciliation Engine logic.

## 3. Feature Documentation (`Obsidian/Features/`)
- [ ] `BILLING-CORE.md`: Overall billing philosophy and invoice types.
- [ ] `DSI-RECONCILIATION.md`: How DSI import and reconciliation works.
- [ ] `PAYMENT-ALLOCATION.md`: The new verified payment and allocation model.

## 4. User Manual (`MANUAL-BOOK.md`)
- [ ] Add "Finance & Billing" section.
- [ ] Document Proforma vs Final Invoice workflow.
- [ ] Explain Credit Balance management.
- [ ] Add screenshots/mockups for new views.
