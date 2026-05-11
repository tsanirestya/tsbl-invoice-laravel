# ISSUE-002 — Dynamic Dropdown Filtering (Partner Category)

- **Date:** 2026-05-11
- **GitHub Issue:** [#18](https://github.com/tsanirestya/tsbl-invoice-laravel/issues/18)
- **Status:** `pending`
- **Component:** `InvoiceController`, `New Invoice Form`

## Problem Statement

When creating a new invoice, the product/service and DSI Code dropdowns show all available items regardless of the selected partner. This can lead to selection errors where a user chooses a product category that doesn't match the partner type (e.g., choosing a Travel category for a Hotel partner).

## Requirements

The dropdowns for **Produk/Layanan** and **DSI Code** must filter their options dynamically based on the **Partner Category** of the selected partner:

| Partner Category | Filter Category |
|------------------|-----------------|
| Hotel            | `HTL`           |
| Travel           | `TVL`           |
| Tour Desk        | `TRD`           |

## Implementation Plan

1. **Frontend (JS):** 
   - Add a listener to the Partner dropdown.
   - When a partner is selected, fetch their category (if not already present in the data attributes).
   - Filter the product and DSI Code dropdowns based on the category code.
   
2. **Backend (Optional):**
   - Ensure the API/Controller provides the category information for partners if not already available in the initial page load.

## Acceptance Criteria

- [x] Selecting a Hotel partner filters dropdowns to show only `HTL` related items.
- [x] Selecting a Travel partner filters dropdowns to show only `TVL` related items.
- [x] Selecting a Tour Desk partner filters dropdowns to show only `TRD` related items.
- [x] Resetting or changing partner correctly updates the filters.
- [x] Auto-reset manual inputs when partner category changes.
- [x] Display warning for mismatch in DSI imports.
