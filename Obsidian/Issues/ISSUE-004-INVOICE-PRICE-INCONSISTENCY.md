# ISSUE-004: Inconsistency of Adjusted Anomaly Prices in Invoice Queue and Form

## Problem Description
User reported that after adjusting Gross and Nett prices for anomaly transaction rows in the **Import Review** module, the updated prices were not correctly reflected in:
1.  The **Antrian Invoice** (Pending Invoices) list.
2.  The **Invoice Creation Form** (Item Pax Price).

### Root Causes
1.  **Queue Display**: The `PendingInvoiceController` was hardcoded to display only the Gross price sum (`publish_rate ?: unit_price`) and lacked granularity to show Nett adjustments or per-unit breakdowns.
2.  **Form Overwrite**: The JavaScript `pickProduct` function in the invoice form was automatically fetching standard prices from the `Product` database and overwriting the adjusted prices passed during pre-fill.
3.  **Dropdown Dependency**: The pricing logic in the form relied heavily on the `Metode Pembayaran` selection. If no method was selected (initial state), it defaulted back to standard Nett prices, ignoring adjustments.

---

## Fix Implementation

### 1. Backend: Pending Invoice Controller
- **File**: `app/Http/Controllers/PendingInvoiceController.php`
- **Change**: Added `total_gross`, `total_nett`, `unit_gross`, `unit_nett`, and `qty` to the grouped transaction data.
- **Logic**: Ensured that the "Total" display is no longer ambiguous by providing a per-unit breakdown.

### 2. Frontend: Queue View
- **File**: `resources/views/pending-invoices/index.blade.php`
- **Change**: Updated the "Total" column to show `[Unit Price] x [Qty]`.
- **Logic**: Added conditional display for Nett prices if they differ from Gross, ensuring adjustments are visible at a glance.

### 3. Frontend: Invoice Form (Core Fix)
- **File**: `resources/views/invoices/_form.blade.php`
- **Changes**:
    - **Data Persistence**: Modified `addRow()` to store adjusted prices in the row's dataset (`data-price-gross` and `data-price-nett`).
    - **Context-Aware Pricing**: Updated `getPriceForProduct(prod, row)` to prioritize dataset values over product table values.
    - **Initialization**: Fixed `pickProduct` and `pickProductByName` to pass the `row` context, preventing standard prices from overwriting adjustments.
    - **Defaulting**: Added logic to use adjusted prices as the default even before a `Metode Pembayaran` is selected.

---

## Side Effects Analysis
- **Manual Rows**: Rows added via "Tambah Baris" do not have import context, so they correctly use standard product rates.
- **Edit Mode**: Existing invoices already have their prices saved in `invoice_items`. Changing the payment method in edit mode will correctly use adjusted rates if the invoice was originally created from an import.
- **Finance Table**: The bottom "Pengecekan Finance" now accurately reflects the actual invoiced amount (including adjustments) instead of just theoretical product rates.

## Verification Steps
1.  Adjust an anomaly price in **Import Transaksi**.
2.  Check **Antrian Invoice**; the breakdown (Price x Qty) should reflect the adjusted rate.
3.  Click **Buat**; the "Harga/Pax" in the table should immediately show the adjusted rate.
