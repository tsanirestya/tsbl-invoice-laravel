# BUG-004 — Invoice PDF: Subtotal & Grand Total Tidak Sejajar Kolom Jumlah

## Status
- ✅ Fixed — 2026-05-05

## Priority
- High

## URL
`http://localhost/tsbl-invoice-laravel/public/invoices/3/pdf`

## Deskripsi
Pada PDF invoice, baris **Subtotal** dan **Grand Total** tidak rata/sejajar di bawah kolom **Jumlah** (amount column). Angka total tampil di posisi yang salah — bukan flush-right di bawah kolom jumlah item.

## Agent yang Direkomendasikan
- `engineering-senior-developer` — DomPDF table/colspan fix

## Scope Investigasi
- [ ] Cek Blade template PDF invoice (`resources/views/invoices/pdf.blade.php` atau sejenisnya)
- [ ] Pastikan baris subtotal/total pakai `<tr>` + `<td colspan>` yang benar
- [ ] Cek lebar kolom tabel — kemungkinan colspan salah hitung
- [ ] Pastikan CSS `text-align: right` diterapkan pada cell total
- [ ] Test render ulang setelah fix

## Root Cause (Hipotesis)
DomPDF tidak support `flexbox`/`grid` — jika layout total pakai flex atau margin trick, posisi akan meleset. Harus pakai `<table>` dengan `colspan` eksplisit dan `text-align: right`.

## Files yang Mungkin Terdampak
```
resources/views/invoices/pdf.blade.php (atau template PDF invoice)
app/Http/Controllers/InvoiceController.php — method pdf/download
```

## Notes
- DomPDF: gunakan `table`-based layout, bukan flexbox/grid
- Colspan harus = jumlah kolom sebelum kolom amount (misal: No + Deskripsi + Qty + Harga = 4 kolom, maka `colspan="4"` + 1 cell amount)
- CSS inline lebih aman di DomPDF daripada `<style>` block

## Log
- 2026-05-05 — Bug dicatat dari review PDF invoice #3, belum diinvestigasi
