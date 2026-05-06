# BUG-003 — Products Pagination Berantakan

→ Kembali ke: [[INDEX]]

## Status
✅ Fixed — 2026-05-05

## Symptom
Halaman `/products` — pagination layout berantakan/rusak secara visual.

**URL:** `http://localhost/tsbl-invoice-laravel/public/products`

## Likely Root Cause
Belum diselidiki. Kemungkinan:
- Laravel default pagination links tidak pakai Bootstrap 5 style
- Missing `->links('pagination::bootstrap-5')` di view
- CSS pagination class tidak match Bootstrap 5.3

## Fix (TODO)
Cek:
1. `resources/views/products/index.blade.php` — apakah `{{ $products->links() }}` sudah pakai Bootstrap 5 preset
2. `app/Providers/AppServiceProvider.php` — apakah `Paginator::useBootstrapFive()` sudah di-set
3. Jika belum, tambahkan di `AppServiceProvider::boot()`:
   ```php
   use Illuminate\Pagination\Paginator;
   Paginator::useBootstrapFive();
   ```

## Related
- [[INDEX]]
- `resources/views/products/index.blade.php`
- `app/Providers/AppServiceProvider.php`
