---
phase: 6
title: Partner Deposit System
status: DONE
created: 2026-05-06
completed: 2026-05-06
---

# TODO — Phase 6: Partner Deposit System

→ Kembali ke: [[MASTER-PLAN]]
→ Planning detail: [[FEATURE-PARTNER-DEPOSIT]]

---

## Checklist

### 1. Database Migration
- [ ] Buat migration `create_partner_deposits_table` dengan `hasTable()` guard
- [ ] Tambah kolom `deposit_balance` (virtual/computed) atau simpan di `partners` — **keputusan: pakai tabel transaksi, bukan kolom cached**
- [ ] Verifikasi kolom `invoices.deposit` sudah ada (lihat SCHEMA — sudah ada ✅)

### 2. Model & Relationships
- [ ] Buat model `PartnerDeposit`
- [ ] Tambah relasi `Partner::deposits()` → hasMany PartnerDeposit
- [ ] Tambah method `Partner::depositBalance()` → sum TOPUP - sum DEDUCTION
- [ ] Tambah method `Partner::hasEnoughDeposit(amount)` → boolean
- [ ] Update `Invoice` model — method `useDeposit()` untuk catat pemotongan

### 3. Controller — Deposit Management
- [ ] Buat `PartnerDepositController`
  - [ ] `index($partnerId)` — riwayat deposit partner
  - [ ] `store($partnerId)` — tambah top-up deposit (ADMIN/FINANCE only)
  - [ ] `adjustBalance($partnerId)` — koreksi manual (ADMIN only)

### 4. Invoice Integration
- [ ] Update `InvoiceController::store()` — simpan deposit, buat DEDUCTION record
- [ ] Update `InvoiceController::update()` — reverse DEDUCTION lama + buat baru jika deposit berubah
- [ ] Auto-buat record `PartnerDeposit` (type=DEDUCTION) saat invoice pakai deposit
- [ ] Auto-reverse DEDUCTION jika invoice di-edit/hapus
- [ ] Validasi server-side: deposit ≤ min(saldo, subtotal) — jangan andalkan JS saja

### 5. Views

**Deposit Management:**
- [ ] `partners/deposit/index.blade.php` — riwayat transaksi deposit per partner
- [ ] `partners/deposit/topup.blade.php` — form tambah deposit
- [ ] Tambah deposit balance card di `partners/show.blade.php`
  - [ ] Badge "Deposit Rendah ⚠️" jika saldo < threshold
  - [ ] Badge "Deposit Habis 🔴" jika saldo = 0

**Invoice Form — Deposit Panel:**
- [ ] Panel "Saldo Deposit Partner" tampil otomatis saat partner dipilih (via AJAX)
- [ ] Skenario A (saldo cukup): info saldo + checkbox + input nominal + auto-fill
- [ ] Skenario B (saldo rendah / low warning): alert kuning ⚠️ + pesan top-up
- [ ] Skenario C (tidak ada deposit): blok deposit tersembunyi
- [ ] Skenario D (saldo habis): alert merah 🔴 + link top-up + checkbox disabled
- [ ] Grand Total update real-time saat nominal deposit diubah (JavaScript)
- [ ] Clamp input: max = min(saldo, subtotal) — tidak bisa lebih

**Dashboard Widget:**
- [ ] Widget "Partner Deposit Rendah" di dashboard
  - [ ] Tampil hanya jika ada ≥1 partner saldo < threshold atau habis
  - [ ] List partner + saldo masing-masing
  - [ ] Link ke halaman top-up per partner

**PDF & Report:**
- [ ] Update invoice PDF — baris deposit muncul hanya jika `invoice.deposit > 0`

### 5b. Settings — Deposit Threshold
- [ ] Tambah key `deposit_low_threshold` di settings seeder/migration
- [ ] Tampilkan field ini di halaman Settings (ADMIN only)
- [ ] Validasi: numeric, > 0

### 6. Routes
- [ ] `GET  /partners/{id}/deposits` → index
- [ ] `GET  /partners/{id}/deposits/topup` → form
- [ ] `POST /partners/{id}/deposits` → store
- [ ] `GET  /api/partners/{id}/deposit-balance` → JSON (`balance`, `is_low`, `is_empty`, `threshold`)

### 7. Validasi & Edge Cases
- [ ] Block top-up negatif
- [ ] Block pakai deposit melebihi saldo
- [ ] Block hapus/edit invoice finalized yang sudah potong deposit
- [ ] Handle refund: jika invoice void/cancel → kembalikan deposit
- [ ] Log semua perubahan di `invoice_logs`

### 8. Laporan
- [ ] Tambah tab "Deposit" di Reports page
  - [ ] Saldo deposit per partner
  - [ ] Riwayat top-up & pemakaian
  - [ ] Export CSV + PDF

### 9. Testing Manual
- [ ] Top-up deposit travel A
- [ ] Buat invoice → panel deposit muncul dengan saldo benar
- [ ] Centang "Gunakan Deposit" → grand_total berkurang real-time
- [ ] Submit invoice → saldo deposit berkurang sesuai
- [ ] Saldo tidak bisa minus (block di form + controller)
- [ ] Saldo rendah (< threshold) → warning kuning ⚠️ muncul di form invoice
- [ ] Saldo habis → alert merah 🔴, checkbox disabled
- [ ] Partner detail → badge saldo rendah/habis tampil
- [ ] Dashboard widget muncul jika ada partner saldo rendah
- [ ] Finalized invoice — deposit tidak bisa diubah
- [ ] Edit draft invoice dengan deposit → saldo reverse & recalculate benar
- [ ] Report deposit akurat (sum TOPUP vs DEDUCTION)

---

## Estimasi Kompleksitas

| Task | Effort |
|---|---|
| Migration + Model + `depositBalance()` | Kecil |
| Controller CRUD deposit | Sedang |
| AJAX endpoint balance (is_low, is_empty) | Kecil |
| Invoice form — deposit panel + JS real-time | Sedang |
| Invoice Integration (server-side + DEDUCTION) | Sedang-Besar |
| Reminder: form warning + partner badge | Kecil |
| Dashboard widget partner deposit rendah | Kecil |
| Settings threshold | Kecil |
| Edge cases + reverse/rollback | Besar |
| Reports tab Deposit | Kecil |

**Total estimasi:** 1 sesi panjang atau 2 sesi normal
