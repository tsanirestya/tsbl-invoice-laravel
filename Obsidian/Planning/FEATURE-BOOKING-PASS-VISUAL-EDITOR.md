---
title: Feature Planning — Booking Pass Visual Template Editor
status: DRAFT
created: 2026-05-13
phase: 10d
depends_on: FEATURE-BOOKING-PASS (Phase 10c)
---

# Feature Planning — Booking Pass Visual Template Editor (Phase 10d)

> Kembali ke: [[FEATURE-BOOKING-PASS]]
> Related: [[FEATURE-RESERVATION-SYSTEM]]

---

## Tujuan

Upgrade template editor dari JSON input mentah menjadi **visual drag-and-drop editor** di browser. Admin upload background image → lihat preview template → drag kotak variabel ke posisi bebas → simpan → PDF generated dengan layout persis seperti di editor.

---

## Konsep Inti

```
+------------------------------------------+
|  [SIDEBAR VARIABEL]  |  [CANVAS PREVIEW]  |
|                      |                    |
|  □ Nama Tamu         |  [BG IMAGE]        |
|  □ No. Reservasi     |   ┌──────────┐     |
|  □ Tanggal Kunjungan |   │Nama Tamu │←drag|
|  □ Partner           |   └──────────┘     |
|  □ Total Amount      |      ┌───────────┐ |
|  □ QR Code           |      │No. Reserv.│ |
|  □ Tabel Produk      |      └───────────┘ |
|  □ Custom Field 1    |                    |
|  □ ...               |                    |
|                      |                    |
|  [+ Add Custom Field]|  [Save Template]   |
+------------------------------------------+
```

- Canvas = preview background template (image yang diupload)
- Sidebar = daftar variabel dari reservation yang bisa di-drag
- Setiap variabel jadi **draggable box** di canvas
- Posisi disimpan sebagai **persentase x/y** dari canvas (bukan pixel absolut — responsif di berbagai ukuran PDF)
- Saat generate PDF, DomPDF render HTML dengan `position: absolute` berdasarkan koordinat tersimpan

---

## Available Variables (dari Reservation)

### Core Fields
| Key | Label | Tipe |
|-----|-------|------|
| `reservation_no` | No. Reservasi | text |
| `guest_name` | Nama Tamu | text |
| `guest_country` | Negara Asal | text |
| `visit_date` | Tanggal Kunjungan | date |
| `partner_name` | Nama Partner | text |
| `payment_method` | Metode Pembayaran | text |
| `payment_channel` | Channel Pembayaran | text |
| `total_amount` | Total Amount | currency |
| `status` | Status Reservasi | badge |
| `notes` | Catatan | text |
| `created_at` | Tanggal Dibuat | datetime |

### Items Block
| Key | Label | Tipe |
|-----|-------|------|
| `items_table` | Tabel Produk (full table) | table |
| `items_list` | Daftar Produk (simple list) | list |

### Special Elements
| Key | Label | Tipe |
|-----|-------|------|
| `qr_code` | QR Code | image |
| `logo` | Logo Perusahaan | image |

### booking_pass_data Custom Fields
Admin bisa tambah custom key bebas dari `booking_pass_data` JSON:
- `booking_pass_data.voucher_code` → "Kode Voucher"
- `booking_pass_data.hotel_room` → "No. Kamar"
- `booking_pass_data.special_notes` → "Catatan Khusus"
- Dan seterusnya (unlimited, defined per template)

---

## Struktur Data — field_mapping JSON (Updated)

Upgrade dari struktur lama (string position) ke **koordinat persentase + styling**:

