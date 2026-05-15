# TODO: Revisi Reservation Form — Pax-Based Pricing

- **Date:** 2026-05-15
- **Status:** ✅ DONE — All phases complete
- **Branch:** `feat/phase-N-reservation-pax-form`

---

## Latar Belakang

Form reservasi saat ini menggunakan model lama: pilih produk 1 per 1, isi qty + harga per row.  
Tujuan revisi: UX lebih natural — pilih **activity** (parents_name), isi jumlah tamu per tipe (Adult/Child/Baby), harga otomatis kalkulasi.

---

## Audit Data (2026-05-15)

- Total produk: **112** — semua punya pola `" - "` (0 anomali)
- Klasifikasi pax_type hasil deteksi regex:

| pax_type | Jumlah | Contoh Parent |
|----------|--------|---------------|
| ADULT    | ~45    | Theme Park Package, Fast Track Package |
| CHILD    | ~30    | Theme Park Package, VIP Play Package |
| BUNDLE   | ~14    | Fast Track Play Package (Fusion Holiday), Family Package (2A&2C) |
| TICKET   | ~13    | I-Fly Package, Room Bundling, Yummy Play, Open Booth |

- Case inconsistency (UPPERCASE vs Title Case) bukan masalah — sudah dipisah oleh payment mode filter
- `parents_name` akan disimpan **UPPERCASE** semua

---

## Logic Form Baru

```
User pilih Activity (parents_name)
        ↓
Deteksi market dari guest_country
  → non-Indonesia = FOREIGN
  → Indonesia     = DOMESTIC
        ↓
FOREIGN:
  ├─ pax_type ADULT+CHILD ada → [Adult qty] [Child qty] [Baby qty - free]
  │     Buat 2 reservation_items:
  │       - adult_product × adult_qty
  │       - child_product × child_qty
  │
  ├─ pax_type BUNDLE saja → [Dropdown varian bundle] [Qty] [Baby qty - free]
  │     Setelah pilih varian: tampil komposisi read-only (mis: "2 Adult + 1 Child")
  │     Buat 1 reservation_item: bundle_product × qty
  │
  └─ pax_type TICKET → [Total Qty] [Baby qty - free]
        Buat 1 reservation_item: ticket_product × qty

DOMESTIC (semua parent):
  → [Jumlah Tiket] [Baby qty - free]
  → Buat 1 reservation_item: product pertama yang match × qty

Baby:
  - Selalu free (price = 0), tidak binding ke product
  - Disimpan di reservations.pax_babies
  - Ditampilkan di summary dan booking pass sebagai info
  - Buat reservation_item terpisah price=0 jika pax_babies > 0 (untuk audit trail)
```

---

## Rencana Implementasi

### FASE 1 — Database & Model

#### Task 1.1 — Migration: tambah kolom ke `products`
```php
// database/migrations/xxxx_add_pax_columns_to_products.php
Schema::hasColumn('products', 'parents_name') || Schema::table('products', function (Blueprint $table) {
    $table->string('parents_name', 255)->nullable()->after('product_name');
    $table->enum('pax_type', ['ADULT', 'CHILD', 'BUNDLE', 'TICKET'])->nullable()->after('parents_name');
    $table->tinyInteger('bundle_adult_count')->default(0)->after('pax_type');
    $table->tinyInteger('bundle_child_count')->default(0)->after('bundle_adult_count');
    $table->index('parents_name');
    $table->index(['parents_name', 'pax_type', 'sub_payment_mode', 'market_type']);
});
```

#### Task 1.2 — Migration: tambah `pax_babies` ke `reservations`
```php
Schema::hasColumn('reservations', 'pax_babies') || Schema::table('reservations', function (Blueprint $table) {
    $table->tinyInteger('pax_babies')->default(0)->after('pax_kids');
});
```

#### Task 1.3 — Seeder / artisan command: populate `parents_name`, `pax_type`, bundle counts untuk existing products

