# BUG-005 — Production Setup: FK Type Mismatch + Route Cache 404

## Status
- ✅ Fixed — 2026-05-08

## Priority
- Critical (blocking production deploy)

## Environment
- Production: `https://invoice.transentertainment.id`
- DB: `transen2_tsbl_invoice`

---

## Masalah 1: `/setup-production` → 404

### Gejala
Akses `/setup-production` di production mengembalikan 404.

### Root Cause
Route cache lama di `bootstrap/cache/routes-v7.php` tidak include route baru. Laravel pakai cache lama, `web.php` yang baru tidak terbaca.

### Fix
Hapus semua file di `bootstrap/cache/` via WinSCP (kecuali `.gitignore`):
```
bootstrap/cache/routes-v7.php
bootstrap/cache/config.php
bootstrap/cache/packages.php
bootstrap/cache/services.php
```
Laravel akan rebuild cache otomatis saat request pertama.

---

## Masalah 2: Migration Error — FK Constraint errno 150

### Gejala
```
SQLSTATE[HY000]: General error: 1005 Can't create table `transen2_tsbl_invoice`.`payment_memo_invoices`
(errno: 150 "Foreign key constraint is incorrectly formed")
```

### Root Cause
Type mismatch antara kolom FK dan kolom referensi:

| Tabel | Kolom | Type |
|---|---|---|
| `payment_memos` | `id` | `increments()` = `UNSIGNED INT` |
| `payment_memo_invoices` | `payment_memo_id` | `unsignedBigInteger()` = `UNSIGNED BIGINT` |

MySQL/MariaDB menolak FK jika type tidak identik (signed/unsigned dan size harus sama).

### Fix
Di `2026_05_08_100002_create_payment_memo_invoices_table.php`:
```php
// Salah
$table->unsignedBigInteger('payment_memo_id');

// Benar — harus match dengan payment_memos.id yang pakai increments()
$table->unsignedInteger('payment_memo_id');
```

### Aturan Umum
- `increments('id')` → FK column harus `unsignedInteger()`
- `bigIncrements('id')` / `id()` → FK column harus `unsignedBigInteger()`
- Selalu cek type `id` di tabel referensi sebelum buat FK

---

## Files Terdampak
```
database/migrations/2026_05_08_100002_create_payment_memo_invoices_table.php
routes/web.php (setup-production route improvement)
```

## Commit
```
fix: FK type mismatch on payment_memo_invoices + improve setup route output
763270a
```

## Log
- 2026-05-08 — Bug ditemukan saat deploy Phase 9b ke production
- 2026-05-08 — Fixed: hapus route cache + fix unsignedBigInteger → unsignedInteger
