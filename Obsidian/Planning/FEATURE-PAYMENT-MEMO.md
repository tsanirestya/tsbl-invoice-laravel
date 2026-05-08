# Feature Planning: Memo Pengajuan Pembayaran (Phase 9b)

> Finance generate surat tagihan formal ke partner yang masih punya outstanding invoice credit,
> sebagai pengingat resmi sebelum partner melakukan pembayaran.

**Status:** 📋 Planning  
**Date:** 2026-05-08  
**Depends on:** Phase 8 (Credit Facility) ✅  
**Berkaitan dengan:** [[FEATURE-BATCH-CREDIT-PAYMENT]] (memo ini pre-step sebelum batch payment)

---

## Alur dalam Konteks Bisnis

```
Finance lihat dashboard → ada partner dengan invoice overdue / hampir jatuh tempo
        ↓
Finance buat Memo Pengajuan → pilih partner → centang invoice yang mau ditagih
        ↓
Sistem generate PDF "MEMO OUTSTANDING PAYMENT"
        ↓
Finance cetak / kirim ke partner
        ↓
Partner terima memo → transfer pembayaran
        ↓
Finance input Batch Credit Payment (Phase 9)
```

---

## Keputusan Desain

| Pertanyaan | Keputusan |
|---|---|
| Trigger | Finance buat manual per partner dari dashboard atau halaman partner |
| Invoice yang masuk memo | Semua outstanding, bisa checklist sebagian atau semua |
| Nomor memo | Format `MP-{YYYYMM}-{seq}` — contoh: `MP-202605-001` |
| Batas bayar di memo | 7 hari dari tanggal memo dibuat (`created_at + 7 days`) |
| Dashboard alert | Finance otomatis lihat partner mana yang punya due/overdue invoice |
| Simpan ke DB | Ya — ada riwayat memo pernah dikirim ke partner |

---

## Trigger Point: Dashboard Alert

Finance perlu tahu **siapa yang harus ditagih sekarang** tanpa harus buka tiap partner satu per satu.

### Widget "Partner Perlu Ditagih" di Dashboard

```
┌──────────────────────────────────────────────────────────┐
│  ⚠ Partner dengan Outstanding Kredit                    │
│                                                          │
│  PT ABC Mitra      3 invoice OVERDUE     Rp 18.000.000  │
│                    [+ Buat Memo]                         │
│                                                          │
│  PT XYZ Travel     2 invoice jatuh tempo 5 hari         │
│                    Rp 12.000.000                         │
│                    [+ Buat Memo]                         │
│                                                          │
│  CV Karya Indah    1 invoice jatuh tempo 2 hari         │
│                    Rp 5.500.000                          │
│                    [+ Buat Memo]                         │
│                                                          │
│  [Lihat semua →]                                         │
└──────────────────────────────────────────────────────────┘
```

Urutan prioritas: OVERDUE dulu → lalu UNPAID terdekat jatuh temponya.  
Tombol `[+ Buat Memo]` langsung buka form create dengan partner sudah terpilih.

### Trigger dari Halaman Partner Show

Di halaman `/partners/{id}`, jika ada outstanding invoice → tampil tombol:

```
[📄 Buat Memo Pengajuan Pembayaran]
```

---

## Database

### Tabel Baru: `payment_memos`

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | int PK | — |
| memo_no | string(30) unique | MP-202605-001 |
| partner_id | unsignedInt FK | partner yang ditagih |
| memo_date | date | tanggal memo dibuat |
| payment_deadline | date | memo_date + 7 hari |
| notes | text nullable | catatan internal |
| created_by | unsignedInt FK | user finance yang buat |
| created_at | datetime | — |

### Tabel Baru: `payment_memo_invoices` (pivot)

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | int PK | — |
| payment_memo_id | unsignedInt FK | — |
| invoice_id | unsignedInt FK | invoice yang dimasukkan ke memo |
| grand_total | decimal(15,2) | snapshot nilai saat memo dibuat |
| sisa_tagihan | decimal(15,2) | snapshot sisa bayar saat memo dibuat |

> Snapshot nilai disimpan karena invoice bisa berubah status setelah memo dibuat.
> Memo harus mencerminkan kondisi saat dikirim, bukan kondisi hari ini.

---

## Model & Relasi

```
PaymentMemo
  → belongsTo Partner
  → belongsTo User (created_by)
  → hasMany PaymentMemoInvoice
  → hasManyThrough Invoice (via PaymentMemoInvoice)

PaymentMemoInvoice
  → belongsTo PaymentMemo
  → belongsTo Invoice
```

---

## Routes

```
GET    /payment-memos                          → index (riwayat semua memo)
GET    /payment-memos/create?partner_id={id}   → form (partner pre-selected jika dari shortcut)
POST   /payment-memos                          → store
GET    /payment-memos/{id}                     → show
GET    /payment-memos/{id}/pdf                 → generate & stream PDF (DomPDF)
DELETE /payment-memos/{id}                     → hapus memo (hanya hapus record, bukan invoice)
```

---

## UI Flow

