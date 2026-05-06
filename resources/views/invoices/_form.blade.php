{{-- Shared form partial for create/edit invoice --}}
@php $invoice ??= null; @endphp

@push('styles')
<style>
    .item-row td { vertical-align: middle; }
    .item-row .btn-remove-row { opacity: .4; }
    .item-row:hover .btn-remove-row { opacity: 1; }
    #items-table tfoot td { background: #f8f9fa; }
    #finance-check-card { border-left: 4px solid #ffc107 !important; }

    /* Tom Select sizing tweak for table rows */
    .item-row .ts-wrapper.form-select { padding: 0; }
    .item-row .ts-control { font-size: .8rem; min-height: 31px; padding: .25rem .5rem; }
    .item-row .ts-dropdown { font-size: .8rem; }
    .ts-dropdown .ts-option-dsi { display: flex; align-items: center; gap: .4rem; padding: .3rem .5rem; }
    .ts-dropdown .ts-option-dsi .badge { font-size: .7rem; }
</style>
@endpush

<div class="row g-3">
    {{-- Left: Invoice header --}}
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom fw-semibold py-2">Info Invoice</div>
            <div class="card-body">
                {{-- Partner --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Partner <span class="text-danger">*</span></label>
                    <select name="partner_id" id="partner_id" class="form-select @error('partner_id') is-invalid @enderror" required>
                        <option value="">— Pilih Partner —</option>
                        @foreach($partners as $p)
                            <option value="{{ $p->id }}"
                                    data-due="{{ $p->payment_due_days }}"
                                    @selected(old('partner_id', $invoice?->partner_id ?? '') == $p->id)>
                                {{ $p->nama_partner }} ({{ $p->partner_type }})
                            </option>
                        @endforeach
                    </select>
                    @error('partner_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row g-2">
                    {{-- Guest Name --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label">Nama Tamu</label>
                        <input type="text" name="guest_name" class="form-control"
                               value="{{ old('guest_name', $invoice?->guest_name ?? '') }}" maxlength="200">
                    </div>
                    {{-- Booking Pass --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label">Booking Pass No</label>
                        <input type="text" name="booking_pass_no" class="form-control"
                               value="{{ old('booking_pass_no', $invoice?->booking_pass_no ?? '') }}" maxlength="100">
                    </div>
                    {{-- Visit Date --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label">Tanggal Kunjungan</label>
                        <input type="date" name="visit_date" class="form-control"
                               value="{{ old('visit_date', $invoice?->visit_date?->format('Y-m-d') ?? '') }}">
                    </div>
                    {{-- DSI --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label">No. Transaksi DSI</label>
                        <input type="text" name="dsi_transaction_no" class="form-control"
                               value="{{ old('dsi_transaction_no', $invoice?->dsi_transaction_no ?? '') }}" maxlength="100">
                    </div>
                    {{-- Invoice Date --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Tgl Invoice <span class="text-danger">*</span></label>
                        <input type="date" name="invoice_date" id="invoice_date" class="form-control @error('invoice_date') is-invalid @enderror"
                               value="{{ old('invoice_date', $invoice?->invoice_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
                        @error('invoice_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    {{-- Due Date --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label">Jatuh Tempo</label>
                        <input type="date" name="due_date" id="due_date" class="form-control @error('due_date') is-invalid @enderror"
                               value="{{ old('due_date', $invoice?->due_date?->format('Y-m-d') ?? '') }}">
                        <div class="form-text">Kosongkan = auto dari partner due days</div>
                        @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Notes --}}
                <div class="mt-2">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-control" rows="2" maxlength="1000">{{ old('notes', $invoice?->notes ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- Right: Totals summary --}}
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom fw-semibold py-2">Ringkasan</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Subtotal</span>
                    <span id="summary-subtotal" class="fw-semibold">Rp 0</span>
                </div>
                <div class="mb-2">
                    <label class="form-label text-muted mb-0">Deposit</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="deposit" id="deposit" class="form-control"
                               value="{{ old('deposit', $invoice?->deposit ?? 0) }}"
                               min="0" step="1000" oninput="recalc()">
                    </div>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between">
                    <span class="fw-bold">Grand Total</span>
                    <span id="summary-grand" class="fw-bold fs-6 text-primary">Rp 0</span>
                </div>
                <div id="summary-terbilang" class="text-muted small fst-italic mt-1"></div>
            </div>
        </div>
    </div>
</div>

{{-- Line Items --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold">Item Invoice</span>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow()">
            <i class="bi bi-plus-lg me-1"></i> Tambah Baris
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0" id="items-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:33%">Produk / Layanan <span class="text-danger">*</span></th>
                        <th style="width:22%">DSI Code</th>
                        <th class="text-center" style="width:10%">Pax</th>
                        <th class="text-end" style="width:17%">Harga/Pax</th>
                        <th class="text-end" style="width:15%">Jumlah</th>
                        <th style="width:3%"></th>
                    </tr>
                </thead>
                <tbody id="items-body">
                    @php
                        $existingItems = old('items',
                            isset($invoice) && isset($invoice->items) && is_iterable($invoice->items)
                                ? $invoice->items->toArray()
                                : []
                        );
                    @endphp
                    @forelse($existingItems as $i => $item)
                    @php $existingProductId = $item['product_id'] ?? ''; @endphp
                    <tr class="item-row" data-product-id="{{ $existingProductId }}">
                        <td class="ps-3">
                            <input type="text" name="items[{{ $i }}][product_name]"
                                   class="form-control form-control-sm item-name @error("items.{$i}.product_name") is-invalid @enderror"
                                   value="{{ $item['product_name'] ?? '' }}" required placeholder="Nama layanan">
                            @error("items.{$i}.product_name")<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </td>
                        <td>
                            <input type="hidden" name="items[{{ $i }}][product_id]" class="item-product-id" value="{{ $existingProductId }}">
                            <select class="form-select form-select-sm product-picker">
                                <option value="">— Pilih —</option>
                                @foreach($products as $prod)
                                    <option value="{{ $prod->id }}"
                                            data-price="{{ $prod->nett_price }}"
                                            data-name="{{ $prod->product_name }}"
                                            data-product-name="{{ $prod->product_name }}"
                                            @selected($existingProductId == $prod->id)>
                                        {{ $prod->dsi_code }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td class="text-center">
                            <input type="number" name="items[{{ $i }}][pax]"
                                   class="form-control form-control-sm text-center item-pax @error("items.{$i}.pax") is-invalid @enderror"
                                   value="{{ $item['pax'] ?? 1 }}" min="1" required oninput="recalcRow(this)">
                        </td>
                        <td class="text-end">
                            <input type="number" name="items[{{ $i }}][price_per_pax]"
                                   class="form-control form-control-sm text-end item-price @error("items.{$i}.price_per_pax") is-invalid @enderror"
                                   value="{{ $item['price_per_pax'] ?? 0 }}" min="0" step="1000" required oninput="recalcRow(this)">
                        </td>
                        <td class="text-end">
                            <span class="item-amount text-nowrap">{{ number_format(($item['pax'] ?? 1) * ($item['price_per_pax'] ?? 0), 0, ',', '.') }}</span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-link text-danger btn-remove-row p-0" onclick="removeRow(this)">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    {{-- default blank row injected by JS --}}
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end fw-semibold ps-3">Subtotal</td>
                        <td class="text-end"><span id="foot-subtotal">0</span></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @error('items')<div class="alert alert-danger m-3 py-2">{{ $message }}</div>@enderror
    </div>
</div>

{{-- Finance Double Check — not submitted, not in PDF --}}
<div class="card border-0 shadow-sm mb-3" id="finance-check-card">
    <div class="card-header bg-warning-subtle border-bottom d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold"><i class="bi bi-calculator me-1"></i>Pengecekan Finance</span>
        <span class="badge bg-warning text-dark">Internal — tidak muncul di invoice</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0" id="finance-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:4%">#</th>
                        <th style="width:11%">DSI Code</th>
                        <th style="width:22%">Produk</th>
                        <th class="text-center" style="width:6%">Pax</th>
                        <th class="text-end" style="width:14%">Publish Rate</th>
                        <th class="text-end" style="width:14%">Total Publish</th>
                        <th class="text-end" style="width:12%">Komisi</th>
                        <th class="text-end" style="width:12%">Nett Price</th>
                        <th class="text-end" style="width:9%">% Komisi</th>
                    </tr>
                </thead>
                <tbody id="finance-body">
                    <tr id="finance-empty-row">
                        <td colspan="9" class="text-center text-muted py-3 ps-3">Pilih produk di atas untuk melihat kalkulasi</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="table-light fw-semibold">
                        <td colspan="5" class="text-end ps-3">Total</td>
                        <td class="text-end" id="finance-total-publish">—</td>
                        <td class="text-end" id="finance-total-komisi">—</td>
                        <td class="text-end" id="finance-total-nett">—</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@push('scripts')
@php
$productsJs = $products->map(function($p) {
    return [
        'id'           => $p->id,
        'dsi_code'     => $p->dsi_code,
        'name'         => $p->product_name,
        'price'        => (float) $p->nett_price,
        'publish_rate' => (float) $p->publish_rate,
        'komisi'       => (float) $p->komisi,
        'nett_price'   => (float) $p->nett_price,
    ];
})->values();
@endphp
<script>
const products = @json($productsJs);
let rowIdx = {{ count($existingItems) }};
const defaultDue = {{ $defaultDue ?? 14 }};

function fmt(n) {
    return new Intl.NumberFormat('id-ID').format(Math.round(n));
}

// ── Tom Select: product picker ─────────────────────────────────────────────
function makePicker(sel) {
    const ts = new TomSelect(sel, {
        allowEmptyOption: true,
        placeholder: '— Ketik DSI Code / nama —',
        dropdownParent: 'body',
        searchField: ['text', 'product_name'],
        render: {
            option: function(data, escape) {
                const name = escape(data.product_name || '');
                const code = escape(data.text || '');
                return `<div class="ts-option-dsi">
                    <span class="badge bg-secondary font-monospace">${code}</span>
                    <span class="text-muted">${name}</span>
                </div>`;
            },
            item: function(data, escape) {
                const name = escape(data.product_name || '');
                const code = escape(data.text || '');
                return `<div class="d-flex align-items-center gap-1">
                    <span class="badge bg-secondary font-monospace">${code}</span>
                    <small class="text-muted">${name}</small>
                </div>`;
            },
            no_results: function(data, escape) {
                return `<div class="no-results px-2 py-1">Tidak ditemukan: "${escape(data.input)}"</div>`;
            }
        },
        onChange: function(value) {
            pickProduct(sel, value);
        }
    });
    return ts;
}

// ── Tom Select: partner picker ─────────────────────────────────────────────
function makePartnerPicker() {
    const sel = document.getElementById('partner_id');
    if (!sel) return;
    new TomSelect(sel, {
        allowEmptyOption: true,
        placeholder: '— Ketik nama partner —',
        searchField: ['text'],
        onChange: function(value) {
            applyDueDate(value);
        }
    });
}

function applyDueDate(partnerId) {
    const sel = document.getElementById('partner_id');
    const opt = Array.from(sel.options).find(o => o.value == partnerId);
    if (!opt) return;
    const due  = parseInt(opt.dataset.due || defaultDue);
    const base = document.getElementById('invoice_date').value;
    if (base && due) {
        const d = new Date(base);
        d.setDate(d.getDate() + due);
        document.getElementById('due_date').value = d.toISOString().split('T')[0];
    }
}

// ── Invoice date change → recalc due date ─────────────────────────────────
document.getElementById('invoice_date')?.addEventListener('change', function() {
    const partnerSel = document.getElementById('partner_id');
    if (partnerSel?.value) applyDueDate(partnerSel.value);
});

// ── Row management ─────────────────────────────────────────────────────────
function addRow(name = '', productId = '', pax = 1, price = 0) {
    const i = rowIdx++;
    const opts = products.map(p =>
        `<option value="${p.id}"
            data-price="${p.price}"
            data-name="${p.name}"
            data-product-name="${p.name}"
            ${p.id == productId ? 'selected' : ''}>${p.dsi_code}</option>`
    ).join('');

    const amount = pax * price;
    const tbody  = document.getElementById('items-body');
    const tr     = document.createElement('tr');
    tr.className = 'item-row';
    tr.dataset.productId = productId;
    tr.innerHTML = `
        <td class="ps-3">
            <input type="text" name="items[${i}][product_name]" class="form-control form-control-sm item-name"
                   value="${name}" required placeholder="Nama layanan">
        </td>
        <td>
            <input type="hidden" name="items[${i}][product_id]" class="item-product-id" value="${productId}">
            <select class="form-select form-select-sm product-picker">
                <option value="">— Pilih —</option>${opts}
            </select>
        </td>
        <td class="text-center">
            <input type="number" name="items[${i}][pax]" class="form-control form-control-sm text-center item-pax"
                   value="${pax}" min="1" required oninput="recalcRow(this)">
        </td>
        <td class="text-end">
            <input type="number" name="items[${i}][price_per_pax]" class="form-control form-control-sm text-end item-price"
                   value="${price}" min="0" step="1000" required oninput="recalcRow(this)">
        </td>
        <td class="text-end">
            <span class="item-amount text-nowrap">${fmt(amount)}</span>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-link text-danger btn-remove-row p-0" onclick="removeRow(this)">
                <i class="bi bi-x-lg"></i>
            </button>
        </td>`;
    tbody.appendChild(tr);
    makePicker(tr.querySelector('.product-picker'));
    recalc();
}

function removeRow(btn) {
    const rows = document.querySelectorAll('#items-body .item-row');
    if (rows.length <= 1) return;
    btn.closest('tr').remove();
    recalc();
}

function pickProduct(sel, value) {
    const row = sel.closest('tr');
    row.querySelector('.item-product-id').value = value || '';
    row.dataset.productId = value || '';
    if (value) {
        const opt = Array.from(sel.options).find(o => o.value == value);
        if (opt) {
            row.querySelector('.item-name').value  = opt.dataset.name;
            row.querySelector('.item-price').value = opt.dataset.price;
            recalcRow(row.querySelector('.item-pax'));
            return;
        }
    }
    refreshFinance();
}

// ── Calculations ───────────────────────────────────────────────────────────
function recalcRow(input) {
    const row   = input.closest('tr');
    const pax   = parseFloat(row.querySelector('.item-pax').value)   || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    row.querySelector('.item-amount').textContent = fmt(pax * price);
    recalc();
}

function recalc() {
    let subtotal = 0;
    document.querySelectorAll('#items-body .item-row').forEach(row => {
        const pax   = parseFloat(row.querySelector('.item-pax')?.value)   || 0;
        const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
        subtotal   += pax * price;
    });
    const deposit    = parseFloat(document.getElementById('deposit').value) || 0;
    const grandTotal = Math.max(0, subtotal - deposit);

    document.getElementById('summary-subtotal').textContent  = 'Rp ' + fmt(subtotal);
    document.getElementById('summary-grand').textContent     = 'Rp ' + fmt(grandTotal);
    document.getElementById('foot-subtotal').textContent     = fmt(subtotal);
    document.getElementById('summary-terbilang').textContent = terbilang(grandTotal) + ' rupiah';

    refreshFinance();
}

function refreshFinance() {
    const tbody    = document.getElementById('finance-body');
    let totalPub   = 0, totalKom = 0, totalNett = 0;
    let rowNum     = 1;
    let hasProduct = false;
    const newRows  = [];

    document.querySelectorAll('#items-body .item-row').forEach(row => {
        const productId = row.dataset.productId;
        const pax       = parseFloat(row.querySelector('.item-pax')?.value) || 0;
        const prod      = products.find(p => p.id == productId);
        if (!prod) return;

        hasProduct = true;
        const tPub  = prod.publish_rate * pax;
        const tKom  = prod.komisi * pax;
        const tNett = prod.nett_price * pax;
        const pct   = prod.publish_rate > 0 ? (prod.komisi / prod.publish_rate * 100) : null;

        totalPub  += tPub;
        totalKom  += tKom;
        totalNett += tNett;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="ps-3 text-muted">${rowNum++}</td>
            <td><span class="badge bg-secondary font-monospace">${prod.dsi_code || '—'}</span></td>
            <td class="text-truncate" style="max-width:160px" title="${prod.name}">${prod.name}</td>
            <td class="text-center">${pax}</td>
            <td class="text-end text-muted">Rp ${fmt(prod.publish_rate)}</td>
            <td class="text-end fw-semibold">Rp ${fmt(tPub)}</td>
            <td class="text-end">Rp ${fmt(tKom)}</td>
            <td class="text-end">Rp ${fmt(tNett)}</td>
            <td class="text-end">${pct !== null ? '<span class="badge bg-info text-dark">' + pct.toFixed(1) + '%</span>' : '—'}</td>
        `;
        newRows.push(tr);
    });

    tbody.innerHTML = '';
    if (!hasProduct) {
        const empty = document.createElement('tr');
        empty.innerHTML = '<td colspan="9" class="text-center text-muted py-3 ps-3">Pilih produk di atas untuk melihat kalkulasi</td>';
        tbody.appendChild(empty);
        document.getElementById('finance-total-publish').textContent = '—';
        document.getElementById('finance-total-komisi').textContent  = '—';
        document.getElementById('finance-total-nett').textContent    = '—';
    } else {
        newRows.forEach(tr => tbody.appendChild(tr));
        document.getElementById('finance-total-publish').textContent = 'Rp ' + fmt(totalPub);
        document.getElementById('finance-total-komisi').textContent  = 'Rp ' + fmt(totalKom);
        document.getElementById('finance-total-nett').textContent    = 'Rp ' + fmt(totalNett);
    }
}

// ── Terbilang ──────────────────────────────────────────────────────────────
function terbilang(n) {
    n = Math.round(n);
    if (n === 0) return 'nol';
    const ones = ['','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan',
                  'sepuluh','sebelas','dua belas','tiga belas','empat belas','lima belas',
                  'enam belas','tujuh belas','delapan belas','sembilan belas'];
    const tens = ['','','dua puluh','tiga puluh','empat puluh','lima puluh',
                  'enam puluh','tujuh puluh','delapan puluh','sembilan puluh'];
    function spell(x) {
        if (x < 20)   return ones[x];
        if (x < 100)  { const u = x%10; return tens[Math.floor(x/10)] + (u ? ' '+ones[u] : ''); }
        if (x < 1000) { const h=Math.floor(x/100),r=x%100; return (h===1?'seratus':ones[h]+' ratus')+(r?' '+spell(r):''); }
        if (x < 1e6)  { const t=Math.floor(x/1000),r=x%1000; return (t===1?'seribu':spell(t)+' ribu')+(r?' '+spell(r):''); }
        if (x < 1e9)  { const m=Math.floor(x/1e6),r=x%1e6; return spell(m)+' juta'+(r?' '+spell(r):''); }
        const b=Math.floor(x/1e9),r=x%1e9; return spell(b)+' miliar'+(r?' '+spell(r):'');
    }
    return spell(n);
}

// ── Init ───────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    makePartnerPicker();

    const rows = document.querySelectorAll('#items-body .item-row');
    if (rows.length === 0) {
        addRow(); // addRow calls makePicker internally
    } else {
        rows.forEach(row => makePicker(row.querySelector('.product-picker')));
    }

    recalc();
});
</script>
@endpush
