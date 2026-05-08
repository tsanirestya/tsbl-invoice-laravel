# Feature Planning — Partner Deposit System

→ Kembali ke: [[MASTER-PLAN]]
→ TODO: [[TODO-PHASE-6-PARTNER-DEPOSIT]]

**Date:** 2026-05-06
**Phase:** 6
**Status:** PLANNING

---

## Ringkasan Fitur

Partner (travel) bisa mengisi saldo deposit ke sistem. Saat invoice dibuat:
- Sistem otomatis tampilkan **sisa deposit partner**
- Operator bisa pilih **"Gunakan Deposit"** — saldo dipotong, grand_total berkurang
- Jika saldo mendekati habis → muncul **reminder** agar partner segera top-up

`grand_total = subtotal - deposit_dipakai`

---

## Konsep Bisnis

```
Partner deposit uang ke TSBL → masuk saldo deposit partner
Saat ada invoice baru        → operator bisa pilih "pakai deposit"
Sistem potong saldo deposit  → grand_total invoice berkurang
Riwayat tersimpan            → audit trail lengkap
```

---

## Database Design

### Tabel Baru: `partner_deposits`

| Column | Type | Notes |
|---|---|---|
| id | int unsigned PK AI | |
| partner_id | int unsigned FK partners | |
| type | enum(TOPUP, DEDUCTION, ADJUSTMENT) | TOPUP=isi, DEDUCTION=pakai, ADJUSTMENT=koreksi |
| amount | decimal(15,2) | selalu positif — type yang tentukan arah |
| invoice_id | int unsigned FK invoices | nullable — hanya untuk DEDUCTION |
| reference_no | varchar(100) | nullable — no bukti transfer/referensi |
| notes | text | nullable |
| created_by | int unsigned FK users | |
| created_at | datetime | |

> **Tidak ada `updated_at`** — deposit records immutable. Koreksi pakai ADJUSTMENT baru.

### Kolom Existing yang Dipakai

`invoices.deposit` (decimal 15,2, default 0) — sudah ada di DB ✅
→ Ini stores jumlah deposit yang dipakai di invoice ini.
→ `grand_total = subtotal - deposit`

---

## Alur Deposit Balance

```
deposit_balance(partner) = 
    SUM(amount WHERE type=TOPUP)
  + SUM(amount WHERE type=ADJUSTMENT AND amount > 0)
  - SUM(amount WHERE type=DEDUCTION)
  - SUM(amount WHERE type=ADJUSTMENT AND amount < 0)

Simplified:
  TOPUP + ADJUSTMENT_POSITIF - DEDUCTION - ADJUSTMENT_NEGATIF
```

Implementasi di model:

```php
// Partner model
public function depositBalance(): float
{
    return $this->deposits()
        ->selectRaw("SUM(CASE WHEN type='TOPUP' THEN amount WHEN type='DEDUCTION' THEN -amount WHEN type='ADJUSTMENT' THEN amount ELSE 0 END) as balance")
        ->value('balance') ?? 0;
}
```

---

## Alur Invoice dengan Deposit

### Saat Create Invoice

```
1. User buka form create invoice
2. Sistem cek deposit_balance partner (via AJAX)
3. Jika balance > 0 → tampilkan opsi "Gunakan deposit (Rp X)"
4. User masukkan jumlah deposit yang dipakai (max = min(balance, subtotal))
5. grand_total = subtotal - deposit_dipakai
6. Saat save:
   a. Simpan invoice.deposit = deposit_dipakai
   b. Buat record PartnerDeposit(type=DEDUCTION, invoice_id=invoice.id)
```

### Saat Edit Invoice (Draft Only)

```
1. Jika deposit sebelumnya > 0 → reverse DEDUCTION lama
2. Hitung ulang dengan deposit baru
3. Buat DEDUCTION record baru
```

### Saat Invoice Finalized

```
- deposit tidak bisa diubah lagi
- DEDUCTION record terkunci
```

### Jika Invoice Dibatalkan/Dihapus (Future)

```
- Reverse DEDUCTION → buat ADJUSTMENT positif senilai deposit
- Log di invoice_logs
```

---

## Model Relationships

```php
// Partner
public function deposits()
{
    return $this->hasMany(PartnerDeposit::class);
}

// Invoice
public function depositTransaction()
{
    return $this->hasOne(PartnerDeposit::class)->where('type', 'DEDUCTION');
}

// PartnerDeposit
public function partner() { return $this->belongsTo(Partner::class); }
public function invoice() { return $this->belongsTo(Invoice::class); }
public function creator() { return $this->belongsTo(User::class, 'created_by'); }
```

---

## UI / UX Flow

### Halaman Partner Detail (partners/show)

```
[Informasi Partner]
[Saldo Deposit]  ← card baru
  Saldo saat ini: Rp 5.000.000
  [Lihat Riwayat]  [Top-up Deposit]
```

