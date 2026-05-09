# BUG-007 — Production 500 Error: Troubleshooting Guide

**Date:** 2026-05-10
**Status:** Resolved
**Severity:** Critical — seluruh app tidak bisa diakses

---

## Root Cause (Kasus Ini)

1. **Controller baru tidak di-commit** → `routes/web.php` import class yang tidak ada di prod → PHP fatal error saat bootstrap
2. **View cache lama di server** → `route('password.request')` gagal karena view cache dikompilasi dengan route cache lama

---

## Deployment Architecture (WAJIB PAHAM)

```
GitHub repo (local push)
    ↓ GitHub Actions (.github/workflows/deploy.yml)
    ↓ FTP Deploy (incremental) → dua target:

[1] /home2/transen2/tsbl-invoice-laravel/   ← Laravel root (app, routes, models, vendor, dll)
    vendor/ → ✅ di-deploy otomatis, incremental (hanya yang berubah)

[2] /home2/transen2/invoice.transentertainment.id/   ← Webroot (isi public/)
    index.php → referensi ke ../tsbl-invoice-laravel/vendor/ dan bootstrap/app.php
```

**Catatan penting:**
- `bootstrap/cache/` tidak di-deploy → server selalu baca config/routes fresh
- `storage/framework/views/` tidak di-deploy tapi BISA ada cache lama di server
- `vendor/` sudah otomatis via FTP incremental — **tidak perlu WinSCP manual lagi**
- FTP deploy pakai `.ftp-deploy-sync-state.json` untuk track perubahan — hanya upload file yang berbeda

---

## Checklist Debug 500 (Jalankan Berurutan)

### Step 1 — Cek apakah ada file yang belum di-commit

```bash
git status
```

**Yang dicari:** file `??` (untracked) yang direferensikan di `routes/web.php` atau `AppServiceProvider`.

**Fix:** `git add [file] && git commit && git push`

> Ini penyebab paling umum. Cek ini PERTAMA sebelum yang lain.

---

### Step 2 — Buat `public/deploy.php` untuk baca error log

Karena tidak ada SSH, buat file ini, push, akses via browser:

```php
<?php
if (($_GET['token'] ?? '') !== 'TOKEN_RAHASIA') { http_response_code(403); exit; }

$base = dirname(__DIR__) . '/tsbl-invoice-laravel';
$logFile = $base . '/storage/logs/laravel.log';
$lines = file_exists($logFile) ? array_slice(file($logFile), -100) : ['Log not found'];

echo '<pre>' . htmlspecialchars(implode('', $lines)) . '</pre>';
```

**Baca error paling bawah** — itu error terbaru.

**Error umum dan artinya:**

| Error | Penyebab | Fix |
|-------|----------|-----|
| `Class "App\Http\Controllers\XxxController" not found` | Controller belum di-commit | Step 1 |
| `Route [xxx] not defined` | View cache lama + route cache stale | Step 3 |
| `SQLSTATE: Table 'xxx' doesn't exist` | Migration belum jalan | Step 4 |
| `No application encryption key` | `.env` tidak ada APP_KEY | Cek deploy.yml |
| `vendor/autoload.php not found` | vendor/ belum terSync (deploy gagal/timeout) | Cek GitHub Actions log, re-trigger deploy |

---

### Step 3 — Clear semua cache (view, route, config)

Update `deploy.php` agar bootstrap Laravel dan jalankan Artisan:

```php
<?php
if (($_GET['token'] ?? '') !== 'TOKEN_RAHASIA') { http_response_code(403); exit; }

$base = dirname(__DIR__) . '/tsbl-invoice-laravel';
define('LARAVEL_START', microtime(true));
require $base . '/vendor/autoload.php';
$app = require_once $base . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo '<pre>';
foreach (['view:clear', 'route:clear', 'config:clear'] as $cmd) {
    \Illuminate\Support\Facades\Artisan::call($cmd);
    echo "$cmd\n" . htmlspecialchars(\Illuminate\Support\Facades\Artisan::output());
}
echo '</pre>';
```

