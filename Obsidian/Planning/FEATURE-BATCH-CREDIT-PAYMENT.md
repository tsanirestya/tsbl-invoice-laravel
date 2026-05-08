# Feature Planning: Batch Credit Payment (Phase 9)

> Partner transfer 1 nominal → tim finance alokasikan ke banyak invoice sekaligus.

**Status:** 📋 Planning  
**Date:** 2026-05-08  
**Depends on:** Phase 8 (Credit Facility) ✅

---

## Latar Belakang

Sebelumnya, catat pembayaran hanya bisa dilakukan per-invoice satu per satu (di halaman `invoices/{id}`). Ini tidak efisien ketika partner mentransfer 1 nominal besar untuk melunasi banyak invoice sekaligus.

Fitur ini memungkinkan tim finance merekam **1 penerimaan** dan mengalokasikannya ke **banyak invoice** dalam 1 transaksi atomik.

---

## Keputusan Desain

| Pertanyaan | Keputusan |
|---|---|
| Sisa alokasi (transfer > total invoice) | Masuk **deposit partner** (TOPUP otomatis) |
| Hapus batch | Invoice kembali ke **UNPAID** + Payment records dihapus (rollback penuh) |
| Siapa yang bisa buat batch payment | **FINANCE** (semua role FINANCE, bukan admin-only) |
| Alokasi default | **FIFO** (invoice terlama terisi duluan), user bisa override manual |
| Total alokasi | Harus **≤ total_received** — tidak boleh alokasi melebihi yang diterima |

---

## Database Changes

### Tabel Baru: `credit_payments`

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | int PK | — |
| partner_id | unsignedInt FK | partner yang bayar |
| payment_date | date | tanggal transfer masuk |
| total_received | decimal(15,2) | total nominal diterima |
| total_allocated | decimal(15,2) | total yang dialokasikan ke invoice |
| excess_to_deposit | decimal(15,2) | sisa yang masuk deposit (0 jika pas) |
| deposit_transaction_id | unsignedInt FK nullable | FK ke `partner_deposits` jika ada sisa |
| payment_method | string(50) | Transfer Bank / Cash / QRIS / dll |
| reference_no | string(100) nullable | no. rekening / kode transfer |
| proof_file | string nullable | path bukti upload |
| notes | text nullable | catatan |
| created_by | unsignedInt FK | user yang input |
| created_at | datetime | — |

### Perubahan Tabel `payments` (existing)

Tambah 1 kolom nullable:

```
credit_payment_id  unsignedInt nullable FK → credit_payments.id
```

Kolom ini `nullable` agar payment lama (per-invoice biasa) tidak terpengaruh.

---

## Model & Relasi

```
CreditPayment
  → belongsTo Partner
  → hasMany Payment (via credit_payment_id)
  → belongsTo PartnerDeposit (via deposit_transaction_id, nullable)

Payment
  → belongsTo CreditPayment (nullable)  ← tambahan baru

Partner
  → hasMany CreditPayment              ← tambahan baru
```

---

## Routes

```
GET    /credit-payments              → index  (list semua batch)
GET    /credit-payments/create       → form baru
POST   /credit-payments              → store
GET    /credit-payments/{id}         → show detail + alokasi

DELETE /credit-payments/{id}         → destroy (void batch → invoice kembali UNPAID)

GET    /api/partners/{partner}/outstanding-invoices   → AJAX: load invoices outstanding
```

**Akses:** semua role authenticated (Finance, Admin). Tidak perlu `role:ADMIN` guard.

---

## UI Flow

### Halaman: List `/credit-payments`

- Tabel: No. Batch, Partner, Tanggal, Total Diterima, Jumlah Invoice, Sisa→Deposit, Dibuat oleh
- Badge status: FULL / PARTIAL (jika ada sisa ke deposit)
- Tombol `+ Terima Pembayaran Credit`

### Halaman: Form Create `/credit-payments/create`

```
Step 1: Pilih Partner
  → dropdown partner ber-credit (limit_credit > 0)
  → saat pilih → AJAX load credit info + invoices outstanding

Step 2: Tampil Credit Info
  → Limit | Used | Available (bar)

Step 3: Tabel Invoice Outstanding
  Checkbox | Invoice No | Tgl Invoice | Jatuh Tempo | Grand Total | Sudah Bayar | Sisa Tagihan | Alokasi [input]
  -----------------------------------------------------------------------
  ☑         INV-001      01/04          30/04          5.000.000     0             5.000.000      [5.000.000]
  ☑         INV-002      05/04          05/05          8.000.000     0             8.000.000      [8.000.000]
  ☐         INV-003      10/04          10/05          7.000.000     0             7.000.000      [        0]

  [FIFO Auto-Fill] button → isi nominal dari invoice terlama sampai total_received habis

Step 4: Summary Bar (live JS update)
  Total Dialokasikan : Rp 13.000.000
  Sisa → Deposit     : Rp  7.000.000  ← kuning jika > 0
  Total Diterima     : [___20.000.000___]

Step 5: Payment Info
  Tanggal Bayar   [__/__/____]
  Metode          [Transfer Bank ▼]
  No. Referensi   [______________]
  Bukti Bayar     [Upload jpg/png/pdf]
  Catatan         [______________]

  [Simpan Pembayaran]
```