### Halaman Riwayat Deposit (partners/{id}/deposits)

```
Tabel:
| Tanggal | Tipe | Nominal | Referensi | Invoice | Oleh |
| 01 Mei  | TOPUP | +5.000.000 | TF-001 | — | Admin |
| 03 Mei  | DEDUCTION | -1.500.000 | — | INV-2026-0001 | Finance |
---
Saldo saat ini: Rp 3.500.000
```

### Form Invoice — Bagian Deposit

**Skenario A: Saldo deposit cukup (≥ threshold)**

```
┌─────────────────────────────────────────────────────────┐
│  💰 Saldo Deposit Partner                               │
│  Sisa deposit: Rp 5.000.000                             │
│                                                         │
│  [ ✓ ] Gunakan pembayaran menggunakan deposit           │
│         Jumlah: [____________] (maks: Rp 2.000.000)    │
│         ← default auto-fill subtotal jika saldo cukup  │
└─────────────────────────────────────────────────────────┘

  Subtotal       Rp 2.000.000
  Deposit        (Rp 2.000.000)   ← update real-time
  ─────────────────────────────
  Grand Total    Rp 0              ← hijau jika 0
```

**Skenario B: Saldo deposit di bawah threshold (low warning)**

```
┌─────────────────────────────────────────────────────────┐
│  ⚠️  Saldo Deposit Hampir Habis                         │
│  Sisa deposit: Rp 800.000                               │
│  Batas minimum: Rp 1.000.000                            │
│                                                         │
│  Mohon informasikan kepada partner untuk segera          │
│  melakukan pengisian deposit.                           │
│                                                         │
│  [ ✓ ] Tetap gunakan saldo yang tersisa (Rp 800.000)   │
└─────────────────────────────────────────────────────────┘
```

**Skenario C: Tidak ada deposit**

```
  → Blok deposit tidak ditampilkan sama sekali
  → Grand Total = Subtotal langsung
```

**Skenario D: Saldo deposit habis total (= 0)**

```
┌─────────────────────────────────────────────────────────┐
│  🔴 Saldo Deposit Habis                                 │
│  Partner belum memiliki saldo deposit.                  │
│  [Tambah Deposit Sekarang ↗]  ← link ke form top-up    │
└─────────────────────────────────────────────────────────┘
  → Checkbox "Gunakan Deposit" di-disable
```

---

### Low Deposit Reminder — Logika Threshold

```
low_threshold = settings('deposit_low_threshold') ?? 1.000.000

isLow(partner)  = depositBalance(partner) > 0 
                  AND depositBalance(partner) < low_threshold

isEmpty(partner) = depositBalance(partner) <= 0
```

Threshold disimpan di tabel `settings`:
- Key: `deposit_low_threshold`
- Default: `1000000`
- Label: "Batas minimum saldo deposit (Rp)"
- Bisa diubah di halaman Settings (ADMIN)

---

### Reminder Muncul di 3 Tempat

| Lokasi | Kondisi | Tampilan |
|---|---|---|
| Form create invoice | `isLow` | Alert warning kuning ⚠️ + pesan minta top-up |
| Form create invoice | `isEmpty` | Alert danger merah 🔴 + link top-up, checkbox disabled |
| Partner detail (show) | `isLow` | Badge "Deposit Rendah" di card saldo |
| Partner detail (show) | `isEmpty` | Badge "Deposit Habis" merah di card saldo |
| Dashboard | `isLow` OR `isEmpty` | Widget "Partner Perlu Top-up Deposit" |

---

### Dashboard Widget — Partner Perlu Top-up

```
┌──────────────────────────────────────────┐
│  ⚠️  Partner Deposit Rendah (3)          │
│  • Travel Sentosa     — Rp 500.000       │
│  • Bali Tour Express  — Rp 200.000       │
│  • Holiday Travel     — Rp 0 (Habis!)   │
│  [Lihat Semua ↗]                         │
└──────────────────────────────────────────┘
```
Tampil hanya jika ada ≥1 partner dengan saldo rendah/habis.

---

### Behavior Checkbox "Gunakan Deposit"

| State | Behavior |
|---|---|
| Unchecked (default) | Deposit field = 0, grand_total = subtotal |
| Checked, saldo cukup | Deposit field auto-fill = subtotal, bisa diedit manual |
| Checked, saldo < subtotal | Deposit field auto-fill = saldo (max yang bisa dipakai) |
| Unchecked setelah checked | Reset deposit = 0, grand_total kembali ke subtotal |

Semua kalkulasi real-time via JavaScript — tanpa reload halaman.

---

### Form Invoice — JavaScript Flow

