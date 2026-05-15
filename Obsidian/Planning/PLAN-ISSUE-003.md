# Implementation Plan — Issue #003: Booking Pass Template Enhancements

## Overview
This plan addresses limitations in the Booking Pass Template system, specifically missing configuration fields, broken barcode rendering in PDFs, and limited styling options in the visual editor.

## 1. Database Schema Update
**Goal**: Add template-level configuration for QR/Barcode mode and template categorization.

- **Migration**: `2026_05_14_000001_add_type_fields_to_booking_pass_templates.php`
- **Columns**:
    - `qr_type`: ENUM('qr', 'barcode') — Default global mode.
    - `template_type`: ENUM('self_service', 'internal', 'partner') — For categorization.
- **Action**: Run `php artisan migrate`.

## 2. Backend Logic & Services
### A. Barcode Rendering Service
- **New File**: `app/Services/BarcodeRenderer.php`
- **Logic**: Pure PHP implementation of Code 39 SVG generator. 
- **Rationale**: DomPDF struggles with font-based barcodes or complex CSS. SVG `<rect>` elements are rendered natively and reliably.

### B. Booking Pass Service Enhancements
- **File**: `app/Services/BookingPassService.php`
- **Changes**:
    - Include `product_name` in renderable values.
    - Use `BarcodeRenderer` when `output_type` is 'barcode'.
    - Implement `previewWithReservation` to allow testing with real data.

## 3. Visual Editor (JS) Upgrades
**File**: `public/js/booking-pass-editor.js`

- **Style Panel**:
    - Add inputs for `label_font_size` and `label_color`.
    - Add `output_type` selector (Text / QR / Barcode).
- **Canvas Logic**:
    - Update `applyBoxStyles` to show dashed borders for special output types (Purple for QR, Orange for Barcode).
    - Update `getPreviewValue` to show visual placeholders like `▣ QR: value`.
- **Variables**: Add `product_name` to `PREVIEW_VALUES`.

## 4. PDF Template Refinement
**File**: `resources/views/booking-pass/pdf-custom.blade.php`

- **Styles**: Use `pt` units instead of `px` for better PDF consistency.
- **Conditional Rendering**:
    - `output_type === 'barcode'`: Call `BarcodeRenderer::code39`.
    - `output_type === 'qr'`: Render a scannable-looking placeholder (until a QR library is added).
    - Apply `label_font_size` and `label_color` to the `.field-label` span.

## 5. Verification & Testing
- **Real Data Preview**: The editor toolbar now includes a dropdown of the 30 most recent reservations.
- **Workflow**:
    1. Select a reservation from the dropdown.
    2. Click "Preview PDF".
    3. The system saves the current layout and generates a PDF using the selected reservation's actual data.

---

## Current Status
- [ ] **Migration**: Pending (Needs execution)
- [x] **Service Logic**: Implemented in `BarcodeRenderer` and `BookingPassService`.
- [x] **JS Editor**: Logic implemented in `booking-pass-editor.js`.
- [x] **Views**: `pdf-custom.blade.php` and `edit.blade.php` updated.

## Next Step
Execute the pending migration to enable the new database fields.
```powershell
php artisan migrate
```
