# Design System — TSBL Invoice System

→ Kembali ke: [[MASTER-PLAN]]
→ Lihat juga: [[LARAVEL-STRUCTURE]] untuk component usage

---

## Color Palette
| Usage | Color | Bootstrap Class |
|---|---|---|
| Paid / Success | Green #198754 | `text-success`, `bg-success`, `badge-paid` |
| Overdue / Error | Red #dc3545 | `text-danger`, `bg-danger`, `badge-overdue` |
| Warning / Partial | Orange #fd7e14 | `text-warning`, `bg-warning`, `badge-partial` |
| Unpaid / Neutral | Gray #6c757d | `text-secondary`, `badge-unpaid` |
| Primary Actions | Blue #0d6efd | `btn-primary` |
| Background | Light Gray #f0f2f5 | body background |
| Sidebar | Dark Navy #1a2235 | custom |

---

## Typography
- Font: System default (no CDN dependency — load fast)
- Body: 14px minimum di mobile
- Headings: bold, condensed
- Numbers/amounts: `fw-bold` + `font-variant-numeric: tabular-nums`

---

## Layout Structure ✅ (Phase 1 done)

```
┌─────────────────────────────────────┐
│ SIDEBAR (250px fixed, desktop)      │
│  Logo + Nav links                   │
├─────────────────────────────────────┤
│ TOPBAR (56px sticky)                │
│  Hamburger | Page Title | User Info │
├─────────────────────────────────────┤
│ CONTENT AREA (padding 1.5rem)       │
│  @yield('content')                  │
├─────────────────────────────────────┤
│ BOTTOM NAV (mobile only, fixed)     │
│  Dashboard | Invoice | + | Partner | Menu │
└─────────────────────────────────────┘
```

**Mobile behavior:**
- Sidebar hidden by default → slide-in saat tap hamburger / Menu tab
- Backdrop overlay saat sidebar terbuka
- Bottom nav visible di `< 768px`
- Sidebar hidden di `< 768px`

---

## Component Standards

### Stat Cards
```html
<div class="card stat-card h-100">
  <div class="card-body d-flex align-items-center gap-3 p-3">
    <div class="stat-icon bg-{color} bg-opacity-10">
      <i class="bi bi-{icon} text-{color}"></i>
    </div>
    <div>
      <div class="text-muted small">Label</div>
      <div class="fw-bold fs-5">Nilai</div>
    </div>
  </div>
</div>
```

### Status Badges
```html
<span class="badge badge-paid">Lunas</span>
<span class="badge badge-overdue">Jatuh Tempo</span>
<span class="badge badge-partial">Partial</span>
<span class="badge badge-unpaid">Belum Bayar</span>
```

### Forms (Mobile-first)
- Input height minimum 48px
- `inputmode="decimal"` untuk field amount
- `inputmode="numeric"` untuk field angka
- `inputmode="tel"` untuk phone
- Label di atas input (bukan floating)
- Submit button: sticky bottom di mobile

### Tables
- Horizontal scroll wrapper `<div class="table-responsive">`
- Kolom penting selalu visible — kolom sekunder `d-none d-md-table-cell`
- Action buttons: icon-only di mobile, icon+text di desktop

### Alert/Flash Messages
```html
<div class="alert alert-success alert-dismissible fade show py-2">
  <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
</div>
```

---

## Navigation — Sidebar Links
| Icon | Label | Route | Role |
|---|---|---|---|
| bi-speedometer2 | Dashboard | dashboard | All |
| bi-file-earmark-text | Invoice | invoices.index | All |
| bi-people | Partner | partners.index | All |
| bi-cash-stack | Pembayaran | payments.index | Finance, Admin |
| bi-bar-chart-line | Laporan | reports.index | Finance, Admin |
| bi-box-seam | Produk | products.index | Admin |
| bi-person-gear | Pengguna | users.index | Admin only |
| bi-gear | Pengaturan | settings.index | Admin only |

---

## Bottom Nav (Mobile)
| Tab | Icon | Route |
|---|---|---|
| Dashboard | bi-speedometer2 | dashboard |
| Invoice | bi-file-earmark-text | invoices.index |
| + (FAB) | bi-plus-lg | invoices.create |
| Partner | bi-people | partners.index |
| Menu | bi-list | toggle sidebar |

---

## Invoice PDF Layout (Phase 3)
```
┌────────────────────────────────┐
│ [LOGO]   TSBL                  │
│          Alamat | Phone        │
├────────────────────────────────┤
│ INVOICE No: INV-2026-0001      │
│ Tanggal: 05/05/2026            │
│ Jatuh Tempo: 19/05/2026        │
├────────────────────────────────┤
│ Kepada: [Partner Name]         │
│         [Alamat]               │
├────────────────────────────────┤
│ Tamu: [guest_name]             │
│ Visit Date: [visit_date]       │
├────────────────────────────────┤
│ No │ Item │ Pax │ Price │ Amt  │
│ 1  │ ...  │  2  │  ...  │ ... │
├────────────────────────────────┤
│              Subtotal: xxx     │
│              Deposit:  xxx     │
│              TOTAL:    xxx     │
├────────────────────────────────┤
│ Terbilang: [teks]              │
├────────────────────────────────┤
│ Pembuat        Authorized      │
│ [signature]    [signature]     │
│ [nama]         [nama]          │
└────────────────────────────────┘
WATERMARK: "LUNAS" (green) / "BELUM LUNAS" (red)
```

---

## Breakpoints (Bootstrap 5)
| Prefix | Breakpoint | Target |
|---|---|---|
| (none) | < 576px | Mobile portrait |
| sm | ≥ 576px | Mobile landscape |
| md | ≥ 768px | Tablet |
| lg | ≥ 992px | Desktop |

---

## Related Notes
- [[MASTER-PLAN]] — project roadmap
- [[LARAVEL-STRUCTURE]] — component & route map
