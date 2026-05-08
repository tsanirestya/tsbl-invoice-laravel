---
title: Feature Planning — Credit Facility
status: APPROVED
created: 2026-05-07
phase: 8
---

# Feature Planning — Credit Facility

→ Kembali ke: [[MASTER-PLAN]]
→ TODO: [[TODO-PHASE-8-CREDIT-FACILITY]]

---

## Latar Belakang

Partner bertipe CREDIT bisa menerima invoice dan bayar belakangan (kredit).
Sistem perlu:
- Membatasi total outstanding per partner (credit limit)
- Mengklasifikasikan partner ke dalam tier/class kredit
- Memvisualkan penggunaan kredit di dashboard
- Memvalidasi saat buat invoice agar tidak melewati limit

---

## Keputusan Desain

| Keputusan | Pilihan | Alasan |
|---|---|---|
| Tabel credit_classes | Buat baru | Fleksibel, admin bisa atur |
| Credit class assign | Auto dari limit_credit + bisa override manual | Best of both worlds |
| Invoice validation | Soft block (warning >80%, error+reason >100%) | Tidak hard-block ops |
| Credit aging buckets | Editable via Settings page | User minta bisa edit |
| Tabel invoice khusus kredit | Tidak dibuat | Invoice biasa + partner type = cukup |
| Interest/denda | Tidak dibangun | Tidak diminta |
| Credit approval workflow | Tidak dibangun | Overkill untuk skala ini |

---

## Struktur Data Baru

### Tabel `credit_classes`
```
id                  bigint PK
name                varchar(100)        -- "Platinum", "Gold", "Silver", "Bronze"
color               varchar(20)         -- Bootstrap color: "primary", "warning", "success", "secondary"
min_limit           decimal(15,2)       -- batas bawah limit kredit untuk masuk class ini
max_limit           decimal(15,2) null  -- null = tidak terbatas (kelas tertinggi)
description         text null
sort_order          int default 0       -- urutan tampil
created_at / updated_at
```

### Perubahan Tabel `partners`
```
credit_class_id     bigint FK → credit_classes.id, nullable, SET NULL
-- partners.limit_credit sudah ada, tetap pakai
-- partners.payment_type tetap ada (cleanup ke enum CASH|CREDIT|DEPOSIT nanti)
```

### Settings Keys Baru
```
credit_aging_bucket_1   = 30    (hari — bucket pertama)
credit_aging_bucket_2   = 60    (hari)
credit_aging_bucket_3   = 90    (hari)
credit_aging_bucket_4   = 120   (hari — lebih dari ini masuk "120+ hari")
credit_warning_threshold = 80   (persen — warning level)
```

---

## Logic Credit Usage

**Credit Used** = SUM(`grand_total`) dari invoice partner dengan status UNPAID + PARTIAL + OVERDUE

**Credit Available** = `limit_credit` - `creditUsed()`

**Utilization %** = (`creditUsed()` / `limit_credit`) × 100

**Credit Status:**
- `NORMAL`     = utilization < warning threshold (default 80%)
- `WARNING`    = utilization ≥ warning threshold dan ≤ 100%
- `OVER_LIMIT` = utilization > 100%

---

## Credit Aging Logic

Buckets dikonfigurasi dari Settings. Contoh default (bisa diubah user):

| Bucket | Range |
|---|---|
| Current | ≤ 0 hari (belum jatuh tempo) |
| 1–30 hari | sudah jatuh tempo ≤ 30 hari |
| 31–60 hari | 31–60 hari |
| 61–90 hari | 61–90 hari |
| 91–120 hari | 91–120 hari |
| >120 hari | lebih dari 120 hari |

Dihitung dari: `today` - `due_date` invoice yang belum lunas.

---

## Auto-Assign Credit Class

Logic saat partner disimpan (create/update):
1. Ambil semua `credit_classes` order by `min_limit` ASC
2. Cocokkan `partner.limit_credit` ke class yang sesuai range
3. Set `credit_class_id` otomatis
4. Admin tetap bisa override manual via form

---

## Yang Tidak Dibangun (Out of Scope)

- Tabel `credit_invoices` terpisah
- Perhitungan bunga/denda keterlambatan
- Credit approval workflow (multi-step)
- Auto-adjust limit kredit
- Credit scoring algorithm
- Payment rail khusus kredit