Logic PHP:
```php
foreach (Product::all() as $p) {
    [$parent, $type] = explode(' - ', $p->product_name, 2) + ['', ''];
    $parent = strtoupper(trim($parent));
    $type   = trim($type);

    // Bundle detection
    $isBundle = preg_match('/\d+\s*A\s*[&\s]\s*\d+\s*C/i', $type)
             || preg_match('/\d+\s*ADULT/i', $type)
             || preg_match('/ADULT[\/&].+CHILD/i', $type);

    if ($isBundle) {
        $paxType = 'BUNDLE';
        preg_match('/(\d+)\s*A\s*[&\s]\s*(\d+)\s*C/i', $type, $m1);
        preg_match('/(\d+)\s*ADULT/i', $type, $m2);
        preg_match('/(\d+)\s*CHILD/i', $type, $m3);
        $adultCount = $m1[1] ?? $m2[1] ?? 0;
        $childCount = $m1[2] ?? $m3[1] ?? 0;
    } else {
        $hasAdult = stripos($type, 'adult') !== false;
        $hasChild = stripos($type, 'child') !== false;
        $paxType  = $hasAdult ? 'ADULT' : ($hasChild ? 'CHILD' : 'TICKET');
        $adultCount = $childCount = 0;
    }

    $p->update([
        'parents_name'       => $parent,
        'pax_type'           => $paxType,
        'bundle_adult_count' => $adultCount,
        'bundle_child_count' => $childCount,
    ]);
}
```

#### Task 1.4 — Product model: update $fillable + boot hook
- Tambah `parents_name`, `pax_type`, `bundle_adult_count`, `bundle_child_count` ke `$fillable`
- Tambah `static::saving()` boot hook agar auto-set saat product di-create/edit

#### Task 1.5 — Reservation model: update $fillable + $casts
- Tambah `pax_babies` ke `$fillable`
- Tambah `'pax_babies' => 'integer'` ke `$casts`

---

### FASE 2 — Controller

#### Task 2.1 — `ReservationController::create()`

Kirim products ke view sebagai JSON terstruktur:
```php
$products = Product::where('is_active', true)
    ->select('id','product_name','parents_name','pax_type',
             'sub_payment_mode','market_type','publish_rate',
             'nett_price','komisi','payment_mode',
             'bundle_adult_count','bundle_child_count')
    ->get();

// Struktur: grouped[parents_name][sub_payment_mode][market_type] = [products]
$groupedProducts = $products->groupBy(['parents_name', 'sub_payment_mode', 'market_type']);
```

#### Task 2.2 — `ReservationController::store()` — logic baru

Validation tambahan:
```php
'pax_babies' => 'nullable|integer|min:0',
'items.*.adult_qty'  => 'nullable|integer|min:0',
'items.*.child_qty'  => 'nullable|integer|min:0',
'items.*.bundle_product_id' => 'nullable|exists:products,id',
'items.*.bundle_qty' => 'nullable|integer|min:1',
'items.*.ticket_qty' => 'nullable|integer|min:0',
```

Processing items:
```php
foreach ($request->items as $idx => $item) {
    switch ($item['row_type']) {
        case 'ADULT_CHILD':
            // Buat 2 items: adult + child
            if ($item['adult_qty'] > 0) createItem($adultProduct, $item['adult_qty'], $adultPrice);
            if ($item['child_qty'] > 0) createItem($childProduct, $item['child_qty'], $childPrice);
            break;
        case 'BUNDLE':
            createItem($bundleProduct, $item['bundle_qty'], $bundlePrice);
            break;
        case 'TICKET':
        case 'DOMESTIC':
            createItem($ticketProduct, $item['ticket_qty'], $ticketPrice);
            break;
    }
}
// Baby item (free)
if ($request->pax_babies > 0) {
    createBabyItem($reservation, $request->pax_babies);
}
// Update reservation pax fields
$reservation->update([
    'pax_adults' => $totalAdults,
    'pax_kids'   => $totalKids,
    'pax_babies' => $request->pax_babies,
]);
```

---

### FASE 3 — View

#### Task 3.1 — `create.blade.php`: redesign item rows

Struktur row baru (per activity):

