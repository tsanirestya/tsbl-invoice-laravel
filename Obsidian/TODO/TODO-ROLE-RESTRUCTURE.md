# TODO: Role Restructure — 8 Role System

- **Date:** 2026-05-18
- **Status:** ✅ Done
- **Branch:** `feat/role-restructure`
- **Completed:** 2026-05-18

---

## Background

Sistem saat ini hanya memiliki 4 role (`ADMIN`, `FINANCE`, `SALES`, `ADMISSION`). Perlu dipecah menjadi 8 role yang lebih granular sesuai struktur organisasi.

### Role Baru

| Kode | Nama | Scope |
|---|---|---|
| `ADMIN` | Admin | Super user, full access |
| `IT` | IT | System & user management, bukan bisnis |
| `BUSDEV_HO` | Busdev HO | Monitor bisnis, read-only |
| `FINANCE_STAFF` | Finance Staff | Operasional invoice, import |
| `FINANCE_MANAGER` | Finance Manager | Approval + tanda tangan |
| `BPM` | Business Partner Manager | Partner + reservasi, non-finansial |
| `RESERVATION_STAFF` | Reservation Staff | Reservasi saja, view partner |
| `ADMISSION` | Admission | QR + scan + riwayat |

---

## Task List

### FASE 1 — Database Migration ✅

- [x] **1.1** Migration: ubah enum `user_status` dari 4 role lama → 8 role baru
  - File: `2026_05_18_100001_restructure_user_roles.php`
  - 3-step: expand enum → map old roles → shrink enum
- [x] **1.2** Migration: tambah kolom `finalized_by` + `finalized_by_signature` di tabel `invoices`
  - File: `2026_05_18_100002_add_finalized_by_to_invoices.php`
- [x] **1.3** Migration: mapping role lama → baru (FINANCE→FINANCE_MANAGER, SALES→BPM, VIEWER→FINANCE_STAFF)
  - Done inside migration 1.1

---

### FASE 2 — Middleware & Route ✅

- [x] **2.1** `CheckRole` middleware — tidak ada perubahan logic
- [x] **2.2** `bootstrap/app.php` — alias `role` tetap terdaftar
- [x] **2.3** Refactor `routes/web.php` — role groups updated ke 8 role baru
- [x] **2.4** Split route `partners` — read-only (broad) vs write (BPM/Finance)
- [x] **2.5** Split route anomalies — view: `ADMIN,FINANCE_*,BUSDEV_HO,BPM` | resolve: `ADMIN,FINANCE_MANAGER`
- [x] **2.6** Split route reports — `ADMIN,FINANCE_*,BUSDEV_HO`

---

### FASE 3 — Model ✅

- [x] **3.1** `User.php` — 8 helper methods + `canApproveFinance()` + `canAccessFinance()`
- [x] **3.2** `Invoice.php` — tambah `finalized_by` + `finalized_by_signature` ke `$fillable`; tambah `finalizedBy()` relation

---

### FASE 4 — Partner Field-Level Lock ✅

- [x] **4.1** Field finansial: `bank_name`, `bank_account_no`, `bank_account_name`, `npwp`, `payment_type`, `payment_due_days`, `limit_credit`, `credit_class_id`, `doc_npwp`
- [x] **4.2** Backend guard di `PartnerController@update` — BPM: strip financial fields sebelum update
- [x] **4.3** Frontend: `partners/_form.blade.php` — BPM melihat field finansial sebagai read-only (disabled)

---

### FASE 5 — Signature Finance Manager saat Finalize ✅

- [x] **5.1** `InvoiceController@finalize` — snapshot `signature_image` → `finalized_by_signature`, simpan `finalized_by`
- [x] **5.2** `invoices/pdf.blade.php` — 3-level fallback: `finalized_by_signature` → `finalizedBy.signature_image` → `creator.signature_image`
- [x] **5.3** Fallback graceful untuk invoice lama (null signature)

---

### FASE 6 — Commission Request Flow ✅

- [x] **6.1** `CommissionReleaseRequest` model + migration (`commission_release_requests` table)
- [x] **6.2** `ReservationAnomalyController` — `commissionRequestAction`, `commissionRequestApprove`, `commissionRequestReject`
- [x] **6.3** Routes: `commission-review.request-action`, `commission-requests.approve`, `commission-requests.reject`
- [x] **6.4** `commission-review/index.blade.php` — Finance Staff: tombol "Ajukan" + modal; Finance Manager: tombol Setuju/Tolak
- [x] **6.5** `ReservationPayment` model — relasi `commissionRequests()` + `pendingRequest()`

---

### FASE 7 — UI: Sidebar & Menu Guard ✅

- [x] **7.1** `layouts/app.blade.php` — sidebar accordion sections per role:
  - ADMISSION: hanya Admission section
  - IT: hanya Sistem section
  - Lainnya: Operasional + Reservasi + Keuangan + Pengaturan sesuai role
- [x] **7.2** Bottom nav mobile — IT role tidak tampilkan bottom nav finance links

---

### FASE 8 — User Management Update ✅

- [x] **8.1** `UserController` — validasi enum 8 role baru
- [x] **8.2** `users/_form.blade.php` — dropdown 8 role dengan deskripsi

---

### FASE 9 — Testing

- [ ] **9.1** Test login tiap role, pastikan redirect dan akses sesuai matrix
- [ ] **9.2** Test PDF invoice — signature dari Finance Manager yang finalize
- [ ] **9.3** Test Partner edit BPM — financial fields tidak terupdate
- [ ] **9.4** Test route 403 — akses tidak sah harus return 403 bukan redirect login
- [ ] **9.5** Jalankan migration via `/run-migrations?token=...`

---

## Catatan Implementasi

> **PENTING:** Jangan jalankan `migrate:fresh`. Semua migration gunakan `Schema::hasColumn()` / `Schema::hasTable()` guard.

> **Mapping role existing users** sudah di-handle di dalam migration: FINANCE→FINANCE_MANAGER, SALES→BPM, VIEWER→FINANCE_STAFF.

> Commission request flow sudah menggunakan Opsi A: tabel `commission_release_requests`.

---

## Urutan Eksekusi yang Disarankan

```
✅ Fase 1 → ✅ Fase 2 → ✅ Fase 3 → ✅ Fase 7 → ✅ Fase 4 → ✅ Fase 5 → ✅ Fase 8 → ✅ Fase 6 → Fase 9 (testing)
```
