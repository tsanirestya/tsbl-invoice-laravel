# 🗂️ TSBL Invoice System — Obsidian Index

> **Hub utama** — semua note terhubung dari sini.
> Buka file ini pertama kali di Obsidian untuk navigasi keseluruhan project.

---

## 📋 Planning
- [[MASTER-PLAN]] — Roadmap lengkap semua phase, decisions, status
- [[LARAVEL-STRUCTURE]] — Architecture, module map, route groups, storage
- [[FEATURE-PARTNER-DEPOSIT]] — Feature planning: sistem deposit partner (Phase 6)
- [[FEATURE-CREDIT-FACILITY]] — Feature planning: credit facility per partner (Phase 8)
- [[FEATURE-BATCH-CREDIT-PAYMENT]] — Feature planning: batch credit payment (Phase 9)
- [[FEATURE-PAYMENT-MEMO]] — Feature planning: memo pengajuan pembayaran ke partner (Phase 9b)

## 🗄️ Database
- [[SCHEMA]] — Semua table, columns, FK, data existing

## 🎨 UI/UX
- [[DESIGN-SYSTEM]] — Color palette, layout, components, PDF layout

## ✅ TODO per Phase
| Phase | File | Status |
|---|---|---|
| Phase 1 — Foundation | [[TODO-PHASE-1-FOUNDATION]] | ✅ Done |
| Phase 2 — Core Modules | [[PHASE-2-SUMMARY]] | ✅ Done |
| Phase 3 — Invoice Engine | [[PHASE-3-SUMMARY]] | ✅ Done |
| Phase 4 — Finance Operations | [[TODO-PHASE-4-FINANCE]] | 🔲 Pending |
| Phase 5 — Settings & Reports | [[TODO-PHASE-5-SETTINGS-REPORTS]] | 🔲 Pending |
| Phase 6 — Partner Deposit | [[TODO-PHASE-6-PARTNER-DEPOSIT]] | 📋 Planning |
| Phase 7 — Excel Import System | [[TODO-PHASE-7-EXCEL-IMPORT]] | ✅ Done |
| Phase 8 — Credit Facility | [[TODO-PHASE-8-CREDIT-FACILITY]] | 📋 Planning |
| Phase 9a — Memo Tagihan | [[FEATURE-PAYMENT-MEMO]] | ✅ Done (2026-05-08) |
| Phase 9b — Batch Credit Payment | [[FEATURE-BATCH-CREDIT-PAYMENT]] | 📋 Planning |

## 📦 Features (akan dibuat per feature selesai)
→ Folder: `Features/`

## 🐛 Bugs
→ Folder: `Bugs/`
- [[BUG-001-LOGIN-FORM-ACTION]] — Login 404 fix (subdirectory URL issue)
- [[BUG-002-PDF-EXPORT-LAYOUT]] — Perbaikan tampilan export PDF di halaman Reports 🔲
- [[BUG-003-PRODUCTS-PAGINATION]] — Pagination berantakan di halaman /products 🔴
- [[BUG-004-INVOICE-PDF-SUBTOTAL-ALIGNMENT]] — Subtotal & Grand Total tidak sejajar kolom jumlah di PDF invoice 🔲
- [[BUG-005-PRODUCTION-SETUP-FK-MIGRATION]] — Production deploy: route cache 404 + FK type mismatch errno 150 ✅
- [[BUG-006-PRODUCTION-FILE-UPLOADS-404]] — Production: uploads 404 akibat split webroot + PHP version fix ✅
- [[BUG-007-PROD-500-TROUBLESHOOTING-GUIDE]] — **GUIDE: Prod 500 debug step-by-step + deploy.php template** ✅

## 🔍 Audit
- [[AUDIT-PROMPT-TEMPLATE]] — Template prompt audit menyeluruh (security, fraud, finance, ops)
- [[AUDIT-REPORT-2026-05-08]] — Comprehensive audit result: 28 findings (4 CRITICAL), Health Score 31/100
- [[TODO-AUDIT-2026-05-08]] — Remediation TODO: 28 task (4 IMMEDIATE, 7 this week, 13 this month, 4 long term)

## 📝 Logs
→ Folder: `Logs/`
- [[LOG-2026-05-05]] — Phase 1 implementation log

## 🚀 Development Phases
→ Folder: `Development-Phases/`
- [[PHASE-1-SUMMARY]] — Foundation summary & lessons learned

---

## Quick Reference
| Item | Value |
|---|---|
| App URL | `http://localhost/tsbl-invoice-laravel/public` |
| DB | `tsbl_invoice` (MySQL XAMPP) |
| Admin login | `admin@tsbl.com` / `admin123` |
| PHP | 8.2.12 |
| Laravel | 11.x |
| Stack | Laravel + Blade + Bootstrap 5 + DomPDF |