### Form Create `/payment-memos/create`

```
┌─────────────────────────────────────────────────────┐
│  Buat Memo Pengajuan Pembayaran                     │
│                                                     │
│  Partner     [PT ABC Mitra ▼]  ← pre-fill jika     │
│                                  dari shortcut      │
│                                                     │
│  ── setelah partner dipilih (AJAX) ─────────────── │
│                                                     │
│  Invoice Outstanding:                               │
│                                                     │
│  ☑  INV-001  01/04  JT: 30/04  ⚠ OVERDUE  Rp 5jt  │
│  ☑  INV-002  05/04  JT: 05/05  ⏰ 3 hari   Rp 8jt  │
│  ☑  INV-003  10/04  JT: 10/05  ⏰ 8 hari   Rp 7jt  │
│                                                     │
│  [☑ Pilih Semua]                                   │
│                                                     │
│  Total Tagihan Terpilih : Rp 20.000.000             │
│  Batas Pembayaran       : 15 Mei 2026 (otomatis)   │
│                                                     │
│  Catatan Internal (opsional)                        │
│  [________________________________________]         │
│                                                     │
│  [Buat & Download Memo PDF]                         │
└─────────────────────────────────────────────────────┘
```

Keterangan ikon status di tabel invoice:
- `⚠ OVERDUE` → merah
- `⏰ N hari` → kuning jika ≤ 7 hari, abu jika > 7 hari

### Halaman Show `/payment-memos/{id}`

```
┌─────────────────────────────────────────────────────┐
│  Memo MP-202605-001                                 │
│  Partner: PT ABC Mitra                              │
│  Dibuat: 08/05/2026 oleh Rina Dewi                  │
│  Batas Bayar: 15/05/2026                            │
│                                                     │
│  Invoice dalam memo ini:                            │
│  INV-001  Rp 5.000.000  (saat memo: OVERDUE)        │
│  INV-002  Rp 8.000.000  (saat memo: UNPAID)         │
│  INV-003  Rp 7.000.000  (saat memo: UNPAID)         │
│  Total    Rp 20.000.000                             │
│                                                     │
│  Status invoice sekarang:                           │
│  INV-001  → PAID ✓   (sudah lunas setelah memo)     │
│  INV-002  → UNPAID                                  │
│  INV-003  → UNPAID                                  │
│                                                     │
│  [📄 Download PDF]   [🗑 Hapus Memo]                │
└─────────────────────────────────────────────────────┘
```

> Halaman show menampilkan 2 kondisi: status saat memo dibuat (snapshot) dan status terkini invoice.

---

## Rancangan Visual PDF Memo (A4 Portrait)

```
╔══════════════════════════════════════════════════════════════════╗
║                                                                  ║
║  [LOGO]    PT. NAMA PERUSAHAAN                                   ║
║            Jl. Alamat Perusahaan, Kota, 12345                   ║
║            Telp: 021-XXXXXXX  |  finance@perusahaan.com         ║
║                                                                  ║
║  ════════════════════════════════════════════════════════════    ║
║                                                                  ║
║               MEMO OUTSTANDING PAYMENT                           ║
║                                                                  ║
║  ════════════════════════════════════════════════════════════    ║
║                                                                  ║
║  No. Memo  : MP-202605-001             Tanggal : 08 Mei 2026    ║
║                                                                  ║
║  ────────────────────────────────────────────────────────────   ║
║                                                                  ║
║  Kepada Yth.                                                     ║
║  PT. ABC MITRA UTAMA                                             ║
║  u.p. Bpk. Budi Santoso                                          ║
║                                                                  ║
║  Di Tempat                                                       ║
║                                                                  ║
║  ────────────────────────────────────────────────────────────   ║
║                                                                  ║
║  Dengan hormat,                                                  ║
║                                                                  ║
║  Pertama-tama kami mengucapkan terima kasih atas kerja sama     ║
║  dan kepercayaan yang telah terjalin antara PT. Nama Perusahaan ║
║  dan PT. ABC Mitra Utama.                                        ║
║                                                                  ║
║  Bersama memo ini, kami ingin menginformasikan bahwa hingga     ║
║  saat ini masih terdapat outstanding pembayaran atas            ║
║  invoice-invoice berikut:                                        ║
║                                                                  ║
║  ┌────┬─────────────┬────────────┬────────────┬─────────────┐  ║
║  │ No │ No. Invoice │ Tgl Invoice│ Jth. Tempo │ Sisa Tagihan│  ║
║  ├────┼─────────────┼────────────┼────────────┼─────────────┤  ║
║  │  1 │ INV-001     │ 01/04/2026 │ 30/04/2026 │ Rp 5.000.000│  ║
║  │  2 │ INV-002     │ 05/04/2026 │ 05/05/2026 │ Rp 8.000.000│  ║
║  │  3 │ INV-003     │ 10/04/2026 │ 10/05/2026 │ Rp 7.000.000│  ║
║  ├────┴─────────────┴────────────┴────────────┼─────────────┤  ║
║  │                          Total Outstanding │Rp 20.000.000│  ║
║  └────────────────────────────────────────────┴─────────────┘  ║
║                                                                  ║
║  Sehubungan dengan hal tersebut, kami mohon kesediaannya untuk  ║
║  dapat melakukan pembayaran atas invoice-invoice tersebut       ║
║  selambat-lambatnya pada tanggal 15 Mei 2026.                   ║
║  Pembayaran tepat waktu sangat kami harapkan guna menjaga       ║
║  kelancaran administrasi dan kerja sama yang baik di antara     ║
║  kedua belah pihak.                                              ║
║                                                                  ║
║  Pembayaran dapat dilakukan melalui transfer ke rekening:       ║
║                                                                  ║
║    Bank        : [Nama Bank dari Settings]                       ║
║    No. Rekening: [Nomor Rekening dari Settings]                  ║
║    Atas Nama   : [Nama Rekening dari Settings]                   ║
║                                                                  ║
║  Apabila pembayaran telah dilakukan, kami mohon bantuannya      ║
║  untuk mengirimkan bukti pembayaran kepada kami agar dapat      ║
║  segera kami tindak lanjuti pada sistem kami.                   ║
║                                                                  ║
║  Demikian memo ini kami sampaikan. Atas perhatian, kerja sama,  ║
║  dan itikad baiknya kami ucapkan terima kasih.                  ║
║                                                                  ║
║                                           Hormat kami,          ║
║                                                                  ║
║                                                                  ║
║                                                                  ║
║                                    Rina Dewi                    ║
║                                    Finance                       ║
║                                    PT. Nama Perusahaan          ║
║                                                                  ║
║  ════════════════════════════════════════════════════════════    ║
║  MP-202605-001 · Digenerate pada 08/05/2026 14:32               ║
╚══════════════════════════════════════════════════════════════════╝
```

