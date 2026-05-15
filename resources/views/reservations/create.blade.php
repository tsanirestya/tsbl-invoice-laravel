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
                        <label class="form-label">Negara</label>
                        <select name="guest_country" id="countrySelect" class="form-select">
                            @include('partials._country_options', ['selected' => old('guest_country', 'Indonesia')])
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reservasi --}}
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
                        <select name="reservation_type" class="form-select" id="typeSelect" required>
                            <option value="INTERNAL" {{ old('reservation_type','INTERNAL') === 'INTERNAL' ? 'selected' : '' }}>Internal (Tim TSBL)</option>
                            <option value="PARTNER" {{ old('reservation_type') === 'PARTNER' ? 'selected' : '' }}>Partner</option>
                            <option value="SELF_SERVICE" {{ old('reservation_type') === 'SELF_SERVICE' ? 'selected' : '' }}>Self-Service</option>
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Partner</label>
                        @php
                            $typeMap = ['HOTEL' => 'HTL', 'TRAVEL' => 'TVL', 'TOURDESK' => 'TRD'];
                        @endphp
                        <select name="partner_id" id="partnerSelect">
                            <option value="">— Tanpa Partner —</option>
                            @foreach($partners as $p)
                                <option value="{{ $p->id }}" 
                                        data-type="{{ $typeMap[$p->partner_type] ?? '' }}"
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

        {{-- Items --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center fw-semibold">
                <span><i class="bi bi-box-seam me-2"></i>Produk</span>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn">
                    <i class="bi bi-plus-lg"></i> Tambah Produk
                </button>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0" id="itemsTable">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th style="width:80px">Qty</th>
                            <th style="width:130px">Harga/Pax</th>
                            <th class="text-end" style="width:120px">Subtotal</th>
                            <th style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <tr id="emptyRow">
                            <td colspan="5" class="text-center text-muted py-3">
                                Belum ada produk. Klik "+ Tambah Produk".
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-semibold">
                            <td colspan="3" class="text-end">Total</td>
                            <td class="text-end" id="grandTotal">Rp 0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Opsional">{{ old('notes') }}</textarea>
            </div>
        </div>
    </div>

    {{-- Right column --}}
    <div class="col-lg-5">
        {{-- Hidden GPS inputs —- data tetap terkirim ke server --}}
        <input type="hidden" name="latitude" id="lat">
        <input type="hidden" name="longitude" id="lng">
        <input type="hidden" name="location_name" id="locationName">

        {{-- Summary --}}
        <div class="card mb-3">
            <div class="card-header fw-semibold"><i class="bi bi-calculator me-2"></i>Ringkasan Komisi</div>
            <div class="card-body" id="commissionSummary">
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted">Gross Total</td><td class="text-end fw-semibold" id="summGross">Rp 0</td></tr>
                    <tr><td class="text-muted">Nett Total</td><td class="text-end" id="summNett">Rp 0</td></tr>
                    <tr class="table-warning"><td>Komisi</td><td class="text-end fw-semibold" id="summComm">Rp 0</td></tr>
                </table>
            </div>
        </div>

        {{-- Lokasi otomatis (read-only, tidak bisa diedit) --}}
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

<div class="d-flex gap-2 justify-content-end">
    <a href="{{ route('reservations.index') }}" class="btn btn-outline-secondary">Batal</a>
    <button type="submit" class="btn btn-primary" id="submitBtn">
        <i class="bi bi-check-lg me-1"></i> Simpan Reservasi
    </button>
</div>

</form>

{{-- Product template for JS --}}
<template id="itemRowTpl">
    <tr class="item-row">
        <td>
            <select name="items[__IDX__][product_id]" class="form-select form-select-sm product-select" required>
                <option value="">— Pilih Produk —</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}"
                            data-type="{{ $p->partner_type }}"
                            data-market="{{ $p->market_type }}"
                            data-payment="{{ $p->payment_mode }}"
                            data-sub-payment="{{ $p->sub_payment_mode }}"
                            data-publish="{{ $p->publish_rate }}"
                            data-nett="{{ $p->nett_price }}"
                            data-komisi="{{ $p->komisi }}">
                        {{ $p->product_name }}
                    </option>
                @endforeach
            </select>
            <div class="product-info mt-1 small text-muted"></div>
        </td>
        <td>
            <input type="number" name="items[__IDX__][qty]" value="1" min="1"
                   class="form-control form-control-sm qty-input" required>
        </td>
        <td>
            <input type="number" name="items[__IDX__][price_per_pax]" value="0" min="0" step="1"
                   class="form-control form-control-sm price-input" required>
        </td>
        <td class="text-end fw-semibold subtotal">Rp 0</td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger remove-row">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
<script>
// ── Searchable Partner ─────────────────────────────────────────────────────
const partnerTS = new TomSelect('#partnerSelect', {
    create: false,
    sortField: { field: 'text', direction: 'asc' },
    onChange: function() {
        filterProducts();
    }
});

// Searchable Country
new TomSelect('#countrySelect', {
    create: false,
    allowEmptyOption: true,
    placeholder: 'Cari negara...',
});

function filterProducts() {
    const partnerEl = document.getElementById('partnerSelect');
    const selectedPartnerOpt = partnerEl.options[partnerEl.selectedIndex];
    const partnerType = selectedPartnerOpt?.dataset?.type || '';
    
    const paymentMethod = document.getElementById('paymentMethod').value;
    let targetSubPayment = '';
    if (paymentMethod === 'TRANSFER_GROSS' || paymentMethod === 'ON_THE_SPOT') {
        targetSubPayment = 'GROSS';
    } else if (paymentMethod === 'TRANSFER_NETT') {
        targetSubPayment = 'NETT';
    }

    tbody.querySelectorAll('.item-row').forEach(row => {
        const sel = row.querySelector('.product-select');
        const currentVal = sel.value;
        let stillValid = false;

        Array.from(sel.options).forEach(opt => {
            if (opt.value === '') {
                opt.disabled = false;
                opt.hidden = false;
                opt.style.display = '';
                return;
            }

            const prodType = opt.dataset.type || '';
            const prodSubPayment = (opt.dataset.subPayment || '').toUpperCase();
            
            const typeMatch = !partnerType || prodType === partnerType;
            const paymentMatch = !targetSubPayment || prodSubPayment === targetSubPayment;
            const isMatch = typeMatch && paymentMatch;
            
            opt.disabled = !isMatch;
            opt.hidden = !isMatch;
            opt.style.display = isMatch ? '' : 'none';
            
            if (opt.value === currentVal && isMatch) stillValid = true;
        });

        if (currentVal && !stillValid) {
            sel.value = '';
            sel.dispatchEvent(new Event('change'));
        }
    });
}

// ── Items ──────────────────────────────────────────────────────────────────
let itemIdx = 0;
const tpl   = document.getElementById('itemRowTpl');
const tbody = document.getElementById('itemsBody');
const emptyRow = document.getElementById('emptyRow');

document.getElementById('addItemBtn').addEventListener('click', () => {
    const html = tpl.innerHTML.replaceAll('__IDX__', itemIdx++);
    emptyRow.style.display = 'none';
    const tr = document.createElement('tbody');
    tr.innerHTML = html;
    tbody.appendChild(tr.firstElementChild);
    bindRowEvents(tbody.lastElementChild);
    filterProducts(); // Apply current filter to new row
    recalc();
});

function bindRowEvents(row) {
    row.querySelector('.product-select').addEventListener('change', e => {
        const opt = e.target.selectedOptions[0];
        row.querySelector('.price-input').value = opt.dataset.publish || 0;
        
        // Show metadata: Foreign/Domestic & Payment Mode
        const info = row.querySelector('.product-info');
        if (opt.value) {
            const market = opt.dataset.market || '—';
            const payment = opt.dataset.payment || '—';
            info.innerHTML = `
                <span class="badge bg-light text-dark border fw-normal" title="Market Type">${market}</span>
                <span class="badge bg-light text-dark border fw-normal" title="Payment Mode">${payment}</span>
            `;
        } else {
            info.innerHTML = '';
        }
        recalc();
    });
    row.querySelector('.qty-input').addEventListener('input', recalc);
    row.querySelector('.price-input').addEventListener('input', recalc);
    row.querySelector('.remove-row').addEventListener('click', () => {
        row.remove();
        recalc();
        if (!tbody.querySelector('.item-row')) emptyRow.style.display = '';
    });
}

function recalc() {
    let gross = 0, nett = 0, comm = 0;
    tbody.querySelectorAll('.item-row').forEach(row => {
        const qty   = parseFloat(row.querySelector('.qty-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const sel   = row.querySelector('.product-select').selectedOptions[0];
        const nettP = parseFloat(sel?.dataset?.nett || 0);
        const komisi = parseFloat(sel?.dataset?.komisi || 0);
        const sub   = qty * price;
        gross += sub;
        nett  += qty * nettP;
        comm  += qty * komisi;
        row.querySelector('.subtotal').textContent = 'Rp ' + formatNum(sub);
    });
    document.getElementById('grandTotal').textContent = 'Rp ' + formatNum(gross);
    document.getElementById('summGross').textContent  = 'Rp ' + formatNum(gross);
    document.getElementById('summNett').textContent   = 'Rp ' + formatNum(nett);
    document.getElementById('summComm').textContent   = 'Rp ' + formatNum(comm);
}

function formatNum(n) {
    return Math.round(n).toLocaleString('id-ID');
}

// ── Payment channel visibility ────────────────────────────────────────────
document.getElementById('paymentMethod').addEventListener('change', e => {
    document.getElementById('channelWrap').style.display =
        e.target.value === 'ON_THE_SPOT' ? '' : 'none';
    filterProducts();
});

// ── GPS Capture (auto, read-only display) ─────────────────────────────────
const dangerLat = {{ \App\Models\Setting::get('danger_zone_latitude', -8.7908) }};
const dangerLng = {{ \App\Models\Setting::get('danger_zone_longitude', 115.1553) }};
const dangerRad = {{ \App\Models\Setting::get('danger_zone_radius_meters', 500) }};

function captureGPS() {
    const display = document.getElementById('locationDisplay');
    display.innerHTML = '<span class="fst-italic">Mendeteksi lokasi...</span>';

    if (!navigator.geolocation) {
        display.textContent = 'Geolocation tidak didukung browser.';
        return;
    }

    navigator.geolocation.getCurrentPosition(pos => {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;
        document.getElementById('lat').value = lat;
        document.getElementById('lng').value = lng;

        // Tampilkan koordinat dulu, lalu update dengan alamat
        display.innerHTML = `${lat.toFixed(6)}, ${lng.toFixed(6)}<br><span class="fst-italic">Mencari alamat...</span>`;

        // Reverse Geocoding via server proxy (agar User-Agent valid)
        fetch(`{{ route('geocode.reverse') }}?lat=${lat}&lng=${lng}`)
            .then(r => r.json())
            .then(data => {
                const addr = data.address;
                const areaName = addr.village || addr.suburb || addr.town || addr.city || addr.county || 'Area tidak dikenal';
                const fullAddr = data.display_name || areaName;
                document.getElementById('locationName').value = areaName;
                display.innerHTML = `<strong>${areaName}</strong><br><span class="text-secondary">${fullAddr}</span>`;
            }).catch(() => {
                display.innerHTML = `${lat.toFixed(6)}, ${lng.toFixed(6)}<br><span class="text-danger">Gagal memuat alamat.</span>`;
            });

        // Client-side danger zone check
        const dist = haversine(lat, lng, dangerLat, dangerLng);
        document.getElementById('dangerZoneWarning').classList.toggle('d-none', dist > dangerRad);
    }, err => {
        display.textContent = 'Lokasi tidak tersedia: ' + err.message;
    }, { enableHighAccuracy: true });
}

// Jalankan otomatis saat halaman siap
window.addEventListener('load', captureGPS);

function haversine(lat1, lon1, lat2, lon2) {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2)**2 + Math.cos(lat1*Math.PI/180) * Math.cos(lat2*Math.PI/180) * Math.sin(dLon/2)**2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

// ── Submit guard ──────────────────────────────────────────────────────────
// Initial filter
filterProducts();

document.getElementById('resForm').addEventListener('submit', () => {
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
});
</script>
@endpush