```json
{
  "canvas": {
    "width_px": 794,
    "height_px": 1123,
    "background_file": "templates/partner_1_bg.jpg"
  },
  "fields": [
    {
      "key": "reservation_no",
      "label": "No. Reservasi",
      "x_pct": 62.5,
      "y_pct": 8.3,
      "width_pct": 35.0,
      "font_size": 13,
      "font_weight": "bold",
      "color": "#1a1a1a",
      "align": "left",
      "visible": true
    },
    {
      "key": "guest_name",
      "label": "Nama Tamu",
      "x_pct": 5.0,
      "y_pct": 22.0,
      "width_pct": 50.0,
      "font_size": 16,
      "font_weight": "bold",
      "color": "#000000",
      "align": "left",
      "visible": true
    },
    {
      "key": "qr_code",
      "label": "QR Code",
      "x_pct": 72.0,
      "y_pct": 72.0,
      "width_pct": 20.0,
      "font_size": null,
      "font_weight": null,
      "color": null,
      "align": null,
      "visible": true
    },
    {
      "key": "items_table",
      "label": "Tabel Produk",
      "x_pct": 5.0,
      "y_pct": 45.0,
      "width_pct": 90.0,
      "font_size": 10,
      "font_weight": "normal",
      "color": "#333333",
      "align": "left",
      "visible": true
    }
  ],
  "custom_fields": [
    {
      "key": "booking_pass_data.voucher_code",
      "label": "Kode Voucher",
      "x_pct": 5.0,
      "y_pct": 85.0,
      "width_pct": 40.0,
      "font_size": 11,
      "font_weight": "normal",
      "color": "#444444",
      "align": "left",
      "visible": true
    }
  ]
}
```

---

## Arsitektur Visual Editor

### Tech Stack Editor
- **Drag engine**: HTML5 native drag-and-drop API (zero dependency) ATAU `interact.js` (CDN, lebih smooth)
- **Canvas**: `<div>` relatif berisi `<img>` background + draggable variable boxes di atasnya
- **State management**: Vanilla JS object di memory → serialize ke JSON saat save
- **Preview**: Real-time update posisi saat drag
- **Save**: AJAX POST ke `PUT /booking-pass-templates/{id}` dengan JSON payload

### Alur Editor

```
1. Admin buka form edit template
2. Background image ditampilkan di canvas
3. Variabel yang sudah ada posisinya → muncul sebagai box di posisi tersimpan
4. Variabel belum dipasang → ada di sidebar (greyed out)
5. Admin drag variabel dari sidebar ke canvas
   → box muncul di canvas, posisi terekam (x_pct, y_pct)
6. Admin bisa:
   - Drag reposition box yang sudah ada
   - Resize box (handle di pojok kanan bawah)
   - Klik box → edit font size, warna, alignment di panel kecil
   - Double-klik box → remove dari canvas (balik ke sidebar)
7. Klik [Save] → kirim JSON ke server
8. Klik [Preview Booking Pass] → generate sample PDF dengan data dummy
```

### Component Breakdown

```
resources/views/booking-pass-templates/
├── index.blade.php          → list semua template
├── create.blade.php         → form dasar + upload BG image
├── edit.blade.php           → main visual editor (drag canvas)
├── _variable_sidebar.blade.php  → sidebar daftar variabel
├── _canvas.blade.php        → canvas drag area
└── _field_style_panel.blade.php → panel klik box (font/warna)

public/js/
└── booking-pass-editor.js   → drag logic, state, save/load

resources/views/booking-pass/
└── pdf.blade.php            → PDF render template (pakai field_mapping)
```

---

## Canvas Implementation Detail

### HTML Structure

