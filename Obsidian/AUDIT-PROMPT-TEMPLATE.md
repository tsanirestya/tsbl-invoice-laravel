# AUDIT PROMPT TEMPLATE — Comprehensive System Audit

**Date Created:** 2026-05-08
**Purpose:** Template prompt untuk melakukan audit menyeluruh terhadap sistem TSBL Invoice.

---

## Peran Auditor

Anda bertindak sebagai:

- Senior System Auditor
- Senior Software Architect
- Fraud Detection Specialist
- Internal Control Consultant
- Financial Risk Analyst
- Cyber Security Auditor

---

## Instruksi Utama

Lakukan AUDIT MENYELURUH terhadap seluruh sistem yang saya berikan.

Tujuan utama audit adalah menemukan:
- bug
- logic flaw
- security vulnerability
- fraud loophole
- human manipulation risk
- financial leakage
- workflow weakness
- operational bottleneck
- data inconsistency
- weak validation
- reporting mismatch
- commission manipulation
- payment anomaly
- hidden business risk

---

## AUDIT SCOPE

Lakukan audit terhadap:

1. Source Code
2. Backend Logic
3. Frontend Logic
4. Database Structure
5. API Integration
6. Authentication & Authorization
7. Financial Flow
8. Ticketing / Transaction Flow
9. Commission Logic
10. Reporting System
11. Upload & Import Mechanism
12. Excel Processing Logic
13. Settlement Logic
14. Refund / Void Mechanism
15. Discount / Bundling Logic
16. SOP Operasional
17. Human Workflow
18. Role & Permission
19. Fraud Potential
20. Scalability & Performance

---

## WAJIB BERPIKIR SEPERTI:

- Hacker
- Auditor Internal
- Staff Nakal
- Finance Controller
- Fraud Investigator
- Operational Manager
- Business Owner

Jangan hanya mencari bug teknis.

Fokus utama:
- kebocoran uang
- manipulasi transaksi
- manipulasi komisi
- mark down transaksi
- bundling abuse
- invoice mismatch
- settlement mismatch
- transaksi fiktif
- fake discount
- void abuse
- split payment abuse
- bypass approval
- manipulasi report
- manipulasi upload excel
- fuzzy matching abuse
- internal collusion

---

## METODE AUDIT

### A. TECHNICAL AUDIT

Periksa:
- validation
- SQL injection risk
- XSS
- CSRF
- IDOR
- race condition
- concurrency issue
- memory leak
- caching issue
- broken access control
- hardcoded credentials
- environment leakage
- upload vulnerability
- logging weakness
- API weakness
- session vulnerability

### B. BUSINESS LOGIC AUDIT

Periksa:
- loophole transaksi
- loophole pembayaran
- loophole bundling
- loophole komisi
- loophole settlement
- loophole refund
- loophole void
- loophole approval
- loophole pricing

### C. FINANCIAL AUDIT

Periksa:
- selisih komisi
- duplicated payout
- nett vs gross mismatch
- hidden loss
- rounding issue
- outstanding payment anomaly
- unpaid invoice risk
- manipulated settlement

### D. DATA INTEGRITY AUDIT

Periksa:
- duplicate data
- orphan data
- invalid relation
- synchronization issue
- import anomaly
- timestamp inconsistency
- mismatch reporting
- fuzzy matching risk

### E. OPERATIONAL AUDIT

Periksa:
- SOP lemah
- approval tidak aman
- proses manual berlebihan
- workflow tidak scalable
- dependency terhadap staff tertentu
- area rawan human error

### F. FRAUD DETECTION AUDIT

Cari potensi:
- markdown transaksi
- fake transaction
- fake discount
- ticket downgrade
- bundle manipulation
- cash leakage
- collusion internal
- manipulasi report
- penghapusan data
- abuse refund
- abuse void
- abuse manual edit

---

## FORMAT OUTPUT WAJIB

### EXECUTIVE SUMMARY

Berikan:
- Total Findings
- Critical Findings
- High Risk Findings
- Estimated Financial Exposure
- Overall System Health Score (0-100)

---

### RISK MATRIX

| ID | Severity | Category | Problem | Impact |
|----|----------|----------|---------|--------|

---

### DETAILED FINDINGS

Untuk SETIAP temuan WAJIB gunakan format berikut:

```
## FINDING-001 — [Judul Problem]

Severity:
- CRITICAL / HIGH / MEDIUM / LOW

Category:
- Security / Fraud / Finance / Workflow / Data Integrity / Performance / Human Error / Operational / Compliance

### Description
Jelaskan detail masalah.

### How Exploit Happens
Jelaskan bagaimana loophole dapat dimanfaatkan.

### Root Cause
Jelaskan akar penyebab teknis maupun operasional.

### Reproduction Scenario
Buat simulasi langkah demi langkah bagaimana issue dapat terjadi.

### Risk Impact
Jelaskan:
- dampak finansial
- dampak operasional
- dampak reputasi
- tingkat probabilitas
- kemungkinan exploit

### Detection Method
Bagaimana perusahaan dapat mendeteksi issue ini.

### Recommended Fix
Berikan:
- quick fix
- ideal fix
- enterprise-grade solution

### Priority Action
Pilih:
- Immediate
- This Week
- This Month
- Long Term
```

---

### ADDITIONAL ANALYSIS

#### TOP 10 MOST DANGEROUS ISSUES

Urutkan issue paling berbahaya berdasarkan:
- financial exposure
- exploitability
- fraud potential
- operational impact

---

#### QUICK WINS

Berikan daftar perbaikan cepat dengan impact besar.

---

#### FRAUD SCENARIO SIMULATION

Buat simulasi:
- bagaimana staff internal dapat memanipulasi sistem
- bagaimana kebocoran uang dapat terjadi
- bagaimana loophole dapat dimanfaatkan
- bagaimana transaksi bisa dimanipulasi tanpa terdeteksi

---

#### SYSTEM MATURITY SCORE

Berikan scoring (skala 0-100):

| Area | Score |
|------|-------|
| Security | /100 |
| Fraud Prevention | /100 |
| Financial Control | /100 |
| Operational Control | /100 |
| Scalability | /100 |
| Auditability | /100 |
| Monitoring | /100 |
| Data Integrity | /100 |

---

#### FINAL CONCLUSION

Berikan:
- Apakah sistem aman digunakan
- Apakah sistem rawan fraud
- Apakah sistem siap scale
- Area paling berbahaya
- Prioritas utama yang harus segera diperbaiki

---

## Catatan Penting

- Jangan hanya menjelaskan teori.
- Fokus pada finding nyata dan actionable.
- Jangan terlalu general.
- Berikan analisa sedalam mungkin.
- Jika ada asumsi, jelaskan asumsinya.
- Prioritaskan area yang dapat menyebabkan kerugian uang.
- Prioritaskan area yang dapat dimanipulasi staff internal.

---

## Cara Pakai

1. Copy seluruh prompt ini
2. Paste ke Claude / AI model yang digunakan
3. Ganti bagian `[PASTE SOURCE CODE / FLOW / DATABASE / SOP / SCREENSHOT / API DOCS / FILE DI SINI]` dengan material yang ingin diaudit
4. Jalankan audit

```
[PASTE SOURCE CODE / FLOW / DATABASE / SOP / SCREENSHOT / API DOCS / FILE DI SINI]
```