> Ini fix untuk error `Route [xxx] not defined` yang datang dari view cache lama.

---

### Step 4 — Jalankan migration

Tambahkan ke deploy.php setelah cache clear:

```php
\Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
echo "migrate\n" . htmlspecialchars(\Illuminate\Support\Facades\Artisan::output());
```

---

### Step 5 — Cek vendor/ ada di server

Kalau `deploy.php` error `vendor/autoload.php not found`:

1. Cek tab **Actions** di GitHub — apakah deploy step terakhir sukses atau timeout?
2. Kalau GitHub Actions timeout → naikkan `timeout-minutes` di `deploy.yml` atau re-trigger manual
3. Kalau darurat dan perlu cepat → upload manual via **WinSCP**:
   - Source: `D:\XAMPP NEW\htdocs\tsbl-invoice-laravel\vendor\`
   - Target: `/home2/transen2/tsbl-invoice-laravel/vendor/`

> **Sejak 2026-05-10:** vendor/ sudah di-deploy otomatis via FTP incremental. Upload manual WinSCP hanya untuk keadaan darurat.

---

## Template `deploy.php` Lengkap (Siap Pakai)

Simpan template ini, ganti `TOKEN_RAHASIA` setiap kali pakai:

```php
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (($_GET['token'] ?? '') !== 'TOKEN_RAHASIA') {
    http_response_code(403); exit('Forbidden');
}

$base = dirname(__DIR__) . '/tsbl-invoice-laravel';

// -- Mode: log (default) atau clear+migrate (?mode=fix)
if (($_GET['mode'] ?? 'log') === 'log') {
    $log = $base . '/storage/logs/laravel.log';
    $lines = file_exists($log) ? array_slice(file($log), -150) : ['Log not found'];
    echo '<pre>' . htmlspecialchars(implode('', $lines)) . '</pre>';
    exit;
}

// mode=fix
define('LARAVEL_START', microtime(true));
require $base . '/vendor/autoload.php';
$app = require_once $base . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo '<pre>';
foreach (['view:clear', 'route:clear', 'config:clear'] as $cmd) {
    \Illuminate\Support\Facades\Artisan::call($cmd);
    echo "$cmd: " . htmlspecialchars(\Illuminate\Support\Facades\Artisan::output()) . "\n";
}
\Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
echo "migrate: " . htmlspecialchars(\Illuminate\Support\Facades\Artisan::output());
echo "\nDone.";
echo '</pre>';
```

**Cara pakai:**
- Baca log: `https://invoice.transentertainment.id/deploy.php?token=XXX`
- Fix cache + migrate: `https://invoice.transentertainment.id/deploy.php?token=XXX&mode=fix`

**WAJIB hapus setelah selesai:**
```bash
git rm public/deploy.php && git commit -m "chore: remove deploy.php" && git push
```

---

## SOP Setiap Deploy (Checklist)

- [ ] `git status` — pastikan tidak ada `??` untracked file yang direferensikan di routes/AppServiceProvider
- [ ] Kalau ada file baru → `git add` dulu baru commit
- [ ] Kalau ada migration baru → siapkan `deploy.php` dengan `mode=fix`
- [ ] Kalau ada package baru di `composer.json` → tidak perlu manual, FTP deploy otomatis upload vendor yang berubah
- [ ] Setelah `deploy.php` dipakai → langsung hapus dari repo

---

## Catatan Teknis Server

| Item | Value |
|------|-------|
| Hosting | cPanel shared hosting |
| SSH | Tidak tersedia |
| Deploy method | GitHub Actions → FTP Deploy |
| Laravel root | `/home2/transen2/tsbl-invoice-laravel/` |
| Webroot | `/home2/transen2/invoice.transentertainment.id/` |
| vendor/ | ✅ Otomatis via FTP incremental (sejak 2026-05-10) |
| shell_exec | Disabled di hosting ini |
| PHP binary | `PHP_BINARY` tersedia |
