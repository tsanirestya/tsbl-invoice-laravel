# BUG-006 — Production File Uploads 404 (Split Webroot Architecture)

→ Kembali ke: [[INDEX]]

## Status
✅ Fixed — 2026-05-08

## Symptom
Setelah upload logo perusahaan, navbar logo, favicon di Settings — gambar tidak muncul di UI.
Console browser:
```
uploads/settings/69fd8cb692220_1778224310.png  Failed to load resource: 404
uploads/settings/69fd8cb6949a6_1778224310.png  Failed to load resource: 404
```
Signature user dan dokumen partner juga tidak bisa diakses — 404 semua.

## Root Cause
Arsitektur deploy production memisahkan webroot dari `public/` di dalam app:

```
/home/.../
├── invoice.transentertainment.id/   ← webroot (copy dari public/)
│   ├── index.php
│   └── .htaccess
└── tsbl-invoice-laravel/            ← Laravel app root
    ├── public/                      ← BUKAN webroot
    └── storage/app/public/          ← storage disk
```

**Settings uploads (`SettingsController`):**
- `saveFile()` pakai `public_path('uploads/settings')` → tulis ke `/tsbl-invoice-laravel/public/uploads/settings/`
- `asset('uploads/settings/...')` resolve ke `invoice.transentertainment.id/uploads/settings/` — direktori berbeda → 404

**Signature & dokumen (`UserController`, `PartnerController`, `PaymentController`, dll):**
- Simpan pakai `->store(..., 'public')` → ke `storage/app/public/`
- `Storage::url()` generate `APP_URL/storage/...` → `invoice.transentertainment.id/storage/...`
- Webroot tidak punya direktori `storage/` dan `storage:link` tidak jalan di shared hosting FTP deploy → 404

## Fix

### 1. SettingsController — ganti ke Storage disk
**File:** `app/Http/Controllers/SettingsController.php`

Ganti `public_path()` + `file->move()` dengan `Storage::disk('public')->putFileAs()`.
Path tersimpan sebagai `storage/settings/filename.ext` — format yang sama dengan `asset('storage/...')`.

```php
private function saveFile(UploadedFile $file, ?string $old): string
{
    if ($old) {
        $oldKey = ltrim(preg_replace('#^storage/#', '', $old), '/');
        if ($oldKey && Storage::disk('public')->exists($oldKey)) {
            Storage::disk('public')->delete($oldKey);
        }
    }

    $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
    Storage::disk('public')->putFileAs('settings', $file, $filename);

    return 'storage/settings/' . $filename;
}
```

### 2. storage-proxy.php — proxy file server untuk webroot
**File:** `public/storage-proxy.php` *(baru)*

PHP script yang membaca file dari `storage/app/public/` dan stream ke browser.
Bekerja karena webroot sibling dengan app root:
```
__DIR__ . '/../tsbl-invoice-laravel/storage/app/public'  (production)
__DIR__ . '/../storage/app/public'                        (local fallback)
```

Dilengkapi path traversal protection.

### 3. .htaccess — route /storage/* ke proxy
**File:** `public/.htaccess`

```apache
# Proxy storage requests when symlink is absent (production shared hosting)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^storage/(.+)$ storage-proxy.php?path=$1 [L,QSA]
```

Rule ini hanya aktif kalau file tidak ada secara fisik:
- **Local:** symlink dari `storage:link` membuat file ada → proxy tidak aktif
- **Production:** tidak ada symlink → proxy aktif, stream dari storage

### 4. PDF views — fix logo path
**Files:** `resources/views/invoices/pdf.blade.php`, `resources/views/deposit-invoices/pdf.blade.php`

DomPDF butuh absolute file path, bukan URL. Ganti `public_path($logoPath)` dengan `Storage::disk('public')->path($key)`.

```php
$logoKey = ltrim(preg_replace('#^storage/#', '', $logoPath), '/');
$logoAbs = Storage::disk('public')->exists($logoKey)
    ? Storage::disk('public')->path($logoKey)
    : null;
```

## Commits
- `ad7dedc` — fix: resolve production 404 for uploaded files (initial attempt, DOCUMENT_ROOT)
- `7b7bace` — fix: use Storage disk for settings uploads, fix PDF logo path
- `c4f1f85` — fix: force PHP 8.2 on production shared hosting via .htaccess

## Bonus Fix — PHP Version
Bersamaan ditemukan production server default ke PHP < 8.2 → 500 error.

**Fix:** tambah ke `public/.htaccess`:
```apache
AddHandler application/x-httpd-php82 .php
```

## Lesson Learned
1. **Split webroot deploy** — kalau webroot bukan `public/` di dalam app, semua path berbasis `public_path()` dan `storage:link` tidak berfungsi
2. **Gunakan Storage facade konsisten** — `->store(..., 'public')` + `Storage::url()` untuk semua file upload, bukan mix dengan `public_path()` + `file->move()`
3. **Storage proxy** — solusi untuk shared hosting FTP-only tanpa akses SSH untuk buat symlink
4. **Force PHP version** via `.htaccess` diperlukan kalau hosting default ke versi lama

## Related
- [[LARAVEL-STRUCTURE]] — storage & file serving architecture
- [[BUG-005-PRODUCTION-SETUP-FK-MIGRATION]] — production deploy issues sebelumnya
