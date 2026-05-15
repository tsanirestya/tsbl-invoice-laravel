# Phase 11 — Product Auto-Classification

- **Date:** 2026-05-15
- **Branch:** main
- **Agent used:** Backend Architect
- **Status:** Migration ready, pending `php artisan migrate`

## Objective

Backfill `market_type` dan tambah dua kolom baru (`sub_market_type`, `sub_payment_mode`) pada tabel `products` berdasarkan keyword detection dari `product_name`.

---

## Rules Finalized

### market_type
| Signal | Value |
|--------|-------|
| Name contains: `foreign`, `adult`, `child` | `foreign` |
| Name matches regex `\b\d+[AC]\b` (e.g. 2A, 3C, 4A) | `foreign` |
| Name contains: `local`, `lokal`, `domestic`, `domestik` | `domestic` |
| No match | `foreign` (default) |

> Regex `\b\d+[AC]\b` detects pax-count shorthand — `2A` = 2 Adult, `3C` = 3 Child.

### sub_market_type
| Signal | Value |
|--------|-------|
| Name contains `child` OR matches `\b\d+C\b` | `child` |
| Everything else | `adult` |

### sub_payment_mode
| Signal | Value |
|--------|-------|
| Name contains `net` (case-insensitive, covers NET & NETT) | `NETT` |
| Everything else | `GROSS` |

---

## Files Changed

| File | Change |
|------|--------|
| `database/migrations/2026_05_15_000001_add_sub_classification_to_products_table.php` | Add `sub_market_type`, `sub_payment_mode` columns |
| `database/migrations/2026_05_15_000002_backfill_product_classifications.php` | Backfill all 3 columns on existing data |
| `app/Models/Product.php` | Add `sub_market_type`, `sub_payment_mode` to `$fillable` |

---

## How to Apply

Run migrations via the existing `/run-migrations` route, or locally:

```bash
php artisan migrate
```

---

## Commit

```
feat: add sub_market_type and sub_payment_mode with auto-classification backfill
```