```
[Row]
┌─────────────────────────────────────────────────────────────┐
│ Activity: [Dropdown parents_name, filtered]                 │
│                                                             │
│ [IF FOREIGN + ADULT_CHILD type]                             │
│   Adult: [___]  Child: [___]  Baby: [___]  (Baby = FREE)   │
│                                                             │
│ [IF FOREIGN + BUNDLE type]                                  │
│   Varian: [Dropdown bundle variants]  Qty: [___]            │
│   Komposisi: "2 Adult + 1 Child" (read-only badge)          │
│   Baby: [___]  (FREE)                                       │
│                                                             │
│ [IF FOREIGN + TICKET type]                                  │
│   Qty: [___]  Baby: [___]  (FREE)                           │
│                                                             │
│ [IF DOMESTIC]                                               │
│   Jumlah Tiket: [___]  Baby: [___]  (FREE)                  │
│                                                             │
│ Harga: Rp [auto-fill]    Subtotal: Rp [calculated]          │
└─────────────────────────────────────────────────────────────┘
[+ Tambah Activity]
```

Summary panel kanan:
```
Adult  × N  @ Rp xxx  =  Rp xxx
Child  × N  @ Rp xxx  =  Rp xxx
Baby   × N  FREE      =  Rp 0
─────────────────────────────────
GROSS TOTAL            Rp xxx
Komisi                 Rp xxx
NETT TOTAL             Rp xxx
```

#### Task 3.2 — JavaScript logic

```javascript
// filterActivities(paymentMethod, guestCountry)
// → filter parents_name berdasarkan sub_payment_mode + market_type

// onActivitySelect(parentsName, marketType)
// → deteksi row_type: ADULT_CHILD / BUNDLE / TICKET / DOMESTIC
// → tampilkan input yang sesuai
// → isi harga dari product data

// onBundleVariantSelect(productId)
// → extract bundle_adult_count, bundle_child_count
// → tampilkan komposisi read-only

// recalcRow() → qty × price = subtotal
// recalcTotal() → sum all rows + baby summary
```

#### Task 3.3 — `show.blade.php`: tampilkan pax_babies di detail reservasi

#### Task 3.4 — `edit.blade.php`: tambah field pax_babies (editable)

---

### FASE 4 — Testing & Cleanup

#### Task 4.1 — Static code review (2026-05-15)
- [x] Foreign + ADULT_CHILD — logic correct, adult/child product lookup OK
- [x] Foreign + BUNDLE (Fast Track Play Package) — BUNDLE prioritized over standalone CHILD ✓
- [x] Foreign + TICKET — GROSS only (no NETT/TICKET products in data) ✓
- [x] Domestic — always renders TICKET row, picks first product ✓
- [x] Multi-activity — sort_order increments correctly ✓
- [x] Baby saja — blocked by items min:1 validation (intentional)
- [x] GROSS vs NETT switch — filter logic correct, refreshAllRows wired to TomSelect onChange ✓

#### Bugs fixed:
- [x] BUG: Double refresh (native change + TomSelect onChange) — removed duplicate listener
- [x] BUG: Empty row (no activity selected) causes confusing server validation error — stripped in submit guard + alert
- [x] BUG: Zero-qty submission creates empty reservation — added JS guard before submit
- [x] BUG: Baby item (audit trail) blocked by product_id NOT NULL — migration added, baby item now created in controller

#### Task 4.2 — Update Obsidian docs setelah selesai

---

## Urutan Pengerjaan

```
1.1 Migration products  →  1.2 Migration reservations
        ↓
1.3 Populate existing data  →  1.4 Product model  →  1.5 Reservation model
        ↓
2.1 Controller create()  →  2.2 Controller store()
        ↓
3.1 View create (form)  →  3.2 JavaScript
        ↓
3.3 View show  →  3.4 View edit
        ↓
4.1 Testing  →  4.2 Obsidian update
```

---

## Risiko & Catatan

| Risiko | Mitigasi |
|--------|----------|
| Parent yang sama punya beberapa harga untuk payment mode berbeda | Filter `sub_payment_mode` sudah handle ini — user hanya lihat yang relevan |
| FAST TRACK PLAY PACKAGE punya CHILD product DAN BUNDLE — bisa clash | Jika ada bundle, prioritaskan tampil bundle dropdown. CHILD standalone tidak muncul terpisah |
| `PLAY & FLY PACKAGE` vs `PLAY AND FLY PACKAGE` — beda parent tapi sama activity | Ini data issue — flag ke user untuk di-standardize di product master |
| Product baru di masa depan dengan nama tidak standar | Boot hook auto-classify; jika pax_type tidak terdeteksi → default TICKET |