### Validasi Frontend (JS)

- Nominal alokasi per invoice ≤ sisa tagihan invoice itu
- Total alokasi ≤ total_received
- Minimal 1 invoice dipilih

### Halaman: Show `/credit-payments/{id}`

```
┌─────────────────────────────────────────────────┐
│ Batch #CP-001 — PT ABC                          │
│ Tanggal: 01/05/2026 | Transfer Bank — REF/12345 │
│ Total Diterima: Rp 20.000.000                   │
├─────────────────────────────────────────────────┤
│ Alokasi Invoice:                                │
│  INV-001  Rp  5.000.000  → PAID  ✓              │
│  INV-002  Rp  8.000.000  → PAID  ✓              │
│  Total    Rp 13.000.000                         │
├─────────────────────────────────────────────────┤
│ Sisa → Deposit: Rp 7.000.000                    │
│  → [Lihat Transaksi Deposit]                    │
├─────────────────────────────────────────────────┤
│ [Lihat Bukti Transfer]   [Void / Hapus Batch]   │
└─────────────────────────────────────────────────┘
```

### Void / Hapus Batch

Konfirmasi dialog → DB::transaction:
1. Hapus semua `Payment` record dengan `credit_payment_id = batch.id`
2. Panggil `recalcStatus()` pada tiap invoice yang terpengaruh → kembali UNPAID/PARTIAL
3. Jika ada `deposit_transaction_id` → hapus record `partner_deposits` tersebut (reverse TOPUP)
4. Hapus `credit_payment` header

---

## Logic `store()` — DB Transaction

```php
DB::transaction(function() use ($request, $partner) {

    // 1. Hitung excess
    $totalAllocated   = sum(allocations.amount);
    $excessToDeposit  = max(0, total_received - totalAllocated);

    // 2. Buat deposit record dulu jika ada sisa
    $depositRecord = null;
    if ($excessToDeposit > 0) {
        $depositRecord = PartnerDeposit::create([
            'partner_id' => $partner->id,
            'type'       => 'TOPUP',
            'amount'     => $excessToDeposit,
            'notes'      => "Sisa batch payment #{batch_no}",
            ...
        ]);
    }

    // 3. Buat header credit_payment
    $batch = CreditPayment::create([
        ...
        'total_allocated'       => $totalAllocated,
        'excess_to_deposit'     => $excessToDeposit,
        'deposit_transaction_id'=> $depositRecord?->id,
    ]);

    // 4. Per invoice yang dipilih
    foreach ($allocations as $invoiceId => $amount) {
        Payment::create([
            'invoice_id'        => $invoiceId,
            'amount'            => $amount,
            'credit_payment_id' => $batch->id,
            ...
        ]);
        Invoice::find($invoiceId)->recalcStatus();
    }
});
```

---

## Files yang Dibuat

| File | Keterangan |
|---|---|
| `database/migrations/..._create_credit_payments_table.php` | Tabel baru |
| `database/migrations/..._add_credit_payment_id_to_payments_table.php` | Kolom baru di payments |
| `app/Models/CreditPayment.php` | Model + relasi |
| `app/Http/Controllers/CreditPaymentController.php` | index, create, store, show, destroy |
| `resources/views/credit-payments/index.blade.php` | List batch |
| `resources/views/credit-payments/create.blade.php` | Form + FIFO JS |
| `resources/views/credit-payments/show.blade.php` | Detail + void |

## Files yang Diubah

| File | Perubahan |
|---|---|
| `routes/web.php` | Tambah resource routes + AJAX route |
| `app/Models/Payment.php` | Tambah `creditPayment()` belongsTo |
| `app/Models/Partner.php` | Tambah `creditPayments()` hasMany |
| `resources/views/layouts/app.blade.php` | Tambah menu "Pembayaran Credit" |

---

## Nomor Batch

Format: `CP-{YYYYMM}-{sequence}` → contoh: `CP-202605-001`  
Generate di controller saat `store()`, mirip pola `invoice_no`.

---

## Memo Pengajuan Pembayaran

Fitur terpisah — lihat [[FEATURE-PAYMENT-MEMO]] untuk planning lengkap.

Relasi ke batch: setelah finance kirim memo pengajuan ke partner, partner bayar, lalu finance input Batch Credit Payment. Memo pengajuan berdiri sendiri dan tidak terikat ke `credit_payments`.

---

## Status Flag Void

Tambah kolom `is_voided`, `voided_at`, `voided_by` ke tabel `credit_payments`.  
Saat destroy: set flag void dulu (audit trail) → lalu rollback Payment records + recalcStatus.  
Batch yang void tetap muncul di list dengan badge **DIBATALKAN**, tidak bisa di-edit atau diakses ulang.

---

## Edge Cases

| Skenario | Handling |
|---|---|
| Partner tidak punya invoice outstanding | Form tampil pesan kosong, tombol simpan disabled |
| Alokasi 0 untuk semua invoice | Validasi server: wajib min 1 invoice dengan amount > 0 |
| Invoice sudah PAID saat form submit (race condition) | Validasi server: skip invoice yang sudah PAID, sisa alokasi jadi excess → deposit |
| Void batch tapi deposit record sudah dipakai (DEDUCTION dari invoice lain) | Tampil error: "Deposit dari batch ini sudah terpakai, tidak bisa void" |