---

## Sumber Data PDF

| Elemen PDF | Sumber |
|---|---|
| Logo, nama perusahaan, alamat | `Setting` (company_name, company_logo, address) |
| No. rekening tujuan bayar | `Setting` (bank_name, bank_account_no, bank_account_name) |
| No. Memo, tanggal | `payment_memos.memo_no`, `memo_date` |
| Nama partner, PIC | `partners.nama_partner`, `pic_partner` |
| Tabel invoice | `payment_memo_invoices` (snapshot: grand_total, sisa_tagihan) + invoice_no, invoice_date, due_date dari `invoices` |
| Total outstanding | SUM `sisa_tagihan` dari snapshot |
| Batas bayar | `payment_memos.payment_deadline` |
| Nama penandatangan | `users.full_name` (created_by) |

---

## Elemen Kondisional PDF

| Elemen | Kapan Tampil |
|---|---|
| Nama PIC (`u.p. ...`) | Hanya jika `pic_partner` tidak kosong |
| Logo perusahaan | Hanya jika `Setting('company_logo')` ada |
| Blok rekening bank | Selalu tampil (wajib ada di Settings) |
| Footer no. memo + timestamp | Selalu |

---

## Files yang Dibuat

| File | Keterangan |
|---|---|
| `database/migrations/..._create_payment_memos_table.php` | Tabel header memo |
| `database/migrations/..._create_payment_memo_invoices_table.php` | Tabel pivot + snapshot |
| `app/Models/PaymentMemo.php` | Model + relasi |
| `app/Models/PaymentMemoInvoice.php` | Model pivot |
| `app/Http/Controllers/PaymentMemoController.php` | index, create, store, show, pdf, destroy |
| `resources/views/payment-memos/index.blade.php` | Riwayat memo |
| `resources/views/payment-memos/create.blade.php` | Form + AJAX invoice loader |
| `resources/views/payment-memos/show.blade.php` | Detail + status terkini |
| `resources/views/payment-memos/pdf.blade.php` | Template PDF DomPDF |

## Files yang Diubah

| File | Perubahan |
|---|---|
| `routes/web.php` | Tambah resource routes + AJAX outstanding invoices |
| `resources/views/dashboard/index.blade.php` | Widget "Partner Perlu Ditagih" |
| `resources/views/partners/show.blade.php` | Tombol shortcut "Buat Memo" |
| `resources/views/layouts/app.blade.php` | Tambah menu "Memo Tagihan" |

---

## Nomor Memo

Format: `MP-{YYYYMM}-{seq padded 3}` → `MP-202605-001`  
Generate di controller `store()`: ambil MAX sequence bulan ini + 1.

---

## Edge Cases

| Skenario | Handling |
|---|---|
| Partner tidak ada outstanding invoice | Form tampil pesan "Tidak ada invoice outstanding", form disabled |
| Semua invoice sudah PAID setelah memo dibuat | Show page tetap tampil snapshot, dengan note "Semua invoice sudah lunas" |
| Memo dihapus | Hanya hapus record `payment_memos` + `payment_memo_invoices` — tidak mempengaruhi invoice |
| Rekening bank belum diisi di Settings | PDF tampil placeholder `[Belum diisi — lengkapi di Settings]` |