```html
<!-- Canvas wrapper -->
<div id="bp-canvas" 
     style="position: relative; width: 794px; height: 1123px; overflow: hidden;"
     data-width="794" data-height="1123">
  
  <!-- Background image -->
  <img src="{{ $template->template_file_url }}" 
       style="position: absolute; width: 100%; height: 100%; object-fit: cover;" />
  
  <!-- Draggable field boxes (rendered from saved field_mapping) -->
  @foreach($template->field_mapping['fields'] ?? [] as $field)
    @if($field['visible'])
    <div class="bp-field-box"
         data-key="{{ $field['key'] }}"
         style="position: absolute;
                left: {{ $field['x_pct'] }}%;
                top: {{ $field['y_pct'] }}%;
                width: {{ $field['width_pct'] }}%;
                font-size: {{ $field['font_size'] }}px;
                font-weight: {{ $field['font_weight'] }};
                color: {{ $field['color'] }};"
         draggable="true">
      {{ $field['label'] }}: [{{ strtoupper($field['key']) }}]
    </div>
    @endif
  @endforeach
  
</div>

<!-- Drop zone indicator (shown on dragover) -->
<div id="bp-drop-indicator" class="bp-drop-indicator d-none"></div>
```

### JavaScript State Model

```javascript
// booking-pass-editor.js
const EditorState = {
  canvas: { width: 794, height: 1123 },
  fields: [],        // array of field objects (current state)
  activeField: null, // currently selected box

  // Serialize to JSON for save
  toJSON() {
    return {
      canvas: this.canvas,
      fields: this.fields.filter(f => f.onCanvas),
      custom_fields: this.fields.filter(f => f.isCustom && f.onCanvas),
    };
  },

  // Load from existing field_mapping
  fromJSON(data) {
    this.canvas = data.canvas;
    this.fields = data.fields ?? [];
  }
};

// Drag handlers
function onDragStart(e) { /* set dragData = field key */ }
function onDrop(e) {
  const rect = canvas.getBoundingClientRect();
  const x_pct = ((e.clientX - rect.left) / rect.width) * 100;
  const y_pct = ((e.clientY - rect.top) / rect.height) * 100;
  placeField(dragData.key, x_pct, y_pct);
}

// Save
document.getElementById('btn-save').addEventListener('click', () => {
  fetch(`/booking-pass-templates/${templateId}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
    body: JSON.stringify({ field_mapping: EditorState.toJSON() })
  });
});
```

---

## PDF Generation (DomPDF)

Saat generate PDF, gunakan `field_mapping` untuk render elemen dengan posisi absolut:

```html
<!-- resources/views/booking-pass/pdf.blade.php -->
<div style="position: relative; width: 794px; height: 1123px; margin: 0; padding: 0;">
  
  {{-- Background --}}
  @if($template && $template->template_file)
    <img src="{{ public_path('storage/' . $template->template_file) }}"
         style="position: absolute; width: 100%; height: 100%;" />
  @endif

  {{-- Render fields berdasarkan field_mapping --}}
  @foreach($fields as $field)
    @if($field['visible'])
    <div style="
      position: absolute;
      left: {{ $field['x_pct'] }}%;
      top: {{ $field['y_pct'] }}%;
      width: {{ $field['width_pct'] }}%;
      font-size: {{ $field['font_size'] }}px;
      font-weight: {{ $field['font_weight'] }};
      color: {{ $field['color'] }};
    ">
      {!! $renderedValues[$field['key']] ?? '' !!}
    </div>
    @endif
  @endforeach

</div>
```

---

## BookingPassService — Render Logic

```php
// app/Services/BookingPassService.php

