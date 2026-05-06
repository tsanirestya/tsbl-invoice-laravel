# BUG-002 — Perbaikan Tampilan Export PDF (Reports)

## Status
- ✅ Fixed — 2026-05-05

## Priority
- Medium

## URL
`http://localhost/tsbl-invoice-laravel/public/reports`

## Deskripsi
Tampilan export PDF pada halaman Reports perlu diperbaiki. Layout, styling, atau konten PDF yang dihasilkan DomPDF belum sesuai ekspektasi.

## Agent yang Direkomendasikan
- `engineering-senior-developer` — PDF/DomPDF layout fix
- `design-ui-designer` — jika menyangkut visual layout

## Scope Investigasi
- [ ] Cek Blade view yang dipakai untuk PDF template
- [ ] Cek CSS inline / style yang masuk ke DomPDF
- [ ] Cek data yang dikirim ke view PDF
- [ ] Cek konfigurasi DomPDF (`config/dompdf.php`)
- [ ] Test render hasil PDF

## Files yang Mungkin Terdampak
```
app/Http/Controllers/ReportController.php
resources/views/reports/
resources/views/reports/pdf.blade.php (jika ada)
config/dompdf.php
```

## Notes
- DomPDF hanya support CSS subset — hindari flexbox/grid, gunakan `table`-based layout
- Font harus di-embed atau pakai font yang sudah di-bundle DomPDF
- Gunakan `@media print` atau CSS inline untuk PDF view

## Related
- [[DESIGN-SYSTEM]] — PDF layout guidelines
- [[TODO-PHASE-5-SETTINGS-REPORTS]] — Phase 5 scope

## Log
- 2026-05-05 — Bug dicatat, belum diinvestigasi
