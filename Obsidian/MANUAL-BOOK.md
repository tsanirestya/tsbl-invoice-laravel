# 📘 MANUAL BOOK — TSBL Invoice Management System
**Versi:** 1.0  
**Tanggal:** 2026-05-09  
**Bahasa:** Indonesia  
**Untuk:** Semua Pengguna Sistem (Staff, Finance, Admin)

---

> 💡 **Panduan ini dibuat untuk semua orang — termasuk yang baru pertama kali menggunakan sistem ini.**
> Ikuti langkah demi langkah. Jangan lewatkan satupun.

---

## 📋 DAFTAR ISI

1. [Cara Login ke Sistem](#1-cara-login-ke-sistem)
2. [Mengenal Tampilan Dashboard](#2-mengenal-tampilan-dashboard)
3. [Membuat Invoice Baru](#3-membuat-invoice-baru)
4. [Melihat & Mengelola Invoice](#4-melihat--mengelola-invoice)
5. [Antrian Invoice (Pending Invoices)](#5-antrian-invoice)
6. [Invoice Deposit](#6-invoice-deposit)
7. [Mengelola Partner](#7-mengelola-partner)
8. [Deposit Partner (Top-up)](#8-deposit-partner-top-up)
9. [Import Transaksi dari File Excel/CSV](#9-import-transaksi)
10. [Review Hasil Import (Anomaly Review)](#10-review-hasil-import)
11. [Mencatat Pembayaran Invoice](#11-mencatat-pembayaran-invoice)
12. [Memo Tagihan (Payment Memo)](#12-memo-tagihan)
13. [Pembayaran Kredit (Credit Payment)](#13-pembayaran-kredit)
14. [Laporan & Export Data](#14-laporan--export-data)
15. [Mengelola Produk](#15-mengelola-produk)
16. [Manajemen Pengguna (Admin)](#16-manajemen-pengguna-admin)
17. [Credit Classes (Admin)](#17-credit-classes-admin)
18. [Pengaturan Sistem (Admin)](#18-pengaturan-sistem-admin)
19. [Lupa Password / Reset Password](#19-lupa-password--reset-password)
20. [Jika Mengalami Masalah](#20-jika-mengalami-masalah)
21. [Alur Billing Baru (Redesign)](#21-alur-billing-baru-redesign)
22. [Manajemen Reservasi & Proforma](#22-manajemen-reservasi--proforma)
23. [Rekonsiliasi DSI & Invoice Final](#23-rekonsiliasi-dsi--invoice-final)
24. [Alokasi Pembayaran & Saldo Kredit](#24-alokasi-pembayaran--saldo-kredit)


---

## 1. CARA LOGIN KE SISTEM

### 🎯 Tujuan
Masuk ke sistem agar bisa menggunakan semua fitur.

---

### Langkah 1.1 — Buka Sistem di Browser

**Gambaran tampilan:** Halaman kosong browser (Chrome, Firefox, Edge, dll.)

1. Buka browser di komputer atau HP kamu
2. Ketik alamat sistem di bar atas browser:
   ```
   https://invoice.transentertainment.id
   ```
3. Tekan **Enter**

➜ Sistem akan otomatis membawa kamu ke halaman **Login**

---

### Langkah 1.2 — Isi Form Login

**Gambaran tampilan:**
```
┌─────────────────────────────────┐
│         TSBL Invoice            │
│      Management System          │
│                                 │
│  Email / Username               │
│  [________________________]     │
│                                 │
│  Password                       │
│  [________________________]     │
│                                 │
│       [  MASUK  ]               │
│                                 │
│  Lupa password? Klik di sini    │
└─────────────────────────────────┘
```

1. Klik kolom **Email / Username** → ketik email atau username kamu
2. Klik kolom **Password** → ketik password kamu
3. Klik tombol **[MASUK]** yang ada di tengah-bawah form

⚠️ **Perhatian:**
- Password bersifat *case-sensitive* (huruf besar/kecil berpengaruh)
- Jika salah 5 kali berturut-turut, sistem akan mengunci login selama 1 menit

✅ **Hasil jika berhasil:** Kamu akan diarahkan ke halaman **Dashboard**

---

### Langkah 1.3 — Verifikasi Sudah Login

Cek bagian **pojok kanan atas** — kamu akan melihat nama kamu dan role (jabatan) yang tertera.

Contoh:
```
[ Budi Santoso ]
  Finance
```

---

## 2. MENGENAL TAMPILAN DASHBOARD

### 🎯 Tujuan
Memahami semua informasi yang tampil di halaman utama.

---

### Bagian 2.1 — Struktur Tampilan Utama

```
┌──────────────────────────────────────────────────────────┐
│  [TSBL Invoice]     Dashboard         [Nama User] [Role] │
├───────────┬──────────────────────────────────────────────┤
│           │  Halo, [Nama Kamu] 👋        [Buat Invoice]  │
│  SIDEBAR  │  ─────────────────────────────────────────── │
│  ──────── │  [Total Invoice] [Belum Bayar] [Partial]     │
│ Dashboard │  [Lunas] [Jatuh Tempo] [Partner]             │
│ Invoice   │  ─────────────────────────────────────────── │
│ Antrian   │  [Pendapatan Lunas] [Outstanding] [Deposit]  │
│ Invoice   │  ─────────────────────────────────────────── │
│ Deposit   │  [Saldo Deposit Partner] | [Status Import]   │
│ Partner   │  ─────────────────────────────────────────── │
│ Import    │  [Antrian Invoice] | [Invoice Jatuh Tempo]   │
│ ──────── │                                               │
│ KEUANGAN │                                               │
│ Pembayaran│                                               │
│ Memo      │                                               │
│ Kredit    │                                               │
│ Laporan   │                                               │
│ ──────── │                                               │
│ SETTINGS │                                               │
│ Produk    │                                               │
│ Pengguna  │                                               │
│ Settings  │                                               │
└───────────┴──────────────────────────────────────────────┘
```

---

### Bagian 2.2 — Kartu KPI (Ringkasan Angka)

Di bagian atas dashboard, ada **6 kartu berwarna** yang menampilkan:

| Kartu | Warna | Artinya |
|-------|-------|---------|
| **Total Invoice** | Biru | Jumlah semua invoice di sistem |
| **Belum Bayar** | Abu-abu | Invoice yang belum dibayar sama sekali |
| **Partial** | Kuning | Invoice yang baru dibayar sebagian |
| **Lunas** | Hijau | Invoice yang sudah lunas |
| **Jatuh Tempo** | Merah | Invoice yang sudah melewati batas waktu bayar |
| **Partner** | Cyan | Jumlah partner yang terdaftar |

---

### Bagian 2.3 — Banner Keuangan (3 Kotak)

Di bawah kartu KPI, ada **3 kotak ringkasan keuangan**:
- 🟢 **Pendapatan (Lunas)** — Total uang yang sudah masuk
- 🟡 **Outstanding Piutang** — Total tagihan yang belum dibayar
- 🔵 **Sisa Deposit** — Total saldo deposit semua partner

---

### Bagian 2.4 — Alert / Peringatan Otomatis

⚠️ Sistem akan otomatis menampilkan **kotak peringatan kuning** jika ada partner dengan saldo deposit rendah (di bawah Rp 5.000.000).

⚠️ Sistem juga akan tampilkan **kotak peringatan merah** jika ada hasil import dengan anomaly rate tinggi.

💡 Kamu bisa menutup peringatan ini dengan klik tanda **×** di pojok kanan atas kotak peringatan.

---

### Bagian 2.5 — Sidebar (Menu Kiri)

**Di desktop:** Sidebar selalu terlihat di sebelah kiri layar.

**Di HP/Mobile:** Sidebar tersembunyi. Untuk membukanya:
- Ketuk ikon **☰ (tiga garis)** di pojok kiri atas layar

Menu sidebar terbagi menjadi 3 grup:
1. **Menu Utama** — Dashboard, Invoice, Antrian, Invoice Deposit, Partner, Import
2. **Keuangan** — Pembayaran, Memo Tagihan, Pembayaran Credit, Laporan
3. **Pengaturan** — Produk, Pengguna (Admin), Settings (Admin)

---

## 3. MEMBUAT INVOICE BARU

### 🎯 Tujuan
Membuat tagihan baru untuk dikirimkan ke partner.

---

### Langkah 3.1 — Masuk ke Halaman Buat Invoice

Ada **2 cara** membuka halaman buat invoice:

**Cara A — dari Dashboard:**
➜ Klik tombol **[Buat Invoice]** di pojok kanan atas halaman Dashboard (tombol berwarna biru)

**Cara B — dari Sidebar:**
➜ Klik menu **Invoice** di sidebar kiri  
➜ Klik tombol **[+ Invoice Baru]** di pojok kanan atas halaman daftar invoice

---

### Langkah 3.2 — Isi Header Invoice

**Gambaran form:**
```
┌─────────────────────────────────────────────────┐
│            BUAT INVOICE BARU                    │
│                                                 │
│  Nomor Invoice    [AUTO-GENERATE]               │
│  Partner          [Pilih Partner ▼]             │
│  Tanggal Invoice  [DD/MM/YYYY]                  │
│  Tanggal Jatuh Tempo [DD/MM/YYYY]               │
│  No. Transaksi    [________________________]    │
└─────────────────────────────────────────────────┘
```

1. **Nomor Invoice** — Diisi otomatis oleh sistem, tidak perlu diubah
2. **Partner** — Klik dropdown, ketik nama partner, pilih dari daftar
3. **Tanggal Invoice** — Klik kolom, pilih tanggal di kalender yang muncul
4. **Tanggal Jatuh Tempo** — Klik kolom, pilih tanggal batas pembayaran
5. **No. Transaksi** — Isi nomor transaksi referensi (jika ada)

⚠️ **Kolom bertanda \* wajib diisi.**

---

### Langkah 3.3 — Tambah Item/Produk ke Invoice

Di bagian bawah form, ada tabel untuk memasukkan item:

```
┌──────────────────────────────────────────────────────────┐
│  ITEM INVOICE                                            │
│ ────────────────────────────────────────────────────────│
│  [+ Tambah Item]                                         │
│                                                          │
│  No │ Produk        │ Qty │ Harga Satuan │ Total        │
│  1  │[Pilih Produk] │ [1] │  [100.000]   │  100.000     │
└──────────────────────────────────────────────────────────┘
```

1. Klik tombol **[+ Tambah Item]** — baris baru akan muncul
2. Di kolom **Produk** — ketik nama produk, pilih dari dropdown
3. Di kolom **Qty** — ketik jumlah unit
4. Di kolom **Harga Satuan** — ketik harga per unit (ketik angka saja, titik ribuan otomatis)
5. **Total** akan dihitung otomatis

➜ Ulangi langkah ini untuk setiap item yang ingin ditambahkan

💡 Untuk menghapus item, klik ikon **🗑️ (tempat sampah)** di baris item tersebut.

---

### Langkah 3.4 — Cek Ringkasan Total

Di bagian bawah kanan form:
```
                    Subtotal    : Rp 1.000.000
                    Diskon      : Rp 0
                    PPN (11%)   : Rp 110.000
                    ─────────────────────────
                    GRAND TOTAL : Rp 1.110.000
```

Pastikan angka total sudah benar sebelum menyimpan.

---

### Langkah 3.5 — Simpan Invoice

1. Scroll ke bawah halaman
2. Klik tombol **[Simpan Invoice]** (tombol biru, posisi di bawah form)

✅ **Hasil:** Invoice tersimpan dengan status **DRAFT** dan kamu akan diarahkan ke halaman detail invoice.

⚠️ Invoice dengan status DRAFT **belum bisa** dikirim ke partner. Lanjut ke langkah 3.6 untuk finalisasi.

---

### Langkah 3.6 — Finalisasi Invoice (Ubah ke Status FINAL)

Setelah invoice tersimpan:

1. Di halaman detail invoice, cari tombol **[Finalisasi Invoice]**
2. Klik tombol tersebut
3. Muncul konfirmasi — klik **[Ya, Finalisasi]**

✅ **Hasil:** Status invoice berubah menjadi **UNPAID** (Belum Bayar) dan nomor invoice sudah terkunci.

⚠️ **Perhatian:** Setelah finalisasi, **nomor invoice tidak bisa diubah lagi**. Pastikan semua data sudah benar.

💡 Fitur finalisasi hanya tersedia untuk user dengan role **FINANCE** atau **ADMIN**.

---

### Langkah 3.7 — Download PDF Invoice

Di halaman detail invoice:
1. Cari tombol **[Download PDF]** atau ikon PDF
2. Klik tombol tersebut
3. File PDF akan otomatis terunduh ke komputer/HP kamu

---

## 4. MELIHAT & MENGELOLA INVOICE

### 🎯 Tujuan
Mencari, melihat, mengedit, atau menduplikasi invoice yang sudah ada.

---

### Langkah 4.1 — Buka Daftar Invoice

➜ Klik menu **Invoice** di sidebar kiri

**Gambaran tampilan daftar:**
```
┌─────────────────────────────────────────────────────────┐
│  DAFTAR INVOICE                    [+ Invoice Baru]     │
│  Filter: [Semua Status ▼] [Partner ▼] [Tanggal ▼]      │
│ ───────────────────────────────────────────────────────│
│  No Invoice  │ Partner    │ Total    │ Status   │ Aksi  │
│  INV-001     │ PT ABC     │ 1jt      │ LUNAS    │ [Lihat]│
│  INV-002     │ CV XYZ     │ 500rb    │ BELUM    │ [Lihat]│
│  INV-003     │ PT DEF     │ 2jt      │ OVERDUE  │ [Lihat]│
└─────────────────────────────────────────────────────────┘
```

---

### Langkah 4.2 — Filter & Cari Invoice

Di bagian atas tabel ada tombol **Filter**:

1. **Filter Status** — Klik dropdown, pilih: Semua / Draft / Belum Bayar / Partial / Lunas / Overdue
2. **Filter Partner** — Ketik nama partner untuk menyaring
3. **Filter Tanggal** — Pilih rentang tanggal

Setelah memilih filter, daftar invoice akan otomatis diperbarui.

---

### Langkah 4.3 — Buka Detail Invoice

➜ Klik tombol **[Lihat]** atau klik langsung pada nomor invoice

Di halaman detail, kamu bisa:
- Melihat semua item invoice
- Melihat riwayat pembayaran
- Download PDF
- Menduplikasi invoice
- Mencatat pembayaran (jika FINANCE/ADMIN)

---

### Langkah 4.4 — Duplikasi Invoice

Berguna untuk membuat invoice baru yang mirip dengan invoice sebelumnya:

1. Buka detail invoice yang ingin diduplikasi
2. Klik tombol **[Duplikasi]**
3. Invoice baru akan terbuat dengan status DRAFT
4. Edit bagian yang perlu diubah (tanggal, jumlah, dll.)
5. Simpan

---

## 5. ANTRIAN INVOICE

### 🎯 Tujuan
Melihat dan memproses transaksi yang sudah diimport tapi belum dibuatkan invoicenya.

---

### Langkah 5.1 — Buka Halaman Antrian

➜ Klik menu **Antrian Invoice** di sidebar kiri

⚠️ Jika ada angka merah (badge) di menu ini, artinya ada transaksi yang menunggu dibuatkan invoice.

---

### Langkah 5.2 — Buat Invoice dari Antrian

**Gambaran tampilan:**
```
┌──────────────────────────────────────────────────────┐
│  ANTRIAN INVOICE BELUM DIBUAT                        │
│ ────────────────────────────────────────────────────│
│  TRX-001  │ PT ABC  │ 05/05/2026  │ Rp 500.000  [Buat]│
│  TRX-002  │ CV XYZ  │ 05/05/2026  │ Rp 200.000  [Buat]│
└──────────────────────────────────────────────────────┘
```

1. Cari transaksi yang ingin dibuatkan invoice
2. Klik tombol **[Buat]** di baris transaksi tersebut
3. Form buat invoice akan terbuka dengan data transaksi sudah terisi otomatis
4. Cek dan sesuaikan jika ada yang perlu diubah
5. Klik **[Simpan Invoice]**

✅ **Hasil:** Transaksi hilang dari antrian dan invoice baru tersimpan.

---

## 6. INVOICE DEPOSIT

### 🎯 Tujuan
Membuat tagihan kepada partner untuk mengisi saldo deposit mereka.

---

### Langkah 6.1 — Buka Halaman Invoice Deposit

➜ Klik menu **Invoice Deposit** di sidebar kiri

---

### Langkah 6.2 — Buat Invoice Deposit Baru

1. Klik tombol **[+ Invoice Deposit]** di kanan atas
2. Isi form:
   - **Partner** — Pilih partner yang akan ditagih depositnya
   - **Jumlah** — Masukkan nominal deposit yang diminta
   - **Tanggal** dan **Jatuh Tempo**
3. Klik **[Simpan]**

---

### Langkah 6.3 — Finalisasi & Tandai Lunas

Setelah invoice deposit dibuat:
- Klik **[Finalisasi]** untuk mengunci nomor invoice
- Setelah partner membayar, klik **[Tandai Lunas]** → saldo deposit partner akan otomatis bertambah

---

## 7. MENGELOLA PARTNER

### 🎯 Tujuan
Menambah, melihat, dan mengedit data partner/klien.

---

### Langkah 7.1 — Buka Halaman Partner

➜ Klik menu **Partner** di sidebar kiri

---

### Langkah 7.2 — Tambah Partner Baru

1. Klik tombol **[+ Partner Baru]** di kanan atas
2. Isi form data partner:

```
┌─────────────────────────────────────┐
│  TAMBAH PARTNER BARU                │
│                                     │
│  Nama Partner  * [_______________]  │
│  Tipe          * [Perusahaan ▼]    │
│  Email          [_______________]   │
│  Telepon        [_______________]   │
│  Alamat         [_______________]   │
│  NPWP           [_______________]   │
│  Limit Kredit   [_______________]   │
│  Kelas Kredit  [Pilih Class ▼]     │
│                                     │
│  [ Simpan Partner ]                 │
└─────────────────────────────────────┘
```

3. Klik **[Simpan Partner]**

✅ **Hasil:** Partner baru terdaftar di sistem.

---

### Langkah 7.3 — Lihat Detail Partner

1. Klik nama partner di daftar
2. Di halaman detail, kamu bisa melihat:
   - Informasi lengkap partner
   - Semua invoice partner tersebut
   - Saldo deposit partner
   - Riwayat pembayaran
   - Status kredit

---

### Langkah 7.4 — Lihat Performa Partner

➜ Di halaman Partner, klik tombol **[Performa]**

Halaman ini menampilkan statistik masing-masing partner: total transaksi, tingkat pembayaran tepat waktu, dll.

---

## 8. DEPOSIT PARTNER (TOP-UP)

### 🎯 Tujuan
Menambah saldo deposit partner setelah mereka membayar invoice deposit.

---

### Langkah 8.1 — Buka Halaman Deposit Partner

**Cara A — dari Dashboard:**
➜ Klik tombol **[Top-up]** di sebelah nama partner di widget "Saldo Deposit Partner"

**Cara B — dari Detail Partner:**
➜ Buka halaman detail partner → klik tab/menu **Deposit** → klik **[Top-up Deposit]**

---

### Langkah 8.2 — Isi Form Top-up

```
┌─────────────────────────────────┐
│  TOP-UP DEPOSIT                 │
│  Partner: PT ABC                │
│  Saldo Saat Ini: Rp 2.000.000   │
│                                 │
│  Nominal Top-up  [___________]  │
│  Keterangan      [___________]  │
│  Tanggal         [DD/MM/YYYY]   │
│                                 │
│  [ Simpan Top-up ]              │
└─────────────────────────────────┘
```

1. Isi **Nominal Top-up** — ketik jumlah deposit yang masuk
2. Isi **Keterangan** — misal: "Transfer BCA 05/05/2026"
3. Pastikan **Tanggal** sudah benar
4. Klik **[Simpan Top-up]**

✅ **Hasil:** Saldo deposit partner langsung bertambah.

💡 Fitur ini hanya tersedia untuk role **FINANCE** dan **ADMIN**.

---

## 9. IMPORT TRANSAKSI

### 🎯 Tujuan
Memasukkan data transaksi dari file Excel/CSV ke dalam sistem secara massal.

---

### Langkah 9.1 — Siapkan File

Pastikan file Excel/CSV kamu sudah dalam format yang benar. Kolom yang dibutuhkan:
- Nomor Transaksi
- Tanggal
- Nama Partner
- Nama Produk / Tiket
- Jumlah / Qty
- Harga

💡 Minta format template ke admin sistem jika belum punya.

---

### Langkah 9.2 — Buka Halaman Import

➜ Klik menu **Import Transaksi** di sidebar kiri  
➜ Klik tombol **[+ Upload Baru]** di kanan atas

---

### Langkah 9.3 — Upload File

```
┌──────────────────────────────────────┐
│  UPLOAD FILE TRANSAKSI               │
│                                      │
│  ┌────────────────────────────────┐  │
│  │  Klik di sini atau seret file  │  │
│  │  ke area ini                   │  │
│  │    [PILIH FILE]                │  │
│  └────────────────────────────────┘  │
│                                      │
│  Format: .xlsx, .csv                 │
│  Maks: 10MB                          │
│                                      │
│  [ Upload & Proses ]                 │
└──────────────────────────────────────┘
```

1. Klik **[PILIH FILE]** → cari dan pilih file dari komputer kamu
2. Atau seret file langsung ke area upload (drag & drop)
3. Setelah file terpilih, klik **[Upload & Proses]**

⚠️ Tunggu hingga proses upload selesai. Jangan tutup browser atau refresh halaman.

✅ **Hasil:** Sistem akan memproses file dan membawa kamu ke halaman **Review Import**.

---

## 10. REVIEW HASIL IMPORT

### 🎯 Tujuan
Memeriksa hasil import dan menangani data yang bermasalah (anomaly).

---

### Langkah 10.1 — Pahami Status Baris Data

Setiap baris data import memiliki status:

| Status | Warna | Artinya |
|--------|-------|---------|
| **Valid** | Hijau | Data normal, siap diproses |
| **Anomaly** | Merah | Data bermasalah, perlu dicheck |
| **Rejected** | Abu-abu | Data ditolak |

---

### Langkah 10.2 — Tangani Baris Anomaly

Untuk setiap baris dengan status **Anomaly**:

1. Klik baris tersebut untuk melihat detail masalahnya
2. Pilih salah satu aksi:
   - **[Override]** — Paksakan data diterima (butuh alasan)
   - **[Reject]** — Tolak baris ini
   - **[Reassign Produk]** — Ganti produk ke yang benar
   - **[Sesuaikan Harga]** — Ubah harga ke harga yang benar

---

### Langkah 10.3 — Approve Baris yang Valid

1. Centang baris-baris yang ingin di-approve (atau centang semua)
2. Klik tombol **[Approve]** di bagian atas/bawah tabel

---

### Langkah 10.4 — Finalisasi Import

Setelah semua baris sudah ditangani:

1. Klik tombol **[Finalisasi Import]**
2. Konfirmasi di popup yang muncul
3. Data yang di-approve akan masuk ke **Antrian Invoice**

✅ **Hasil:** Data import selesai diproses dan siap dibuatkan invoice.

💡 Kamu juga bisa **export laporan anomaly** ke Excel dengan klik tombol **[Export Anomaly]**.

---

## 11. MENCATAT PEMBAYARAN INVOICE

### 🎯 Tujuan
Merekam pembayaran yang diterima dari partner untuk invoice tertentu.

---

### Langkah 11.1 — Buka Invoice yang Ingin Dicatat Pembayarannya

➜ Klik menu **Invoice** → cari invoice yang ingin dicatat pembayarannya  
➜ Klik **[Lihat]** untuk membuka detail invoice

---

### Langkah 11.2 — Tambah Pembayaran

Di bagian bawah halaman detail invoice, ada seksi **Riwayat Pembayaran**:

1. Klik tombol **[+ Catat Pembayaran]**
2. Isi form:

```
┌─────────────────────────────────────┐
│  CATAT PEMBAYARAN                   │
│                                     │
│  Jumlah Pembayaran  [____________]  │
│  Tanggal Bayar      [DD/MM/YYYY]    │
│  Metode             [Transfer ▼]    │
│  Keterangan         [____________]  │
│                                     │
│  [ Simpan Pembayaran ]              │
└─────────────────────────────────────┘
```

3. Isi **Jumlah Pembayaran** — nominal yang diterima
4. Isi **Tanggal Bayar** — kapan pembayaran masuk
5. Pilih **Metode** pembayaran (Transfer, Tunai, dll.)
6. Klik **[Simpan Pembayaran]**

✅ **Hasil:** Pembayaran tercatat. Status invoice akan otomatis berubah:
- Bayar sebagian → Status jadi **PARTIAL**
- Bayar lunas → Status jadi **LUNAS**

⚠️ Fitur ini hanya tersedia untuk role **FINANCE** dan **ADMIN**.

---

### Langkah 11.3 — Lihat Semua Pembayaran

➜ Klik menu **Pembayaran** di sidebar kiri (bagian Keuangan)

Di sini kamu bisa melihat semua riwayat pembayaran dari semua partner dalam satu tampilan.

---

## 12. MEMO TAGIHAN

### 🎯 Tujuan
Membuat surat/memo resmi untuk menagih pembayaran outstanding dari partner.

---

### Langkah 12.1 — Buka Halaman Memo Tagihan

➜ Klik menu **Memo Tagihan** di sidebar kiri (bagian Keuangan)

---

### Langkah 12.2 — Buat Memo Tagihan Baru

1. Klik tombol **[+ Buat Memo]**
2. Isi form:
   - **Partner** — Pilih partner yang akan ditagih
   - Sistem akan otomatis menampilkan **daftar invoice outstanding** partner tersebut
   - **Centang invoice** yang ingin dimasukkan ke memo ini
   - Isi **catatan/keterangan** jika perlu
3. Klik **[Buat Memo]**

✅ **Hasil:** Memo tagihan tersimpan dan bisa di-download sebagai PDF.

---

### Langkah 12.3 — Download Memo sebagai PDF

Di halaman detail memo:
➜ Klik tombol **[Download PDF]**

---

## 13. PEMBAYARAN KREDIT

### 🎯 Tujuan
Mencatat pembayaran kredit dari partner secara batch (untuk melunasi beberapa invoice sekaligus).

---

### Langkah 13.1 — Buka Halaman Pembayaran Kredit

➜ Klik menu **Pembayaran Credit** di sidebar kiri (bagian Keuangan)

---

### Langkah 13.2 — Buat Batch Pembayaran Kredit

1. Klik tombol **[+ Buat Credit Payment]**
2. Pilih **Partner**
3. Sistem akan tampilkan semua invoice outstanding partner tersebut
4. **Centang invoice** yang ingin dilunasi sekaligus
5. Isi **Tanggal Bayar** dan **Keterangan**
6. Klik **[Simpan]**

✅ **Hasil:** Semua invoice yang dicentang akan terupdate statusnya.

---

## 14. LAPORAN & EXPORT DATA

### 🎯 Tujuan
Melihat dan mendownload laporan keuangan dan transaksi.

---

### Langkah 14.1 — Buka Halaman Laporan

➜ Klik menu **Laporan** di sidebar kiri (bagian Keuangan)

---

### Langkah 14.2 — Filter Laporan

```
┌──────────────────────────────────────────────────┐
│  LAPORAN INVOICE                                 │
│                                                  │
│  Periode: [Dari: DD/MM/YYYY] [Ke: DD/MM/YYYY]   │
│  Partner: [Semua Partner ▼]                      │
│  Status:  [Semua Status ▼]                       │
│                                                  │
│  [Tampilkan]                                     │
└──────────────────────────────────────────────────┘
```

1. Pilih **rentang tanggal** (dari - ke)
2. Pilih **partner** tertentu atau biarkan "Semua Partner"
3. Pilih **status** invoice
4. Klik **[Tampilkan]**

---

### Langkah 14.3 — Export Laporan

Setelah laporan tampil:

| Tombol | Fungsi |
|--------|--------|
| **[Export CSV]** | Unduh laporan invoice format Excel/CSV |
| **[Export PDF]** | Unduh laporan invoice format PDF |
| **[Export Credit CSV]** | Unduh laporan kredit format Excel/CSV |
| **[Export Credit PDF]** | Unduh laporan kredit format PDF |

Klik tombol yang sesuai → file akan otomatis terunduh.

---

## 15. MENGELOLA PRODUK

### 🎯 Tujuan
Menambah, mengedit, dan mengelola daftar produk/layanan yang dijual.

---

### Langkah 15.1 — Buka Halaman Produk

➜ Klik menu **Produk** di sidebar kiri (bagian Pengaturan)

---

### Langkah 15.2 — Tambah Produk Baru

1. Klik tombol **[+ Produk Baru]**
2. Isi form:
   - **Nama Produk** — Nama resmi produk
   - **Harga Default** — Harga standar produk
   - **Satuan** — misal: per tiket, per orang, per hari
   - **Keterangan** (opsional)
3. Klik **[Simpan]**

---

### Langkah 15.3 — Kelola Alias Produk

Alias berguna agar sistem bisa mengenali nama produk yang berbeda-beda dari file import.

Contoh: Produk "Paket Wisata Pantai" bisa juga muncul sebagai "WISATA-PANTAI" atau "PANTAI TRIP" di file import.

1. Di daftar produk, klik tombol **[Alias]** di sebelah produk
2. Di halaman alias, klik **[+ Tambah Alias]**
3. Ketik nama alias (nama alternatif)
4. Klik **[Simpan]**

---

## 16. MANAJEMEN PENGGUNA (ADMIN)

> ⚠️ **Bagian ini hanya untuk Admin**

### 🎯 Tujuan
Menambah, mengedit, dan mengelola akun pengguna sistem.

---

### Langkah 16.1 — Buka Halaman Pengguna

➜ Klik menu **Pengguna** di sidebar kiri (bagian Pengaturan)

---

### Langkah 16.2 — Tambah Pengguna Baru

1. Klik tombol **[+ Pengguna Baru]**
2. Isi form:
   - **Nama Lengkap** — Nama pengguna
   - **Email** — Email untuk login
   - **Username** — Username alternatif untuk login
   - **Password** — Password awal
   - **Role** — Pilih jabatan:
     - **ADMIN** — Akses penuh ke semua fitur
     - **FINANCE** — Bisa proses pembayaran, finalisasi invoice
     - **STAFF** — Bisa buat invoice dan import, tidak bisa proses pembayaran
3. Klik **[Simpan Pengguna]**

---

### Langkah 16.3 — Edit atau Nonaktifkan Pengguna

1. Di daftar pengguna, klik tombol **[Edit]** di sebelah nama pengguna
2. Ubah data yang perlu diubah
3. Untuk menonaktifkan pengguna, ubah status menjadi **Tidak Aktif**
4. Klik **[Simpan]**

---

## 17. CREDIT CLASSES (ADMIN)

> ⚠️ **Bagian ini hanya untuk Admin**

### 🎯 Tujuan
Mengelola kelas kredit yang bisa diassign ke partner untuk mengatur limit kredit.

---

### Langkah 17.1 — Buka Halaman Credit Classes

➜ Klik menu **Credit Classes** di sidebar kiri

---

### Langkah 17.2 — Buat Credit Class Baru

1. Klik **[+ Tambah Credit Class]**
2. Isi:
   - **Nama Class** — misal: "Premium", "Standard", "Basic"
   - **Limit Kredit Default** — batas kredit untuk class ini
   - **Warna** — warna badge (untuk identifikasi visual)
3. Klik **[Simpan]**

---

## 18. PENGATURAN SISTEM (ADMIN)

> ⚠️ **Bagian ini hanya untuk Admin**

### 🎯 Tujuan
Mengubah konfigurasi tampilan dan sistem.

---

### Langkah 18.1 — Buka Halaman Pengaturan

➜ Klik menu **Pengaturan** di sidebar kiri (bagian Pengaturan)

---

### Langkah 18.2 — Ubah Pengaturan

Di halaman pengaturan, kamu bisa mengubah:
- **Logo Navbar** — Upload logo yang tampil di sidebar atas
- **Favicon** — Ikon kecil di tab browser
- **Nama Perusahaan** — Nama yang tampil di dokumen PDF
- **Pengaturan lainnya**

Setelah mengubah, klik **[Simpan Pengaturan]**.

---

## 19. LUPA PASSWORD / RESET PASSWORD

### Jika Kamu Lupa Password

### Langkah 19.1 — Ajukan Reset Password

1. Di halaman login, klik link **"Lupa password? Klik di sini"**
2. Kamu akan dibawa ke halaman **Permintaan Reset Password**:

```
┌──────────────────────────────────────────────┐
│         LUPA PASSWORD                        │
│                                              │
│  Masukkan username atau email kamu:          │
│  [________________________________]          │
│                                              │
│  Alasan / Keterangan:                        │
│  [________________________________]          │
│                                              │
│  [ Kirim Permintaan ]                        │
└──────────────────────────────────────────────┘
```

3. Isi username/email dan alasan reset
4. Klik **[Kirim Permintaan]**

✅ **Hasil:** Permintaan kamu tercatat dan Admin akan mendapat notifikasi.

⚠️ **Sistem ini tidak menggunakan email otomatis.** Admin akan memproses permintaanmu secara manual dan memberitahumu password baru.

---

### Langkah 19.2 — Proses oleh Admin

**Untuk Admin yang memproses permintaan reset:**

1. Di sidebar, cek menu **Reset Password** — jika ada badge angka, ada permintaan masuk
2. Klik menu **Reset Password**
3. Lihat daftar permintaan yang masuk
4. Klik **[Resolve]** di sebelah nama pengguna
5. Masukkan password baru untuk pengguna tersebut
6. Klik **[Simpan]**

---

### Langkah 19.3 — Ganti Password (Setelah Login Pertama)

Jika kamu baru login untuk pertama kali atau sistem memintamu ganti password:

1. Kamu akan otomatis diarahkan ke halaman **Ganti Password**
2. Isi:
   - **Password Lama** — password yang diberikan Admin
   - **Password Baru** — password baru pilihanmu
   - **Konfirmasi Password Baru** — ketik ulang password baru
3. Klik **[Simpan Password Baru]**

✅ **Hasil:** Password berhasil diubah dan kamu bisa login dengan password baru.

---

## 20. JIKA MENGALAMI MASALAH

### ⚠️ Masalah Umum & Solusinya

---

**❌ Tidak bisa login — muncul "Username/Password salah"**

Penyebab: Email, username, atau password yang diketik salah.

Solusi:
1. Pastikan Caps Lock di keyboard **tidak aktif**
2. Coba ketik ulang dengan hati-hati
3. Jika masih gagal, gunakan fitur **Lupa Password** (lihat Bagian 19)

---

**❌ Login diblokir sementara**

Penyebab: Gagal login 5 kali berturut-turut — sistem mengunci selama 1 menit.

Solusi: Tunggu 1 menit, lalu coba lagi.

---

**❌ Tombol Finalisasi tidak muncul**

Penyebab: Akunmu tidak memiliki role FINANCE atau ADMIN.

Solusi: Minta Admin untuk menaikkan role akunmu, atau minta Finance/Admin untuk finalisasi invoice.

---

**❌ File import gagal diproses / error**

Penyebab: Format file tidak sesuai, ada kolom yang kosong, atau nama partner/produk tidak dikenali sistem.

Solusi:
1. Cek format file — pastikan menggunakan template yang benar
2. Pastikan nama partner di file **persis sama** dengan nama di sistem
3. Cek kolom harga — harus angka saja, tidak ada huruf atau simbol mata uang

---

**❌ Anomaly rate sangat tinggi setelah import**

Penyebab: Banyak data yang tidak cocok dengan master data di sistem.

Solusi:
1. Buka halaman **Review Import** → lihat detail setiap anomaly
2. Gunakan fitur **Reassign Produk** untuk mencocokkan produk
3. Tambahkan **Alias Produk** agar sistem bisa mengenali nama alternatif di masa depan

---

**❌ Saldo deposit partner tidak bertambah setelah top-up**

Penyebab: Kemungkinan top-up belum disimpan atau terjadi error.

Solusi:
1. Buka halaman detail partner → cek tab Deposit
2. Pastikan ada riwayat top-up terbaru
3. Jika tidak ada, coba lakukan top-up ulang

---

**❌ PDF tidak bisa di-download**

Penyebab: Browser memblokir download atau ada error di sistem.

Solusi:
1. Cek apakah browser memblokir pop-up — izinkan pop-up untuk situs ini
2. Coba di browser lain (Chrome, Firefox, Edge)
3. Hubungi Admin jika masalah berlanjut

---

**❌ Halaman error / blank putih**

Penyebab: Masalah koneksi atau server.

Solusi:
1. Refresh halaman (tekan **F5** atau **Ctrl+R**)
2. Cek koneksi internet/jaringan lokal
3. Jika menggunakan XAMPP — pastikan XAMPP sudah running (Apache & MySQL harus hijau)
4. Hubungi IT/Admin jika masalah berlanjut

---

## 📞 KONTAK BANTUAN

Jika mengalami masalah yang tidak tercantum di panduan ini, hubungi:

- **Admin Sistem** — untuk masalah akun dan hak akses
- **IT Support** — untuk masalah teknis server dan koneksi

---

## 📝 RINGKASAN HAK AKSES PER ROLE

| Fitur | STAFF | FINANCE | ADMIN |
|-------|-------|---------|-------|
| Lihat Dashboard | ✅ | ✅ | ✅ |
| Buat Invoice | ✅ | ✅ | ✅ |
| Finalisasi Invoice | ❌ | ✅ | ✅ |
| Catat Pembayaran | ❌ | ✅ | ✅ |
| Top-up Deposit | ❌ | ✅ | ✅ |
| Import Transaksi | ✅ | ✅ | ✅ |
| Approve Import | ❌ | ✅ | ✅ |
| Buat Memo Tagihan | ✅ | ✅ | ✅ |
| Kelola Pengguna | ❌ | ❌ | ✅ |
| Credit Classes | ❌ | ❌ | ✅ |
| Pengaturan Sistem | ❌ | ❌ | ✅ |
| Reset Password User | ❌ | ❌ | ✅ |

---

*Manual Book ini terakhir diperbarui: 2026-05-09*  
*Sistem: TSBL Invoice Management System v1.0*

---

## 21. ALUR BILLING BARU (REDESIGN)

### ?? Tujuan
Memahami sistem billing yang lebih akurat dan terintegrasi antara reservasi, kunjungan (DSI), dan invoice final.

Sistem baru ini menggunakan alur:
**Reservasi** ? **Invoice Proforma** ? **Kunjungan (DSI)** ? **Rekonsiliasi** ? **Invoice Final**

---

## 22. MANAJEMEN RESERVASI & PROFORMA

### Langkah 22.1 � Mencatat Reservasi
1. Klik menu **Reservations** di sidebar.
2. Klik **[+ Create Reservation]**.
3. Isi nama tamu, partner, tanggal check-in, dan estimasi pax/harga.
4. Klik **[Save]**. Status awal adalah **PENDING**.

### Langkah 22.2 � Konfirmasi & Proforma
1. Buka detail reservasi, klik **[Confirm]** jika sudah pasti.
2. Klik **[Issue Proforma]** untuk menagih pembayaran di muka (Prepaid).
3. Masukkan item yang akan ditagih.
4. Kirim invoice Proforma ke partner.

---

## 23. REKONSILIASI DSI & INVOICE FINAL

### Langkah 23.1 � Import Transaksi DSI
Setiap hari, import data dari sistem operasional (DSI) melalui menu **DSI Import**. Sistem akan otomatis mencocokkan transaksi dengan reservasi yang ada.

### Langkah 23.2 � Melakukan Rekonsiliasi
1. Klik menu **Reconciliations**.
2. Pilih baris yang berstatus **PENDING_REVIEW**.
3. Bandingkan data Proforma (rencana) dengan data DSI (kenyataan).
4. Jika sudah sesuai, klik **[Approve]**.

? **Hasil:** Sistem akan otomatis membuat **Invoice FINAL**. Jika ada pembayaran di Proforma, saldo akan dipindahkan otomatis ke Invoice Final.

---

## 24. ALOKASI PEMBAYARAN & SALDO KREDIT

### Langkah 24.1 � Verifikasi Pembayaran
1. Klik menu **Billing Payments**.
2. Klik **[Verify]** pada pembayaran yang masuk (setelah cek bank).
3. Status pembayaran berubah menjadi **VERIFIED** dan terkunci.

### Langkah 24.2 � Alokasi ke Invoice
1. Buka detail pembayaran yang sudah Verified.
2. Klik **[Allocate]**, pilih invoice mana yang ingin dilunasi dengan uang ini.
3. Satu uang bisa melunasi banyak invoice (Batch Pay).

### Langkah 24.3 � Manajemen Saldo Kredit
Jika partner membayar lebih (Overpayment):
1. Saldo sisa akan masuk ke **Credit Balance** partner.
2. Saat membuat invoice berikutnya, gunakan tombol **[Apply Credit]** untuk memotong tagihan menggunakan saldo tersebut.