public function renderFieldValues(Reservation $reservation): array
{
    $items = $reservation->items;
    $bpData = $reservation->booking_pass_data ?? [];

    return [
        'reservation_no'   => $reservation->reservation_no,
        'guest_name'       => $reservation->guest_name,
        'guest_country'    => $reservation->guest_country,
        'visit_date'       => $reservation->visit_date->format('d M Y'),
        'partner_name'     => $reservation->partner?->name ?? $reservation->partner_name_input,
        'payment_method'   => $reservation->payment_method,
        'payment_channel'  => $reservation->payment_channel,
        'total_amount'     => 'Rp ' . number_format($reservation->total_amount, 0, ',', '.'),
        'status'           => $reservation->status,
        'notes'            => $reservation->notes,
        'created_at'       => $reservation->created_at->format('d M Y H:i'),
        'items_table'      => $this->renderItemsTable($items),
        'items_list'       => $this->renderItemsList($items),
        'qr_code'          => $this->renderQrCode($reservation->reservation_no),
        'logo'             => $this->renderLogo(),

        // booking_pass_data custom fields — semua key diprefix
        ...collect($bpData)->mapWithKeys(
            fn($v, $k) => ["booking_pass_data.{$k}" => $v]
        )->all(),
    ];
}
```

---

## Custom Fields Management

Admin bisa tambah/hapus custom field langsung dari editor:

### UI Flow
```
Sidebar → [+ Tambah Custom Field]
  → Modal popup:
    - Label      : [Kode Voucher      ]
    - Key        : [voucher_code      ]  (auto-slugify dari label)
    - Preview    : booking_pass_data.voucher_code
    → [Tambah]
  → Field baru muncul di sidebar, siap di-drag ke canvas
```

### Saat reservasi dibuat/edit
- Form reservasi punya section "Custom Booking Pass Fields"
- Fields yang tampil = definisi dari template yang dipilih
- User isi nilai per field
- Disimpan ke `reservations.booking_pass_data` JSON

---

## Routes (Updated)

```php
// routes/web.php

Route::prefix('booking-pass-templates')->name('booking-pass-templates.')->group(function () {
    Route::get('/',              [BookingPassController::class, 'index'])->name('index');
    Route::get('/create',        [BookingPassController::class, 'create'])->name('create');
    Route::post('/',             [BookingPassController::class, 'store'])->name('store');
    Route::get('/{id}/edit',     [BookingPassController::class, 'edit'])->name('edit');
    Route::put('/{id}',          [BookingPassController::class, 'update'])->name('update');
    Route::delete('/{id}',       [BookingPassController::class, 'destroy'])->name('destroy');

    // Editor AJAX endpoints
    Route::post('/{id}/upload-bg',    [BookingPassController::class, 'uploadBackground'])->name('upload-bg');
    Route::put('/{id}/field-mapping', [BookingPassController::class, 'updateFieldMapping'])->name('update-mapping');
    Route::get('/{id}/preview',       [BookingPassController::class, 'previewPdf'])->name('preview');
});

