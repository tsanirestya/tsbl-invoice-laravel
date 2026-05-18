# TODO: Development Mode — Quick Role Login

- **Date:** 2026-05-18
- **Status:** 🔲 Pending
- **Branch:** `feat/dev-mode`

---

## Overview

Fitur "Development Mode" yang bisa di-enable/disable oleh Admin.

Ketika enabled:
- Di bawah form login, muncul tombol untuk setiap role
- Klik tombol = auto-login sebagai dev user role tersebut (tanpa ketik email/password)
- Memudahkan testing semua role behavior

---

## Dev Users yang Perlu Dibuat

| Role | Email | Password | Nama |
|------|-------|----------|------|
| `ADMIN` | dev.admin@tsbl.dev | admin123 | Dev Admin |
| `IT` | dev.it@tsbl.dev | admin123 | Dev IT |
| `BUSDEV_HO` | dev.busdev@tsbl.dev | admin123 | Dev Busdev HO |
| `FINANCE_STAFF` | dev.finstaff@tsbl.dev | admin123 | Dev Finance Staff |
| `FINANCE_MANAGER` | dev.finmanager@tsbl.dev | admin123 | Dev Finance Manager |
| `BPM` | dev.bpm@tsbl.dev | admin123 | Dev BPM |
| `RESERVATION_STAFF` | dev.reservation@tsbl.dev | admin123 | Dev Reservation Staff |
| `ADMISSION` | dev.admission@tsbl.dev | admin123 | Dev Admission |

---

## Task List

### FASE 1 — Settings / Toggle

- [ ] **1.1** Tambah row di tabel `settings`:
  - `key = 'dev_mode_enabled'`, `value = '0'` (default off)
  - Gunakan migration dengan `Schema::hasTable()` guard + `DB::table('settings')->updateOrInsert()`
- [ ] **1.2** Tambah UI toggle di halaman Settings (Admin only):
  - Switch / checkbox "Development Mode"
  - Tampilkan warning merah: "Jangan aktifkan di production!"
- [ ] **1.3** Tambah route + controller method untuk save toggle:
  - `POST /settings/dev-mode` → middleware `role:ADMIN`

---

### FASE 2 — Dev Users

- [ ] **2.1** Buat migration seeder (via migration file, bukan `php artisan db:seed`):
  - Insert 8 dev users dengan `DB::table('users')->updateOrInsert(['email' => ...], [...])`
  - Password di-hash dengan `bcrypt('admin123')`
  - `is_active = true`, `password_change_required = false`
  - Guard dengan `Schema::hasTable('users')`
- [ ] **2.2** Semua dev user ditandai supaya mudah diidentifikasi:
  - Email domain `@tsbl.dev`
  - `full_name` prefix `Dev `

---

### FASE 3 — Login Page UI

- [ ] **3.1** Update `resources/views/auth/login.blade.php`:
  - Cek setting `dev_mode_enabled` dari DB (atau cache)
  - Jika `true`, tampilkan section "Dev Quick Login" di bawah form
  - Section berisi 8 tombol warna-warni, satu per role
  - Tampilkan badge/banner "DEV MODE AKTIF" warna kuning/oranye di header login
- [ ] **3.2** Styling tombol:
  - Tiap tombol tampilkan nama role + ikon Bootstrap Icons
  - Warna berbeda per role (pakai `btn-outline-*` Bootstrap)

---

### FASE 4 — Auto-Login Route

- [ ] **4.1** Buat route: `GET /dev-login/{role}` — hanya aktif jika `dev_mode_enabled = 1`
- [ ] **4.2** Buat `DevLoginController`:
  ```php
  public function login(string $role)
  {
      // Abort 403 jika dev_mode tidak aktif
      // Cari user dev dengan role tsb (email @tsbl.dev + user_status = $role)
      // Auth::login($user)
      // Redirect ke dashboard
  }
  ```
- [ ] **4.3** Guard keamanan:
  - Jika `dev_mode_enabled = 0` → return 403
  - Jika user dev tidak ditemukan → flash error "Dev user untuk role ini belum dibuat"
  - Hanya user dengan email `@tsbl.dev` yang bisa di-login via route ini

---

### FASE 5 — Testing

- [ ] **5.1** Pastikan tombol quick login tidak muncul ketika dev_mode off
- [ ] **5.2** Test login tiap role, pastikan session & redirect benar
- [ ] **5.3** Test abuse: akses `/dev-login/ADMIN` ketika dev_mode off → harus 403
- [ ] **5.4** Test: dev user tidak bisa login via route jika email bukan `@tsbl.dev`

---

## Urutan Eksekusi

```
Fase 1 → Fase 2 → Fase 4 → Fase 3 → Fase 5
```

---

## Catatan

> **JANGAN aktifkan di production.** Toggle harus disertai warning visible di UI.
> Dev users (`@tsbl.dev`) adalah akun dummy — pastikan password mereka tidak pernah sama dengan akun real.
> Route `/dev-login/*` harus di-disable di production via setting, bukan via env (agar bisa dikontrol dari panel admin tanpa deploy).