```javascript
// Saat partner dipilih (atau page load saat edit)
async function loadDepositInfo(partnerId) {
    const res = await fetch(`/api/partners/${partnerId}/deposit-balance`);
    const { balance, is_low, is_empty, threshold } = await res.json();
    renderDepositPanel(balance, is_low, is_empty, threshold);
}

// Saat checkbox "Gunakan Deposit" dicentang
function onDepositToggle(checked) {
    if (!checked) {
        setDepositAmount(0);
    } else {
        const max = Math.min(depositBalance, subtotal);
        setDepositAmount(max);
    }
    recalcGrandTotal();
}

// Saat nominal deposit diubah manual
function onDepositInput(value) {
    const clamped = Math.min(value, depositBalance, subtotal);
    setDepositAmount(clamped);
    recalcGrandTotal();
}

// Grand total selalu sinkron
function recalcGrandTotal() {
    grandTotal = subtotal - depositAmount;
    updateUI(grandTotal);
}
```

---

### AJAX Endpoint Response

`GET /api/partners/{id}/deposit-balance`

```json
{
  "partner_id": 5,
  "partner_name": "Travel Sentosa",
  "balance": 800000,
  "balance_formatted": "Rp 800.000",
  "threshold": 1000000,
  "is_low": true,
  "is_empty": false
}
```

---

### Invoice PDF

```
Subtotal       Rp 2.000.000
Deposit        (Rp 2.000.000)
─────────────────────────────
Grand Total    Rp 0
```
→ Baris deposit hanya muncul di PDF jika `invoice.deposit > 0`

---

## Validasi Rules

| Rule | Detail |
|---|---|
| Top-up amount | > 0, required, numeric |
| Deposit pakai di invoice | 0 ≤ deposit ≤ min(saldo, subtotal) |
| Deposit tidak bisa melebihi subtotal | Validasi di JS + controller (double guard) |
| Edit deposit invoice draft | Boleh — reverse + re-create DEDUCTION |
| Edit deposit invoice final | Tidak boleh |
| Hapus invoice punya deposit | Reverse DEDUCTION dulu |
| ADJUSTMENT | ADMIN only |
| `deposit_low_threshold` settings | > 0, numeric — default 1.000.000 |

---

## Access Control

| Action | Role Minimum |
|---|---|
| Lihat riwayat deposit | FINANCE |
| Top-up deposit | FINANCE |
| Koreksi/ADJUSTMENT | ADMIN |
| Pakai deposit di invoice | FINANCE |

---

## Routes Plan

```php
Route::prefix('partners/{partner}/deposits')->middleware('auth')->group(function () {
    Route::get('/', [PartnerDepositController::class, 'index'])->name('deposits.index');
    Route::get('/topup', [PartnerDepositController::class, 'create'])->name('deposits.create');
    Route::post('/', [PartnerDepositController::class, 'store'])->name('deposits.store');
});

// AJAX untuk invoice form
Route::get('/api/partners/{partner}/deposit-balance', [PartnerDepositController::class, 'balance'])
    ->name('api.deposit.balance');
```

---

## Migration Plan

```php
Schema::create('partner_deposits', function (Blueprint $table) {
    $table->id();
    $table->unsignedInteger('partner_id');
    $table->enum('type', ['TOPUP', 'DEDUCTION', 'ADJUSTMENT']);
    $table->decimal('amount', 15, 2);
    $table->unsignedInteger('invoice_id')->nullable();
    $table->string('reference_no', 100)->nullable();
    $table->text('notes')->nullable();
    $table->unsignedInteger('created_by');
    $table->timestamp('created_at')->useCurrent();

    $table->foreign('partner_id')->references('id')->on('partners');
    $table->foreign('invoice_id')->references('id')->on('invoices');
});
```

Dengan guard:
```php
if (!Schema::hasTable('partner_deposits')) {
    // create table above
}
```

---

## Settings — Tambahan Key

Tambah 1 key baru di tabel `settings` (via seeder/migration):

| key | value | label |
|---|---|---|
| `deposit_low_threshold` | `1000000` | Batas minimum saldo deposit (Rp) |

Tampil di halaman Settings > tab baru "Deposit" atau gabung di tab Finance.
ADMIN bisa ubah nilainya.

---

## Laporan Deposit

Tambah di `/reports` page — tab baru "Deposit":

| Kolom | Keterangan |
|---|---|
| Partner | Nama partner |
| Total Top-up | Sum TOPUP |
| Total Terpakai | Sum DEDUCTION |
| Saldo | Balance saat ini |

Filter: Partner, Tanggal
Export: CSV + PDF

---

## Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Double-deduction jika user submit invoice 2x | Unique constraint atau idempotency check |
| Saldo minus karena concurrent edit | Validasi saldo sebelum save (bukan hanya di form) |
| Invoice finalized tapi deposit-nya wrong | Lock deposit field saat finalize |
| Data lama (sebelum fitur ini) | Saldo mulai dari 0 — tidak ada backfill |

---

## Dependencies

- Phase 1-5 harus selesai ✅ (sudah)
- `invoices.deposit` kolom sudah ada ✅
- Partner module sudah ada ✅
- Invoice CRUD sudah ada ✅
