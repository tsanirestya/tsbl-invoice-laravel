# PLAN: Booking Pass No. Validation di Antrian Invoice

- **Date:** 2026-05-15
- **Status:** PLANNED
- **Priority:** Medium
- **Related Feature:** Pending Invoice Queue (`/pending-invoices`)

---

## Latar Belakang

Field `remark` pada `transaction_import_rows` berisi **Reservation No.** yang diinput secara manual oleh operator DSI (sistem eksternal) saat transaksi terjadi. Nilai ini seharusnya merujuk ke `reservations.reservation_no` yang ada di sistem internal TSBL.

Saat ini antrian invoice tidak menampilkan remark ini sama sekali, sehingga tidak ada cara cepat untuk tahu apakah setiap transaksi punya Reservation No. yang valid sebelum invoice dibuat.

---

## Tujuan

1. Tampilkan `remark` sebagai **Booking Pass No.** di tabel antrian invoice
2. Validasi otomatis: cek apakah nilai remark ada di tabel `reservations` (kolom `reservation_no`)
3. Tampilkan status indikator warna:
   - **Abu-abu** (default) → remark kosong / null
   - **Hijau** ✓ → remark ditemukan di tabel `reservations`
   - **Merah** ✗ → remark ada nilainya tapi tidak ditemukan di `reservations`
4. Status **tidak bisa diubah manual** (auto-derived dari DB)
5. Jika **hijau**: tidak ada opsi edit
6. Jika **merah atau abu-abu**: tampilkan tombol edit kecil untuk update remark

---

## Data Flow

```
transaction_import_rows.remark
        ↓
  (batch lookup)
        ↓
reservations.reservation_no
        ↓
  status: empty | found | not_found
        ↓
  UI indicator + kondisional edit button
```

---

## Komponen yang Berubah

### 1. Controller — `PendingInvoiceController::index()`

**File:** `app/Http/Controllers/PendingInvoiceController.php`

Perubahan di blok `$grouped->map()`:
- Tambah field `booking_pass_no` → ambil dari `$first->remark`
- Tambah field `booking_pass_status`:
  - `'empty'` jika `remark` null/kosong
  - `'found'` jika remark ada di set `$validReservationNos`
  - `'not_found'` jika remark ada tapi tidak match di reservations

Sebelum `$grouped->map()`, lakukan batch lookup:
```php
// Kumpulkan semua remark non-empty dari grouped
$allRemarks = $allRows
    ->pluck('remark')
    ->filter()
    ->unique()
    ->values()
    ->toArray();

// Lookup ke reservations table
$validReservationNos = \App\Models\Reservation::whereIn('reservation_no', $allRemarks)
    ->pluck('reservation_no')
    ->flip() // jadikan key untuk O(1) lookup
    ->toArray();
```

Lalu di dalam `map()`:
```php
$remark = $first->remark;
$bookingPassStatus = match(true) {
    empty($remark)                             => 'empty',
    isset($validReservationNos[$remark])       => 'found',
    default                                    => 'not_found',
};

return (object) [
    // ... existing fields ...
    'booking_pass_no'     => $remark,
    'booking_pass_status' => $bookingPassStatus,
];
```

---

### 2. View — Kolom Baru "Booking Pass No."

**File:** `resources/views/pending-invoices/index.blade.php`

**Posisi kolom:** Setelah kolom "No. Transaksi", sebelum "Tiket / Layanan"

**Header:**
```html
<th class="d-none d-md-table-cell">Booking Pass No.</th>
```

**Cell content:**
```html
<td class="d-none d-md-table-cell">
    @php
        $bpStatus = $trx->booking_pass_status;
        $bpNo     = $trx->booking_pass_no;
    @endphp

    <div class="d-flex align-items-center gap-2">
        {{-- Nilai remark --}}
        @if($bpNo)
            <span class="text-monospace small fw-semibold" style="font-size:.78rem">
                {{ $bpNo }}
            </span>
        @else
            <span class="text-muted small" style="font-size:.78rem">—</span>
        @endif

        {{-- Status indicator --}}
        @if($bpStatus === 'found')
            <span class="bp-badge bp-found" title="Reservation ditemukan">
                <i class="bi bi-check-circle-fill"></i>
            </span>
        @elseif($bpStatus === 'not_found')
            <span class="bp-badge bp-notfound" title="Reservation tidak ditemukan di sistem">
                <i class="bi bi-x-circle-fill"></i>
            </span>
        @else
            <span class="bp-badge bp-empty" title="Belum ada Reservation No.">
                <i class="bi bi-circle"></i>
            </span>
        @endif

        {{-- Edit button (hanya jika bukan found) --}}
        @if($bpStatus !== 'found')
            <button class="btn-bp-edit"
                    data-trx="{{ $trx->transaction_no }}"
                    data-current="{{ $bpNo }}"
                    title="Edit Reservation No.">
                <i class="bi bi-pencil-fill"></i>
            </button>
        @endif
    </div>
</td>
```

---

### 3. Styles Baru

Tambah di `@push('styles')`:

