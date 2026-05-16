---
title: Feature Planning — ADMISSION Role + REDEEM Reservation
status: APPROVED
created: 2026-05-16
phase: 12
---

# Feature Planning — ADMISSION Role + REDEEM Reservation (Phase 12)

> Kembali ke: [[MASTER-PLAN]]
> Related: [[FEATURE-RESERVATION-SYSTEM]], [[FEATURE-BOOKING-PASS]], [[FEATURE-FRAUD-DETECTION]]

---

## Latar Belakang

Sistem reservasi saat ini menangani flow dari **booking hingga generate Booking Pass**, tapi tidak ada mekanisme verifikasi kedatangan tamu di gate/pintu masuk. Status reservasi langsung dari `CONFIRMED` ke `COMPLETED` tanpa tracking apakah tamu benar-benar datang dan apakah transaksi aktual sesuai dengan reservasi.

### Masalah
- Tidak ada role khusus untuk petugas gate/admission
- Tidak ada flow "redeem" — verifikasi kedatangan tamu berdasarkan Booking Pass
- Tidak ada tracking apakah transaksi aktual sesuai reservasi (penting untuk rekonsiliasi DSI)
- Status `COMPLETED` tidak membedakan antara "tamu datang" vs "proses selesai"

### Tujuan
- Role **ADMISSION** khusus petugas gate dengan akses terbatas
- Flow **REDEEM** via scan barcode Booking Pass atau input manual Reservation No.
- Tracking **transaction match** — apakah transaksi aktual sesuai reservasi atau berubah
- Dashboard admission dengan metrics real-time hari ini
- Visit date tolerance yang configurable via Settings

---

## Keputusan Desain

| Keputusan | Pilihan | Alasan |
|---|---|---|
| Role baru | `ADMISSION` di `user_status` enum | Konsisten dengan role system existing (ADMIN, FINANCE, SALES, VIEWER) |
| Status baru | `REDEEMED` di `reservations.status` enum | Membedakan "tamu sudah datang" dari "transaksi selesai" (COMPLETED) |
| Scan method | Camera barcode scan + manual input | Camera untuk speed, manual sebagai fallback. Mobile-friendly |
| Barcode library | `html5-qrcode` (CDN) | Supports barcode + QR, no build step needed, works mobile browser |
| Transaction match | Enum field di reservations | Simple, queryable, bisa di-filter di dashboard & reports |
| Visit date tolerance | System Setting (configurable) | Fleksibel — bisa diubah ADMIN tanpa deploy ulang |
| Redirect after login | ADMISSION → `/admission` | Dedicated landing page, tidak perlu lihat menu yang tidak relevan |

---

## Status Flow (Updated)

```
PENDING → CONFIRMED → REDEEMED → COMPLETED
                   ↘ CANCELLED
                   ↘ NO_SHOW
```

- `CONFIRMED` → `REDEEMED`: Petugas admission scan/input reservation no di gate
- `REDEEMED` → `COMPLETED`: Proses transaksi selesai (oleh FINANCE/ADMIN)
- `CONFIRMED` → `NO_SHOW`: Tamu tidak datang (end of day, oleh ADMIN/FINANCE)
- `CONFIRMED` → `CANCELLED`: Reservasi dibatalkan sebelum visit date

---

## Database Changes

### Migration: `add_admission_role_and_redeemed_status`

| # | Change | Detail |
|---|--------|--------|
| 1 | ALTER `users.user_status` | Tambah `ADMISSION` ke enum |
| 2 | ALTER `reservations.status` | Tambah `REDEEMED` ke enum |
| 3 | ADD `reservations.redeemed_at` | `datetime nullable` — waktu redeem |
| 4 | ADD `reservations.redeemed_by` | `unsigned int nullable` FK → users — siapa yang redeem |
| 5 | ADD `reservations.transaction_match` | `enum('MATCH','MISMATCH','PENDING_CHECK') default 'PENDING_CHECK'` |
| 6 | ADD `reservations.transaction_notes` | `text nullable` — catatan jika mismatch |
| 7 | ADD `reservations.actual_items` | `json nullable` — item aktual jika beda dari reservasi |
| 8 | INSERT `settings` | `admission_visit_date_tolerance_days` = `0` |

### Schema Guard
```php
if (!Schema::hasColumn('reservations', 'redeemed_at')) {
    // run migration
}
```

---

## Model Changes

### User.php
```php
// Tambah method
public function isAdmission(): bool
{
    return $this->user_status === 'ADMISSION';
}
```