// Booking pass generation (di ReservationController)
Route::get('/reservations/{id}/booking-pass',        [ReservationController::class, 'bookingPassPreview'])->name('reservations.booking-pass');
Route::get('/reservations/{id}/booking-pass/pdf',    [ReservationController::class, 'bookingPassPdf'])->name('reservations.booking-pass.pdf');
Route::post('/reservations/{id}/booking-pass/upload',[ReservationController::class, 'bookingPassUpload'])->name('reservations.booking-pass.upload');
```

---

## Implementasi Steps

```
Agent: Senior Developer
File: agency-agents/engineering/engineering-senior-developer.md
Reason: Laravel backend + vanilla JS drag editor + DomPDF integration
```

### Step 1 — Database & Model
- [ ] Pastikan migration `booking_pass_templates` sudah jalan
- [ ] Verify `field_mapping` JSON column ada dan castable
- [ ] Seed satu default template (partner_id = null)

### Step 2 — Template CRUD
- [ ] `BookingPassController` — index, create, store, edit, update, destroy
- [ ] Form create: template_name, partner selector, is_active toggle
- [ ] Background upload via `POST /{id}/upload-bg` (separate dari form utama)
- [ ] Simpan ke `storage/booking-pass-templates/`

### Step 3 — Visual Editor (Core)
- [ ] `edit.blade.php` dengan layout 2-kolom (sidebar + canvas)
- [ ] Canvas render background image
- [ ] Sidebar render daftar variabel (Available variables list di atas)
- [ ] `booking-pass-editor.js` — drag-and-drop logic
- [ ] AJAX save `field_mapping` ke server
- [ ] Load state dari DB saat halaman dibuka

### Step 4 — Field Style Panel
- [ ] Klik field box → slide-in panel kecil
- [ ] Controls: font size, font weight, color picker, text align
- [ ] Apply langsung ke box preview (CSS)
- [ ] State tersimpan di EditorState

### Step 5 — Custom Fields
- [ ] Modal "Tambah Custom Field" di sidebar
- [ ] Auto-slugify label → key
- [ ] Field ditambah ke EditorState dan sidebar
- [ ] Saat save, custom_fields ikut tersimpan di field_mapping JSON

### Step 6 — PDF Generation
- [ ] `BookingPassService::generate(Reservation)`
- [ ] `BookingPassService::renderFieldValues(Reservation)` → array nilai
- [ ] `resources/views/booking-pass/pdf.blade.php` — absolute positioning dari field_mapping
- [ ] DomPDF render → simpan ke storage
- [ ] Update `reservations.booking_pass_file`

### Step 7 — Preview & Integration
- [ ] `GET /{id}/preview` → generate sample PDF dengan dummy data
- [ ] Reservasi form: pilih template + isi custom fields
- [ ] Setelah reservasi CONFIRMED → auto-generate booking pass
- [ ] Di detail reservasi → tombol "Download Booking Pass"

### Step 8 — Responsive Canvas
- [ ] Canvas di editor = fixed 794px (A4 width) scroll horizontal di mobile
- [ ] Warning jika layar <794px: "Editor optimal di desktop"
- [ ] Posisi disimpan % → PDF tetap konsisten di semua ukuran

---

## UI/UX Specs

### Editor Page
- Sidebar: 280px fixed width, scrollable
- Canvas: 794×1123px (A4), surrounded by gray background
- Field boxes: semi-transparent background + border dashed, opacity 0.85
- Selected field: border solid biru + resize handle
- Drag from sidebar: ghost image (field label)
- Drop indicator: crosshair/dotted outline di drop position
- Zoom control: 50% / 75% / 100% (scale canvas, koordinat tetap %)

### Toolbar atas canvas
```
[Undo] [Redo] | [Grid On/Off] | [Zoom: 100%▾] | [Preview PDF] | [Save]
```

### Field Box Appearance di Editor
```
┌─────────────────────────┐
│ Nama Tamu               │  ← label
│ [guest_name]            │  ← key (small, gray)
└─────────────────────────┘
```

---

## Edge Cases & Constraints

| Case | Handling |
|------|----------|
| Template tanpa background | Canvas = putih, field tetap bisa diplace |
| Field keluar batas canvas | Clamp x/y agar tetap dalam 0-100% |
| Template dipakai reservasi sudah ada | Edit template tidak re-generate PDF lama (hanya berlaku untuk reservasi baru) |
| Dua field di posisi sama | Allowed, user tanggung jawab — tampilkan warning jika overlap >80% |
| Custom field key duplikat | Validasi di JS: block tambah jika key sudah ada |
| PDF render beda dari preview browser | Gunakan font yang DomPDF support (Arial, Helvetica, sans-serif) |
| background image besar (>5MB) | Resize/compress saat upload, max 5MB, format: JPG/PNG |

---

## Definition of Done

- [ ] Admin bisa upload background gambar ke template
- [ ] Visual editor menampilkan canvas dengan background image
- [ ] Semua variabel reservasi tersedia di sidebar sebagai draggable item
- [ ] Drag variabel ke canvas → box muncul di posisi drop
- [ ] Reposition box dengan drag di canvas
- [ ] Klik box → edit font size, warna, alignment
- [ ] Tambah custom field via modal → langsung bisa di-drag ke canvas
- [ ] Simpan field_mapping ke DB via AJAX tanpa reload
- [ ] Preview PDF menampilkan booking pass sesuai layout editor
- [ ] Generate PDF dari reservasi CONFIRMED menggunakan template yang dipilih
- [ ] PDF layout mencerminkan posisi variabel dari editor
