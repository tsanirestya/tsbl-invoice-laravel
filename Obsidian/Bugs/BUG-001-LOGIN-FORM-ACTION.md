# BUG-001 — Login Form 404 (Subdirectory URL Issue)

→ Kembali ke: [[INDEX]]

## Status
✅ Fixed — 2026-05-05

## Symptom
Setelah membuka halaman login dan submit form, muncul:
```
Not Found
The requested URL was not found on this server.
Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12
```

## Root Cause
Login form menggunakan hardcoded `action="/login"`.
Ini menghasilkan URL absolut `http://localhost/login` — bukan `http://localhost/tsbl-invoice-laravel/public/login`.
Laravel app jalan di subdirectory `/tsbl-invoice-laravel/public/`, bukan di root.

## Fix
File: `resources/views/auth/login.blade.php`

**Before:**
```html
<form method="POST" action="/login">
```

**After:**
```html
<form method="POST" action="{{ route('login') }}">
```

`route('login')` menggunakan `APP_URL` sebagai base, menghasilkan URL yang benar.

## Lesson Learned
Di project Laravel yang jalan di subdirectory:
- **JANGAN** pakai hardcoded `/path` di form action atau link
- **SELALU** pakai `route('name')` atau `url('/path')` helper
- `APP_URL` di `.env` harus set dengan benar ke full subdirectory URL

## Related
- [[LARAVEL-STRUCTURE]] — APP_URL config notes
- [[TODO-PHASE-1-FOUNDATION]] — phase 1 known issues