### Reservation.php
```php
// Update statusBadge() — tambah:
'REDEEMED' => 'info',  // biru muda

// Tambah cast:
'actual_items' => 'array',

// Tambah relationship:
public function redeemer(): BelongsTo
{
    return $this->belongsTo(User::class, 'redeemed_by');
}

// Tambah transaction match badge:
public function transactionMatchBadge(): string
{
    return match ($this->transaction_match) {
        'MATCH' => 'success',
        'MISMATCH' => 'warning',
        'PENDING_CHECK' => 'secondary',
    };
}
```

---

## Controller: AdmissionController

### Methods

| Method | Route | Fungsi |
|--------|-------|--------|
| `dashboard()` | `GET /admission` | Dashboard stats hari ini |
| `scanPage()` | `GET /admission/scan` | Halaman scan/input reservation no |
| `lookup()` | `POST /admission/lookup` | AJAX lookup reservation by no → return JSON detail |
| `redeem()` | `POST /admission/redeem` | Mark reservation as REDEEMED + transaction match |
| `history()` | `GET /admission/history` | List redeemed hari ini dengan filter match/mismatch |

### Business Rules

1. **Hanya `CONFIRMED` yang bisa di-redeem** — status lain tampil warning
2. **Visit date check** — visit_date harus dalam range `today ± tolerance_days` (dari Settings)
3. **Double-redeem prevention** — status `REDEEMED` tidak bisa redeem ulang
4. **Transaction match wajib diisi** saat redeem — MATCH atau MISMATCH
5. **Jika MISMATCH** — `transaction_notes` wajib diisi (catatan apa yang berubah)
6. **`actual_items` opsional** — detail item aktual jika ada perubahan signifikan

---

## Routes

```php
Route::middleware(['auth', 'role:ADMISSION,ADMIN'])->prefix('admission')->group(function () {
    Route::get('/',        [AdmissionController::class, 'dashboard'])->name('admission.dashboard');
    Route::get('/scan',    [AdmissionController::class, 'scanPage'])->name('admission.scan');
    Route::post('/lookup', [AdmissionController::class, 'lookup'])->name('admission.lookup');
    Route::post('/redeem', [AdmissionController::class, 'redeem'])->name('admission.redeem');
    Route::get('/history', [AdmissionController::class, 'history'])->name('admission.history');
});
```

---

## Views

### 1. `admission/dashboard.blade.php`
- **Stats cards:**
  - Confirmed (belum datang) — count reservasi CONFIRMED, visit_date hari ini
  - Redeemed (sudah masuk) — count REDEEMED hari ini
  - Match — count REDEEMED + transaction_match = MATCH
  - Mismatch — count REDEEMED + transaction_match = MISMATCH
  - Total PAX In — sum (pax_adults + pax_kids) yang sudah REDEEMED
- **Quick scan button** — link ke scan page
- **Recent activity** — 10 redeem terakhir hari ini

### 2. `admission/scan.blade.php`
- **Camera scanner area** — html5-qrcode, baca barcode dari Booking Pass
- **Manual input field** — ketik reservation_no, tombol "Cari"
- **Result panel** (AJAX-driven):
  - Guest name, visit_date, reservation_type
  - PAX: adults / kids / babies
  - Items list (product_name, qty, price)
  - Status badge
  - **Jika CONFIRMED:**
    - Tombol [REDEEM]
    - Radio: Sesuai Reservasi (MATCH) / Tidak Sesuai (MISMATCH)
    - Textarea catatan (required jika MISMATCH)
  - **Jika REDEEMED:** Info "Sudah di-redeem" + waktu + oleh siapa
  - **Jika CANCELLED/NO_SHOW:** Warning badge, tidak bisa redeem
- **Auto-focus kembali ke input** setelah redeem sukses (ready for next guest)

### 3. `admission/history.blade.php`
- **Filter:** Match / Mismatch / All
- **Table:** Reservation No, Guest, PAX, Redeemed At, Match Status, Notes
- **Scope:** Hanya hari ini (default), bisa pilih tanggal lain

---

## Barcode Scanner Integration

### Library: `html5-qrcode` via CDN
```html
<script src="https://unpkg.com/html5-qrcode"></script>
```

### Flow
1. User klik "Buka Kamera"
2. `Html5Qrcode.start()` — request camera permission
3. Scan barcode dari Booking Pass → dapat reservation_no
4. Auto-trigger lookup AJAX
5. Tampilkan result panel
6. Setelah redeem, kamera tetap aktif (ready scan next)

### Fallback
- Jika kamera tidak available → show info, manual input tetap tersedia
- Desktop browser → manual input primary, kamera secondary

---

## Admission Role — Access Matrix

