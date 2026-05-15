# Issue #003: Booking Pass Template — Field Tambahan & Perbaikan Editor

**Type**: Feature + Bug Fix
**Status**: Resolved
**Priority**: High
**Date**: 2026-05-14
**Phase**: Phase 10 — Reservation System

---

## Problem

Booking Pass Template editor kekurangan field konfigurasi penting, style panel tidak lengkap, dan barcode di output PDF tidak muncul sama sekali.

### Sub-issues

#### 3a — Field konfigurasi template belum ada
Template tidak punya:
- Mode QR vs Barcode (template-level)
- Tipe template: Self Service / Internal / Partner
- Variabel `product_name` belum ada di sidebar editor

#### 3b — Style panel tidak lengkap
Style panel hanya punya font size global dan warna teks. Tidak bisa:
- Atur ukuran font **label** secara terpisah
- Atur warna **label** secara terpisah
- Set **output type per field** (Teks / QR Code / Barcode)

#### 3c — Barcode tidak muncul di PDF output
Tiga penyebab berlapis:
1. `display:inline-block` — DomPDF tidak support, elemen hilang/collapse
2. Unicode block chars (`█░`) dipakai sebagai "barcode" — font DomPDF (DejaVu Sans) tidak punya karakter ini, render jadi kotak kosong
3. Bukan barcode nyata — hanya tiruan teks, tidak ada batang Code 39 yang valid

#### 3d — Preview hanya pakai data dummy
Tidak ada cara preview PDF dengan data reservasi nyata, sehingga hasil akhir tidak bisa divalidasi sebelum deploy.

---

## Solution

### Migration
File: `database/migrations/2026_05_14_000001_add_type_fields_to_booking_pass_templates.php`
- Tambah kolom `qr_type` ENUM(`qr`, `barcode`) default `qr`
- Tambah kolom `template_type` ENUM(`self_service`, `internal`, `partner`) nullable
- Guard `Schema::hasColumn()` agar aman dijalankan berulang

### Model & Controller
- `BookingPassTemplate::$fillable` + kedua kolom baru
- `store()` + `update()` validasi & simpan `qr_type`, `template_type`
- `edit()` pass `$recentReservations` (30 terakhir) ke view
- `previewPdf()` terima optional `?reservation_id=X` untuk preview real data

### Service
File: `app/Services/BookingPassService.php`
- Tambah key `product_name` di `renderFieldValues()` dan `renderDummyValues()`
- `renderQrOrBarcode()` untuk barcode → delegasi ke `BarcodeRenderer::code39()`
- Tambah method `previewWithReservation(template, reservation)` untuk preview real data

### BarcodeRenderer (baru)
File: `app/Services/BarcodeRenderer.php`
- Pure PHP Code 39 SVG generator, **tanpa library eksternal**
- Encoding table lengkap (0-9, A-Z, `-`, `.`, ` `, `$`, `/`, `+`, `%`, `*`)
- Validasi: 3 wide elements per karakter (sesuai standar Code 39)
- Output: `<svg>` inline dengan `<rect>` hitam — DomPDF render natively
- Tidak bergantung pada font → tidak ada masalah charset
- Tested: `RES-20260513-0001` → 95 bars, SVG valid

### Style Panel (`edit.blade.php`)
Tambah 3 kontrol baru di `#bp-style-panel`:
- `sp-label-font-size` — ukuran font label (px), terpisah dari nilai
- `sp-label-color` — warna label (color picker)
- `sp-output-type` — select: Teks / QR Code / Barcode (per field)
- Rename "Warna Teks" → "Warna Nilai" (lebih jelas)

### Editor JS (`booking-pass-editor.js`)
- `product_name` masuk `PREVIEW_VALUES`
- `applyLabelStyles()` — apply `label_font_size` + `label_color` ke elemen label di canvas
- `applyBoxStyles()` — border warna berbeda per output_type (ungu=QR, oranye=barcode)
- Canvas preview: output QR → `▣ QR: nilai`, barcode → `▐▌ BARCODE: nilai`
- `selectBox()` populate 3 input baru ke style panel
- Field default saat drop: `output_type: 'text'`, `label_font_size: 9`, `label_color: '#64748b'`

### PDF View (`pdf-custom.blade.php`)
- `label_font_size` + `label_color` di-apply ke `<span class="field-label">` via inline style
- `output_type = 'barcode'` → `BarcodeRenderer::code39(strip_tags($value), 2, 55)`
- `output_type = 'qr'` → styled table box (placeholder, tanpa library QR)
- `output_type = 'text'` → render normal

### Preview Real Data (toolbar editor)
- Dropdown di toolbar: 30 reservasi terakhir
- Pilih reservasi → `?reservation_id=X` → PDF pakai data nyata
- `?t=timestamp` di URL → browser tidak cache PDF lama
- Fallback ke dummy jika tidak dipilih

---

## Files Changed

| File | Perubahan |
|------|-----------|
| `database/migrations/2026_05_14_000001_*.php` | Baru — kolom `qr_type`, `template_type` |
| `app/Models/BookingPassTemplate.php` | `$fillable` + 2 kolom baru |
| `app/Services/BarcodeRenderer.php` | Baru — Code 39 SVG generator |
| `app/Services/BookingPassService.php` | `product_name`, `BarcodeRenderer`, `previewWithReservation` |
| `app/Http/Controllers/BookingPassController.php` | `edit()` + `previewPdf()` |
| `public/js/booking-pass-editor.js` | Style panel wiring, label styles, output type, preview |
| `resources/views/booking-pass-templates/create.blade.php` | Field `template_type`, `qr_type` |
| `resources/views/booking-pass-templates/edit.blade.php` | Style panel, toolbar dropdown, `product_name` var |
| `resources/views/booking-pass-templates/index.blade.php` | Kolom Tipe + QR/Barcode di tabel |
| `resources/views/booking-pass/pdf-custom.blade.php` | Per-field output_type, label styles, BarcodeRenderer |

---

## Root Cause Analysis

| Bug | Root Cause |
|-----|------------|
| Barcode hilang di PDF | DomPDF tidak support `display:inline-block` |
| Barcode visual rusak | Font DomPDF tidak punya Unicode block chars |
| "Barcode" bukan barcode | Implementasi awal hanya tiruan teks, bukan Code 39 |
| Preview tidak realistis | Tidak ada mekanisme inject real reservation data |

---

## Notes
- QR Code nyata (scannable) belum diimplementasi — butuh library `endroid/qr-code` atau `chillerlan/php-qrcode`. Saat ini QR field render sebagai placeholder box.
- `BarcodeRenderer::code39()` support Code 39 standard. Untuk barcode dengan checksum atau Code 128, perlu extend class ini.
- Migration menggunakan `Schema::hasColumn()` guard — aman untuk production hosting tanpa SSH.
