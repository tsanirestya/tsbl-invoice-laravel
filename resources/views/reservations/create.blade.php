@extends('layouts.app')

@section('title', 'Buat Reservasi')
@section('page-title', 'Buat Reservasi')

@section('content')
<div class="page-hdr d-flex align-items-center justify-content-between mb-3">
    <div>
        <div class="page-title">Buat Reservasi</div>
        <div class="page-sub">Input reservasi tamu baru (internal)</div>
    </div>
    <a href="{{ route('reservations.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger mb-3">
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('reservations.store') }}" id="resForm">
@csrf

<div class="row g-3">
    {{-- Left column --}}
    <div class="col-lg-7">
        {{-- Tamu --}}
        <div class="card mb-3">
            <div class="card-header fw-semibold"><i class="bi bi-person me-2"></i>Data Tamu</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-8">
                        <label class="form-label">Nama Tamu <span class="text-danger">*</span></label>
                        <input type="text" name="guest_name" value="{{ old('guest_name') }}"
                               class="form-control" required placeholder="Nama lengkap tamu">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Negara Tamu</label>
                        <select name="guest_country" id="countrySelect" class="form-select">
                            @include('partials._country_options', ['selected' => old('guest_country', 'Indonesia')])
                        </select>
                        <div class="mt-1">
                            <span id="marketBadge" class="badge bg-secondary small">DOMESTIC</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detail Reservasi --}}
        <div class="card mb-3">
            <div class="card-header fw-semibold"><i class="bi bi-calendar-event me-2"></i>Detail Reservasi</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-4">
                        <label class="form-label">Tanggal Kunjungan <span class="text-danger">*</span></label>
                        <input type="date" name="visit_date" value="{{ old('visit_date') }}"
                               class="form-control" required>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Tipe Reservasi <span class="text-danger">*</span></label>
                        <select name="reservation_type" class="form-select" required>
                            <option value="INTERNAL" {{ old('reservation_type','INTERNAL') === 'INTERNAL' ? 'selected' : '' }}>Internal (Tim TSBL)</option>
                            <option value="PARTNER" {{ old('reservation_type') === 'PARTNER' ? 'selected' : '' }}>Partner</option>
                            <option value="SELF_SERVICE" {{ old('reservation_type') === 'SELF_SERVICE' ? 'selected' : '' }}>Self-Service</option>
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Partner</label>
                        <select name="partner_id" id="partnerSelect">
                            <option value="">— Tanpa Partner —</option>
                            @foreach($partners as $p)
                                <option value="{{ $p->id }}"
                                        {{ old('partner_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->nama_partner }} ({{ $p->partner_type }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Metode Pembayaran</label>
                        <select name="payment_method" class="form-select" id="paymentMethod">
                            <option value="">— Pilih —</option>
                            <option value="TRANSFER_GROSS" {{ old('payment_method') === 'TRANSFER_GROSS' ? 'selected' : '' }}>Transfer Gross</option>
                            <option value="TRANSFER_NETT" {{ old('payment_method') === 'TRANSFER_NETT' ? 'selected' : '' }}>Transfer Nett</option>
                            <option value="ON_THE_SPOT" {{ old('payment_method') === 'ON_THE_SPOT' ? 'selected' : '' }}>On The Spot</option>
                        </select>
                        <div class="mt-1">
                            <span id="payModeBadge" class="badge bg-secondary small">—</span>
                        </div>
                    </div>
                    <div class="col-sm-6" id="channelWrap" style="display:none">
                        <label class="form-label">Channel Pembayaran</label>
                        <select name="payment_channel" class="form-select">
                            <option value="">— Pilih —</option>
                            <option value="CASH">Cash</option>
                            <option value="DEBIT">Debit</option>
                            <option value="CREDIT">Credit</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Activities --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center fw-semibold">
                <span><i class="bi bi-box-seam me-2"></i>Aktivitas</span>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addActivityBtn">
                    <i class="bi bi-plus-lg"></i> Tambah Aktivitas
                </button>
            </div>
            <div class="card-body">
                <div id="activitiesContainer">
                    <div id="emptyMessage" class="text-center text-muted py-3">
                        Belum ada aktivitas. Klik "+ Tambah Aktivitas".
                    </div>
                </div>
            </div>
        </div>

        {{-- Baby (global) --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-sm-4">
                        <label class="form-label">
                            Jumlah Baby
                            <span class="badge bg-success ms-1 fw-normal">FREE</span>
                        </label>
                        <input type="number" name="pax_babies" id="paxBabies" value="{{ old('pax_babies', 0) }}"
                               min="0" class="form-control" placeholder="0">
                        <div class="form-text">Baby tidak dikenakan biaya</div>
                    </div>
                    <div class="col-sm-8">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Opsional">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Right column --}}
    <div class="col-lg-5">
        {{-- Hidden GPS --}}
        <input type="hidden" name="latitude" id="lat">
        <input type="hidden" name="longitude" id="lng">
        <input type="hidden" name="location_name" id="locationName">

        {{-- Pax Summary --}}
        <div class="card mb-3">
            <div class="card-header fw-semibold"><i class="bi bi-people me-2"></i>Ringkasan Tamu & Biaya</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td class="text-muted">Adult</td>
                        <td class="text-end"><span id="summAdults">0</span> pax</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Child</td>
                        <td class="text-end"><span id="summKids">0</span> pax</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Baby <span class="badge bg-success fw-normal">FREE</span></td>
                        <td class="text-end"><span id="summBabies">0</span> pax</td>
                    </tr>
                    <tr class="table-light">
                        <td class="fw-semibold">Gross Total</td>
                        <td class="text-end fw-semibold" id="summGross">Rp 0</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Komisi</td>
                        <td class="text-end text-warning fw-semibold" id="summComm">Rp 0</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Nett Total</td>
                        <td class="text-end" id="summNett">Rp 0</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Lokasi --}}
        <div class="card mb-3 border-0 bg-light">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-geo-alt-fill text-secondary mt-1" style="font-size:.95rem"></i>
                    <div id="locationDisplay" class="small text-muted lh-sm">
                        <span class="fst-italic">Mendeteksi lokasi...</span>
                    </div>
                </div>
                <div id="dangerZoneWarning" class="badge bg-danger mt-2 d-none">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Danger Zone!
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 justify-content-end mb-4">
    <a href="{{ route('reservations.index') }}" class="btn btn-outline-secondary">Batal</a>
    <button type="submit" class="btn btn-primary" id="submitBtn">
        <i class="bi bi-check-lg me-1"></i> Simpan Reservasi
    </button>
</div>

</form>

{{-- Activity row template --}}
<template id="activityRowTpl">
<div class="activity-row border rounded p-3 mb-2" data-idx="__IDX__">
    <div class="d-flex align-items-center gap-2 mb-2">
        <div class="flex-grow-1">
            <label class="form-label mb-1 small fw-semibold">Aktivitas</label>
            <select name="items[__IDX__][parents_name]" class="form-select form-select-sm activity-select">
                <option value="">— Pilih Aktivitas —</option>
            </select>
        </div>
        <div class="align-self-end">
            <button type="button" class="btn btn-sm btn-outline-danger remove-activity">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>

    <input type="hidden" name="items[__IDX__][row_type]" class="row-type-input">

    {{-- ADULT_CHILD inputs --}}
    <div class="adult-child-inputs d-none">
        <div class="row g-2">
            <div class="col-sm-4">
                <label class="form-label mb-1 small">Adult <span class="adult-price-display text-muted"></span></label>
                <input type="number" name="items[__IDX__][adult_qty]" class="form-control form-control-sm adult-qty" value="1" min="0">
                <input type="hidden" name="items[__IDX__][adult_product_id]" class="adult-product-id">
                <input type="hidden" name="items[__IDX__][adult_price]" class="adult-price">
                <input class="adult-nett" type="hidden">
                <input class="adult-komisi" type="hidden">
            </div>
            <div class="col-sm-4 child-section">
                <label class="form-label mb-1 small">Child <span class="child-price-display text-muted"></span></label>
                <input type="number" name="items[__IDX__][child_qty]" class="form-control form-control-sm child-qty" value="0" min="0">
                <input type="hidden" name="items[__IDX__][child_product_id]" class="child-product-id">
                <input type="hidden" name="items[__IDX__][child_price]" class="child-price">
                <input class="child-nett" type="hidden">
                <input class="child-komisi" type="hidden">
            </div>
            <div class="col-sm-4">
                <label class="form-label mb-1 small">Subtotal</label>
                <div class="fw-semibold row-subtotal pt-1">Rp 0</div>
            </div>
        </div>
    </div>

    {{-- BUNDLE inputs --}}
    <div class="bundle-inputs d-none">
        <div class="row g-2">
            <div class="col-sm-5">
                <label class="form-label mb-1 small">Varian Bundle</label>
                <select name="items[__IDX__][bundle_product_id]" class="form-select form-select-sm bundle-select">
                    <option value="">— Pilih Varian —</option>
                </select>
                <div class="bundle-composition small text-muted mt-1"></div>
                <input type="hidden" name="items[__IDX__][bundle_price]" class="bundle-price">
                <input class="bundle-nett" type="hidden">
                <input class="bundle-komisi" type="hidden">
            </div>
            <div class="col-sm-3">
                <label class="form-label mb-1 small">Qty</label>
                <input type="number" name="items[__IDX__][bundle_qty]" class="form-control form-control-sm bundle-qty" value="1" min="1">
            </div>
            <div class="col-sm-4">
                <label class="form-label mb-1 small">Subtotal</label>
                <div class="fw-semibold row-subtotal pt-1">Rp 0</div>
            </div>
        </div>
    </div>

    {{-- TICKET / DOMESTIC inputs --}}
    <div class="ticket-inputs d-none">
        <div class="row g-2">
            <div class="col-sm-5">
                <label class="form-label mb-1 small">Produk</label>
                <div class="ticket-price-display small text-muted fst-italic">—</div>
                <input type="hidden" name="items[__IDX__][ticket_product_id]" class="ticket-product-id">
                <input type="hidden" name="items[__IDX__][ticket_price]" class="ticket-price">
                <input class="ticket-nett" type="hidden">
                <input class="ticket-komisi" type="hidden">
            </div>
            <div class="col-sm-3">
                <label class="form-label mb-1 small">Jumlah Tiket</label>
                <input type="number" name="items[__IDX__][ticket_qty]" class="form-control form-control-sm ticket-qty" value="1" min="1">
            </div>
            <div class="col-sm-4">
                <label class="form-label mb-1 small">Subtotal</label>
                <div class="fw-semibold row-subtotal pt-1">Rp 0</div>
            </div>
        </div>
    </div>
</div>
</template>

@endsection

@push('scripts')
<script>
// ── Product data from server ────────────────────────────────────────────────
const grouped = @json($groupedProducts);
// grouped[parentsName][subPayMode][marketType] = [{id, pax_type, publish_rate, nett_price, komisi, bundle_adult_count, bundle_child_count}]

let rowIdx = 0;

// ── Helpers ─────────────────────────────────────────────────────────────────
function getPayMode() {
    const pm = document.getElementById('paymentMethod').value;
    if (pm === 'TRANSFER_NETT') return 'NETT';
    if (pm === 'TRANSFER_GROSS' || pm === 'ON_THE_SPOT') return 'GROSS';
    return '';
}

function getMarketType() {
    const country = document.querySelector('#countrySelect')?.value
                  || document.querySelector('[name="guest_country"]')?.value
                  || 'Indonesia';
    return (country === 'Indonesia') ? 'DOMESTIC' : 'FOREIGN';
}

function fmtNum(n) {
    return Math.round(n).toLocaleString('id-ID');
}

// ── Activity options based on current payMode + market ─────────────────────
function getActivityOptions() {
    const payMode = getPayMode();
    const market  = getMarketType();
    const opts = [];

    for (const [parent, payModes] of Object.entries(grouped)) {
        let show = false;
        if (!payMode) {
            // No payment selected — show all that have any products for this market
            show = Object.values(payModes).some(markets =>
                Object.keys(markets).some(m => m === market || m === 'DOMESTIC' || m === 'FOREIGN')
            );
        } else {
            show = (payModes[payMode]?.[market]?.length ?? 0) > 0;
        }
        if (show) opts.push(parent);
    }
    return opts.sort();
}

function detectRowType(parentsName, payMode, marketType) {
    if (marketType === 'DOMESTIC') return 'DOMESTIC';

    const products = grouped[parentsName]?.[payMode]?.[marketType] || [];
    if (!products.length) return 'TICKET';

    const hasBundle = products.some(p => p.pax_type === 'BUNDLE');
    if (hasBundle) return 'BUNDLE';

    const hasAdult = products.some(p => p.pax_type === 'ADULT');
    const hasChild = products.some(p => p.pax_type === 'CHILD');
    if (hasAdult || hasChild) return 'ADULT_CHILD';

    return 'TICKET';
}

// ── Row management ──────────────────────────────────────────────────────────
function addActivityRow() {
    const idx = rowIdx++;
    const tpl = document.getElementById('activityRowTpl').innerHTML;
    const html = tpl.replaceAll('__IDX__', idx);
    const container = document.getElementById('activitiesContainer');

    document.getElementById('emptyMessage').style.display = 'none';

    const wrap = document.createElement('div');
    wrap.innerHTML = html;
    const row = wrap.firstElementChild;
    container.appendChild(row);

    initRow(row);
    refreshActivityOptions(row);
}

function initRow(row) {
    const actSelect    = row.querySelector('.activity-select');
    const bundleSelect = row.querySelector('.bundle-select');

    actSelect.addEventListener('change', () => onActivityChange(row));
    bundleSelect.addEventListener('change', () => onBundleVariantChange(row));

    row.querySelectorAll('.adult-qty, .child-qty, .bundle-qty, .ticket-qty').forEach(inp => {
        inp.addEventListener('input', () => { recalcRow(row); recalcTotal(); });
    });

    row.querySelector('.remove-activity').addEventListener('click', () => {
        row.remove();
        recalcTotal();
        if (!document.querySelector('.activity-row')) {
            document.getElementById('emptyMessage').style.display = '';
        }
    });
}

function refreshActivityOptions(row) {
    const actSelect  = row.querySelector('.activity-select');
    const currentVal = actSelect.value;
    const opts       = getActivityOptions();

    actSelect.innerHTML = '<option value="">— Pilih Aktivitas —</option>';
    opts.forEach(parent => {
        const opt = document.createElement('option');
        opt.value = parent;
        opt.textContent = parent;
        if (parent === currentVal) opt.selected = true;
        actSelect.appendChild(opt);
    });

    if (currentVal && !opts.includes(currentVal)) {
        actSelect.value = '';
        clearRowInputs(row);
    } else if (currentVal) {
        onActivityChange(row); // re-detect row type
    }
}

function refreshAllRows() {
    // Update market badge
    const market  = getMarketType();
    const payMode = getPayMode();
    document.getElementById('marketBadge').textContent  = market;
    document.getElementById('marketBadge').className    = 'badge small ' + (market === 'FOREIGN' ? 'bg-info text-dark' : 'bg-secondary');
    document.getElementById('payModeBadge').textContent = payMode || '—';

    document.querySelectorAll('.activity-row').forEach(row => refreshActivityOptions(row));
    recalcTotal();
}

// ── Activity change handler ─────────────────────────────────────────────────
function onActivityChange(row) {
    const parentsName = row.querySelector('.activity-select').value;
    if (!parentsName) { clearRowInputs(row); return; }

    const payMode    = getPayMode();
    const marketType = getMarketType();
    const rowType    = detectRowType(parentsName, payMode, marketType);

    row.querySelector('.row-type-input').value = rowType;

    // Hide all panels
    row.querySelector('.adult-child-inputs').classList.add('d-none');
    row.querySelector('.bundle-inputs').classList.add('d-none');
    row.querySelector('.ticket-inputs').classList.add('d-none');

    // Resolve products for this activity
    const products = grouped[parentsName]?.[payMode]?.[marketType]
                  || grouped[parentsName]?.[payMode]?.['FOREIGN']
                  || grouped[parentsName]?.[payMode]?.['DOMESTIC']
                  || [];

    if (rowType === 'ADULT_CHILD') {
        row.querySelector('.adult-child-inputs').classList.remove('d-none');

        const adultProd = products.find(p => p.pax_type === 'ADULT');
        const childProd = products.find(p => p.pax_type === 'CHILD');

        if (adultProd) {
            row.querySelector('.adult-product-id').value      = adultProd.id;
            row.querySelector('.adult-price').value           = adultProd.publish_rate;
            row.querySelector('.adult-nett').value            = adultProd.nett_price;
            row.querySelector('.adult-komisi').value          = adultProd.komisi;
            row.querySelector('.adult-price-display').textContent = '@ Rp ' + fmtNum(adultProd.publish_rate);
        }
        if (childProd) {
            row.querySelector('.child-product-id').value      = childProd.id;
            row.querySelector('.child-price').value           = childProd.publish_rate;
            row.querySelector('.child-nett').value            = childProd.nett_price;
            row.querySelector('.child-komisi').value          = childProd.komisi;
            row.querySelector('.child-price-display').textContent = '@ Rp ' + fmtNum(childProd.publish_rate);
        }
        row.querySelector('.child-section').classList.toggle('d-none', !childProd);

    } else if (rowType === 'BUNDLE') {
        row.querySelector('.bundle-inputs').classList.remove('d-none');

        const bundleSelect = row.querySelector('.bundle-select');
        const bundles = products.filter(p => p.pax_type === 'BUNDLE');

        bundleSelect.innerHTML = '<option value="">— Pilih Varian —</option>';
        bundles.forEach(bp => {
            const variantLabel = bp.product_name.includes(' - ')
                ? bp.product_name.split(' - ').slice(1).join(' - ')
                : bp.product_name;
            const opt = document.createElement('option');
            opt.value            = bp.id;
            opt.textContent      = variantLabel;
            opt.dataset.price    = bp.publish_rate;
            opt.dataset.nett     = bp.nett_price;
            opt.dataset.komisi   = bp.komisi;
            opt.dataset.adults   = bp.bundle_adult_count;
            opt.dataset.kids     = bp.bundle_child_count;
            bundleSelect.appendChild(opt);
        });

        if (bundles.length === 1) {
            bundleSelect.value = bundles[0].id;
            onBundleVariantChange(row);
        }

    } else {
        // TICKET or DOMESTIC
        row.querySelector('.ticket-inputs').classList.remove('d-none');

        const ticketProd = products.find(p => p.pax_type === 'TICKET') || products[0];
        if (ticketProd) {
            row.querySelector('.ticket-product-id').value      = ticketProd.id;
            row.querySelector('.ticket-price').value           = ticketProd.publish_rate;
            row.querySelector('.ticket-nett').value            = ticketProd.nett_price;
            row.querySelector('.ticket-komisi').value          = ticketProd.komisi;
            row.querySelector('.ticket-price-display').textContent =
                ticketProd.product_name + ' — Rp ' + fmtNum(ticketProd.publish_rate) + '/pax';
        }
    }

    recalcRow(row);
    recalcTotal();
}

function onBundleVariantChange(row) {
    const bundleSelect = row.querySelector('.bundle-select');
    const opt = bundleSelect.selectedOptions[0];

    if (!opt?.value) {
        row.querySelector('.bundle-price').value = 0;
        row.querySelector('.bundle-composition').textContent = '';
        recalcRow(row); recalcTotal();
        return;
    }

    row.querySelector('.bundle-price').value   = opt.dataset.price;
    row.querySelector('.bundle-nett').value    = opt.dataset.nett;
    row.querySelector('.bundle-komisi').value  = opt.dataset.komisi;

    const adults = parseInt(opt.dataset.adults) || 0;
    const kids   = parseInt(opt.dataset.kids) || 0;
    const parts  = [];
    if (adults) parts.push(adults + ' Adult');
    if (kids)   parts.push(kids + ' Child');
    row.querySelector('.bundle-composition').textContent =
        parts.length ? '(' + parts.join(' + ') + ')' : '';

    recalcRow(row);
    recalcTotal();
}

function clearRowInputs(row) {
    row.querySelector('.row-type-input').value = '';
    row.querySelector('.adult-child-inputs').classList.add('d-none');
    row.querySelector('.bundle-inputs').classList.add('d-none');
    row.querySelector('.ticket-inputs').classList.add('d-none');
    row.querySelector('.row-subtotal').textContent = 'Rp 0';
    recalcTotal();
}

// ── Recalculation ───────────────────────────────────────────────────────────
function recalcRow(row) {
    const rowType = row.querySelector('.row-type-input').value;
    let subtotal = 0;

    if (rowType === 'ADULT_CHILD') {
        const aq = parseFloat(row.querySelector('.adult-qty').value) || 0;
        const ap = parseFloat(row.querySelector('.adult-price').value) || 0;
        const cq = parseFloat(row.querySelector('.child-qty').value) || 0;
        const cp = parseFloat(row.querySelector('.child-price').value) || 0;
        subtotal = aq * ap + cq * cp;
    } else if (rowType === 'BUNDLE') {
        const bq = parseFloat(row.querySelector('.bundle-qty').value) || 0;
        const bp = parseFloat(row.querySelector('.bundle-price').value) || 0;
        subtotal = bq * bp;
    } else {
        const tq = parseFloat(row.querySelector('.ticket-qty').value) || 0;
        const tp = parseFloat(row.querySelector('.ticket-price').value) || 0;
        subtotal = tq * tp;
    }

    row.querySelector('.row-subtotal').textContent = 'Rp ' + fmtNum(subtotal);
    return subtotal;
}

function recalcTotal() {
    let gross = 0, nett = 0, comm = 0;
    let totalAdults = 0, totalKids = 0;

    document.querySelectorAll('.activity-row').forEach(row => {
        const rowType = row.querySelector('.row-type-input').value;

        if (rowType === 'ADULT_CHILD') {
            const aq = parseInt(row.querySelector('.adult-qty').value) || 0;
            const ap = parseFloat(row.querySelector('.adult-price').value) || 0;
            const an = parseFloat(row.querySelector('.adult-nett').value) || 0;
            const ak = parseFloat(row.querySelector('.adult-komisi').value) || 0;
            const cq = parseInt(row.querySelector('.child-qty').value) || 0;
            const cp = parseFloat(row.querySelector('.child-price').value) || 0;
            const cn = parseFloat(row.querySelector('.child-nett').value) || 0;
            const ck = parseFloat(row.querySelector('.child-komisi').value) || 0;
            gross += aq * ap + cq * cp;
            nett  += aq * an + cq * cn;
            comm  += aq * ak + cq * ck;
            totalAdults += aq;
            totalKids   += cq;

        } else if (rowType === 'BUNDLE') {
            const bq  = parseInt(row.querySelector('.bundle-qty').value) || 0;
            const bp  = parseFloat(row.querySelector('.bundle-price').value) || 0;
            const bn  = parseFloat(row.querySelector('.bundle-nett').value) || 0;
            const bk  = parseFloat(row.querySelector('.bundle-komisi').value) || 0;
            const opt = row.querySelector('.bundle-select').selectedOptions[0];
            gross += bq * bp;
            nett  += bq * bn;
            comm  += bq * bk;
            totalAdults += bq * (parseInt(opt?.dataset?.adults) || 0);
            totalKids   += bq * (parseInt(opt?.dataset?.kids) || 0);

        } else if (rowType === 'TICKET' || rowType === 'DOMESTIC') {
            const tq = parseInt(row.querySelector('.ticket-qty').value) || 0;
            const tp = parseFloat(row.querySelector('.ticket-price').value) || 0;
            const tn = parseFloat(row.querySelector('.ticket-nett').value) || 0;
            const tk = parseFloat(row.querySelector('.ticket-komisi').value) || 0;
            gross += tq * tp;
            nett  += tq * tn;
            comm  += tq * tk;
            totalAdults += tq;
        }
    });

    const babies = parseInt(document.getElementById('paxBabies').value) || 0;
    document.getElementById('summAdults').textContent = totalAdults;
    document.getElementById('summKids').textContent   = totalKids;
    document.getElementById('summBabies').textContent = babies;
    document.getElementById('summGross').textContent  = 'Rp ' + fmtNum(gross);
    document.getElementById('summNett').textContent   = 'Rp ' + fmtNum(nett);
    document.getElementById('summComm').textContent   = 'Rp ' + fmtNum(comm);
}

// ── Event listeners ─────────────────────────────────────────────────────────
document.getElementById('addActivityBtn').addEventListener('click', addActivityRow);

document.getElementById('paymentMethod').addEventListener('change', e => {
    document.getElementById('channelWrap').style.display =
        e.target.value === 'ON_THE_SPOT' ? '' : 'none';
    refreshAllRows();
});

// Country select (TomSelect wraps the select — listen to the underlying element)
document.querySelector('[name="guest_country"]').addEventListener('change', refreshAllRows);

document.getElementById('paxBabies').addEventListener('input', recalcTotal);

// ── TomSelect ────────────────────────────────────────────────────────────────
new TomSelect('#partnerSelect', { create: false, sortField: { field: 'text', direction: 'asc' } });
new TomSelect('#countrySelect', {
    create: false,
    allowEmptyOption: true,
    onChange: refreshAllRows,
});

// ── GPS Capture ───────────────────────────────────────────────────────────────
const dangerLat = {{ \App\Models\Setting::get('danger_zone_latitude', -8.7908) }};
const dangerLng = {{ \App\Models\Setting::get('danger_zone_longitude', 115.1553) }};
const dangerRad = {{ \App\Models\Setting::get('danger_zone_radius_meters', 500) }};

function captureGPS() {
    const display = document.getElementById('locationDisplay');
    display.innerHTML = '<span class="fst-italic">Mendeteksi lokasi...</span>';
    if (!navigator.geolocation) { display.textContent = 'Geolocation tidak didukung browser.'; return; }

    navigator.geolocation.getCurrentPosition(pos => {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;
        document.getElementById('lat').value = lat;
        document.getElementById('lng').value = lng;
        display.innerHTML = `${lat.toFixed(6)}, ${lng.toFixed(6)}<br><span class="fst-italic">Mencari alamat...</span>`;

        fetch(`{{ route('geocode.reverse') }}?lat=${lat}&lng=${lng}`)
            .then(r => r.json())
            .then(data => {
                const addr = data.address;
                const areaName = addr.village || addr.suburb || addr.town || addr.city || addr.county || 'Area tidak dikenal';
                document.getElementById('locationName').value = areaName;
                display.innerHTML = `<strong>${areaName}</strong><br><span class="text-secondary">${data.display_name || areaName}</span>`;
            }).catch(() => {
                display.innerHTML = `${lat.toFixed(6)}, ${lng.toFixed(6)}<br><span class="text-danger">Gagal memuat alamat.</span>`;
            });

        const dist = haversine(lat, lng, dangerLat, dangerLng);
        document.getElementById('dangerZoneWarning').classList.toggle('d-none', dist > dangerRad);
    }, err => {
        display.textContent = 'Lokasi tidak tersedia: ' + err.message;
    }, { enableHighAccuracy: true });
}

function haversine(lat1, lon1, lat2, lon2) {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2)**2 + Math.cos(lat1*Math.PI/180) * Math.cos(lat2*Math.PI/180) * Math.sin(dLon/2)**2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

window.addEventListener('load', () => {
    captureGPS();
    refreshAllRows();
});

// ── Submit guard ─────────────────────────────────────────────────────────────
document.getElementById('resForm').addEventListener('submit', () => {
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
});
</script>
@endpush