| Feature | ADMISSION | ADMIN | FINANCE | SALES | VIEWER |
|---------|-----------|-------|---------|-------|--------|
| Admission Dashboard | v | v | - | - | - |
| Scan & Redeem | v | v | - | - | - |
| Redeem History | v | v | - | - | - |
| Create Reservation | - | v | - | v | - |
| Edit Reservation | - | v | - | v | - |
| Cancel Reservation | - | v | v | - | - |
| View Invoices | - | v | v | - | v |
| Manage Users | - | v | - | - | - |
| Finance Operations | - | v | v | - | - |
| Settings | - | v | - | - | - |

---

## Transaction Match — Use Cases

### MATCH (Sesuai Reservasi)
- Customer datang sesuai booking: jumlah pax, produk, semua cocok
- Admission klik MATCH, done

### MISMATCH (Tidak Sesuai Reservasi)
Contoh situasi:
- Booking 3 adult, datang 2 adult + 1 kid → pax berubah
- Booking Bundle A, di tempat mau ganti ke Bundle B → produk berubah
- Booking 5 orang, yang datang cuma 3 → partial arrival
- Customer tambah item di tempat yang tidak ada di reservasi

**Catatan wajib diisi** agar bisa dicocokkan dengan DSI (Daily Sales Invoice) nanti.

### Alur Rekonsiliasi
```
Reservation (plan) → REDEEM + match status → DSI (actual) → Rekonsiliasi
```
- MATCH: otomatis cocok, tidak perlu review
- MISMATCH: perlu di-review saat rekonsiliasi DSI — catatan admission jadi referensi

---

## Auth & Navigation Changes

### Login Redirect
```php
// AuthController@login — setelah Auth::attempt() berhasil:
if ($user->isAdmission()) {
    return redirect()->route('admission.dashboard');
}
// existing redirect ke /dashboard untuk role lain
```

### Sidebar/Nav
- ADMISSION role: hanya tampil menu Admission (Dashboard, Scan, History)
- ADMIN role: tampil semua menu + Admission section
- Role lain: menu Admission tidak tampil

---

## Settings

### New Setting Row
| Key | Value | Description |
|-----|-------|-------------|
| `admission_visit_date_tolerance_days` | `0` | Toleransi hari untuk redeem. 0 = strict hari ini. 1 = +/- 1 hari dari visit_date |

### Admin Settings Page
- Tambah input di halaman Settings existing
- Label: "Toleransi Visit Date (hari)"
- Input type: number, min 0, max 7
- Help text: "0 = hanya bisa redeem di visit_date. 1 = bisa redeem H-1 sampai H+1."

---

## Impact Analysis

### Files Baru (6)
| File | Fungsi |
|------|--------|
| `database/migrations/xxxx_add_admission_role_and_redeemed_status.php` | Schema changes |
| `app/Http/Controllers/AdmissionController.php` | Controller utama |
| `resources/views/admission/dashboard.blade.php` | Dashboard view |
| `resources/views/admission/scan.blade.php` | Scan + redeem view |
| `resources/views/admission/history.blade.php` | History view |
| `public/js/barcode-scanner.js` | Scanner wrapper (atau inline di blade) |

### Files Diubah (~7)
| File | Perubahan |
|------|-----------|
| `app/Models/User.php` | Tambah `isAdmission()` |
| `app/Models/Reservation.php` | REDEEMED badge, transaction_match cast, redeemer relationship |
| `routes/web.php` | Admission route group |
| `resources/views/layouts/sidebar.blade.php` | Admission menu section |
| `app/Http/Controllers/Auth/AuthController.php` | Login redirect logic |
| `app/Http/Controllers/ReservationController.php` | Show redeemed info di detail |
| Settings view (jika ada) | Tolerance config input |

### Tidak Berubah
- CheckRole middleware — sudah support dynamic roles
- Booking Pass — reservation_no sudah ada di barcode
- Partner system — tidak terpengaruh
- Invoice system — tidak terpengaruh
- Fraud detection — tidak terpengaruh

---

## Estimasi Scope

| Komponen | Jumlah |
|----------|--------|
| Migration | 1 file |
| Controller | 1 file, 5 methods |
| Views | 3 blade files |
| Model changes | 2 files |
| Route changes | 1 file |
| JS (scanner) | 1 file/inline |
| Config/Settings | 1 row |

---

## Open Questions

1. **NO_SHOW marking** — Siapa yang mark NO_SHOW di akhir hari? ADMISSION atau ADMIN/FINANCE?
2. **Bulk redeem** — Perlu fitur redeem multiple sekaligus (group booking)?
3. **Print/export history** — Admission perlu export/print history harian?
4. **Notification** — Perlu notifikasi ke FINANCE/ADMIN jika banyak MISMATCH?
5. **Actual items detail** — Seberapa detail input actual_items? Free text atau structured form?