```css
/* ── Booking Pass status badges ── */
.bp-badge {
    display: inline-flex;
    align-items: center;
    font-size: .85rem;
    line-height: 1;
}
.bp-found    { color: #198754; } /* Bootstrap success green */
.bp-notfound { color: #dc3545; } /* Bootstrap danger red   */
.bp-empty    { color: #adb5bd; } /* Bootstrap gray         */

/* ── Edit button ── */
.btn-bp-edit {
    background: none;
    border: none;
    padding: .1rem .25rem;
    color: #6c757d;
    font-size: .72rem;
    border-radius: 4px;
    cursor: pointer;
    transition: color .15s, background .15s;
    line-height: 1;
}
.btn-bp-edit:hover {
    color: #0d6efd;
    background: #e8f0ff;
}
```

---

### 4. Modal Edit Remark

Tambah satu modal di bawah `@endsection` (sebelum tutupnya):

```html
{{-- Modal: Edit Booking Pass No. / Reservation No. --}}
<div class="modal fade" id="modalBpEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold">Edit Reservation No.</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <form id="formBpEdit" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body py-3">
                    <div class="mb-0">
                        <label class="form-label fw-semibold mb-1" style="font-size:.8rem">
                            No. Transaksi
                        </label>
                        <div class="text-muted small mb-2" id="bpEditTrxNo"></div>
                        <label class="form-label fw-semibold mb-1" style="font-size:.8rem">
                            Reservation No. (Remark)
                        </label>
                        <input type="text" name="remark" id="bpEditInput"
                               class="form-control form-control-sm"
                               placeholder="Contoh: RES-20260515-0001"
                               maxlength="100">
                        <div class="form-text text-muted" style="font-size:.72rem">
                            Nilai ini akan disinkronkan dengan Reservation No. di sistem.
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                            data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="bi bi-check2 me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
```

---

### 5. JavaScript — Wiring Modal

Tambah di `@push('scripts')`:

```javascript
document.addEventListener('DOMContentLoaded', function () {
    const modal    = new bootstrap.Modal(document.getElementById('modalBpEdit'));
    const form     = document.getElementById('formBpEdit');
    const input    = document.getElementById('bpEditInput');
    const trxLabel = document.getElementById('bpEditTrxNo');

    document.querySelectorAll('.btn-bp-edit').forEach(btn => {
        btn.addEventListener('click', function () {
            const trxNo  = this.dataset.trx;
            const current = this.dataset.current || '';
            form.action  = `/pending-invoices/${trxNo}/remark`;
            trxLabel.textContent = trxNo;
            input.value  = current;
            modal.show();
        });
    });
});
```

---

### 6. Route Baru

**File:** `routes/web.php`

```php
Route::patch('/pending-invoices/{transaction_no}/remark', [PendingInvoiceController::class, 'updateRemark'])
    ->name('pending-invoices.update-remark');
```

---

### 7. Method Baru di Controller

**File:** `app/Http/Controllers/PendingInvoiceController.php`

```php
public function updateRemark(Request $request, string $transactionNo)
{
    $request->validate([
        'remark' => ['nullable', 'string', 'max:100'],
    ]);

    // Update semua baris dengan transaction_no yang sama
    TransactionImportRow::where('transaction_no', $transactionNo)
        ->update(['remark' => $request->input('remark')]);

    return redirect()->back()
        ->with('success', "Reservation No. untuk transaksi {$transactionNo} berhasil diperbarui.");
}
```

---

## Urutan Implementasi

| # | File | Aksi |
|---|------|------|
| 1 | `PendingInvoiceController.php` | Tambah batch lookup + `booking_pass_no` & `booking_pass_status` di grouped object |
| 2 | `PendingInvoiceController.php` | Tambah method `updateRemark()` |
| 3 | `routes/web.php` | Tambah PATCH route |
| 4 | `pending-invoices/index.blade.php` | Tambah kolom header + cell + styles + modal + JS |

---

## Edge Cases & Catatan

| Case | Handling |
|------|----------|
| Satu transaction_no punya beberapa rows dengan remark berbeda | Ambil dari `$first->remark` (row pertama) — sudah konsisten karena DSI input per-transaksi |
| Remark berisi whitespace saja | Gunakan `trim()` atau `filled()` untuk deteksi empty |
| Update remark — apakah update semua rows? | Ya, semua rows dengan `transaction_no` yang sama diupdate seragam |
| Setelah update, perlu re-validasi? | Ya — redirect back akan reload halaman dan re-run lookup |
| Kolom remark bisa null di DB? | Ya — sudah ada di fillable, tidak ada `NOT NULL` constraint yang ketat |

---

## UI Wireframe (Teks)

```
┌─────────────────┬───────────────────────────────────────────┬───────────┐
│ No. Transaksi   │ Booking Pass No.                          │ Tiket     │
├─────────────────┼───────────────────────────────────────────┼───────────┤
│ TRX-20260515-01 │ RES-20260512-0042  ✓ (hijau)             │ Entrance  │
│ TRX-20260515-02 │ RES-20260515-0099  ✗ (merah)  [✏ Edit]  │ Boat Tour │
│ TRX-20260515-03 │ —                  ○ (abu)    [✏ Edit]  │ Parking   │
└─────────────────┴───────────────────────────────────────────┴───────────┘
```

---

## Commit Plan

```
feat: add booking pass no. validation to invoice queue

- show remark as Booking Pass No. in pending invoice table
- color-coded indicator: gray (empty), green (found), red (not found)
- edit option when remark missing or reservation not found
- PATCH endpoint to update remark for all rows of a transaction
```
