<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reservasi — {{ $partner->nama_partner }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4fb; font-family: 'Segoe UI', sans-serif; }
        .brand-bar { background: #0f1729; color: #fff; padding: 12px 16px; }
        .brand-bar .badge-partner { background: rgba(59,130,246,.2); color: #93c5fd; border-radius: 6px; padding: 2px 8px; font-size: .7rem; }
        .card { border-radius: 12px; border: none; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
        .form-control, .form-select { min-height: 44px; border-radius: 8px; }
        .btn-primary { min-height: 44px; border-radius: 8px; }
        .item-row td { vertical-align: middle; }
        @media (max-width: 576px) { .container { padding: 0 8px; } }
    </style>
</head>
<body>
<div class="brand-bar mb-4">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-calendar-check fs-5"></i>
        <div>
            <div class="fw-bold">Form Reservasi</div>
            <div class="badge-partner">{{ $partner->nama_partner }}</div>
        </div>
    </div>
</div>

<div class="container" style="max-width:640px">
    @if(session('error'))
        <div class="alert alert-danger mb-3">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('partner.reserve.store', $token) }}" id="partnerForm">
    @csrf
    <input type="hidden" name="device_fingerprint" id="dfp">

    {{-- Tamu --}}
    <div class="card mb-3">
        <div class="card-header fw-semibold"><i class="bi bi-person me-2"></i>Data Tamu</div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Nama Tamu <span class="text-danger">*</span></label>
                <input type="text" name="guest_name" value="{{ old('guest_name') }}"
                       class="form-control" required placeholder="Nama lengkap tamu">
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label">Negara</label>
                    <select name="guest_country" id="countrySelect">
                        @include('partials._country_options', ['selected' => old('guest_country', 'Indonesia')])
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label">Tanggal Kunjungan <span class="text-danger">*</span></label>
                    <input type="date" name="visit_date" value="{{ old('visit_date') }}"
                           class="form-control" required min="{{ now()->format('Y-m-d') }}">
                </div>
            </div>
        </div>
    </div>

    {{-- Asal tamu --}}
    <div class="card mb-3">
        <div class="card-header fw-semibold"><i class="bi bi-people me-2"></i>Asal Tamu</div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Asal Tamu</label>
                <select name="customer_origin" class="form-select">
                    <option value="">— Pilih —</option>
                    <option value="HOTEL" {{ old('customer_origin') === 'HOTEL' ? 'selected' : '' }}>Hotel</option>
                    <option value="TRAVEL_AGENT" {{ old('customer_origin') === 'TRAVEL_AGENT' ? 'selected' : '' }}>Travel Agent</option>
                    <option value="WALK_IN" {{ old('customer_origin') === 'WALK_IN' ? 'selected' : '' }}>Walk In (tanpa referral)</option>
                    <option value="ONLINE_AD" {{ old('customer_origin') === 'ONLINE_AD' ? 'selected' : '' }}>Online Ad</option>
                    <option value="OTHER" {{ old('customer_origin') === 'OTHER' ? 'selected' : '' }}>Lainnya</option>
                </select>
            </div>
            <div>
                <label class="form-label">Nama Hotel / Agent (jika ada)</label>
                <input type="text" name="customer_origin_detail" value="{{ old('customer_origin_detail') }}"
                       class="form-control" placeholder="Opsional">
            </div>
        </div>
    </div>

    {{-- Pembayaran --}}
    <div class="card mb-3">
        <div class="card-header fw-semibold"><i class="bi bi-credit-card me-2"></i>Pembayaran</div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                <select name="payment_method" class="form-select" required id="pm">
                    <option value="">— Pilih —</option>
                    <option value="TRANSFER_GROSS">Transfer Gross (terima komisi)</option>
                    <option value="TRANSFER_NETT">Transfer Nett (sudah dipotong komisi)</option>
                    <option value="ON_THE_SPOT">On The Spot (tamu bayar langsung)</option>
                </select>
            </div>
            <div id="channelWrap" style="display:none">
                <label class="form-label">Channel Pembayaran</label>
                <select name="payment_channel" class="form-select">
                    <option value="CASH">Cash</option>
                    <option value="DEBIT">Debit</option>
                    <option value="CREDIT">Credit Card</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Produk --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center fw-semibold">
            <span><i class="bi bi-box-seam me-2"></i>Produk</span>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn">
                <i class="bi bi-plus-lg"></i> Tambah
            </button>
        </div>
        <div class="card-body p-0">
            <table class="table mb-0" id="itemsTable">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th style="width:70px">Qty</th>
                        <th class="text-end" style="width:110px">Subtotal</th>
                        <th style="width:36px"></th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <tr id="emptyRow">
                        <td colspan="4" class="text-center text-muted py-3">
                            Belum ada produk. Klik "+ Tambah".
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="table-light fw-semibold">
                        <td colspan="2" class="text-end">Total</td>
                        <td class="text-end" id="grandTotal">Rp 0</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- GPS --}}
    <div class="card mb-3">
        <div class="card-header fw-semibold"><i class="bi bi-geo-alt me-2"></i>Lokasi</div>
        <div class="card-body">
            <p class="small text-muted mb-2">Izinkan akses lokasi agar reservasi dapat diproses dengan lebih cepat.</p>
            <button type="button" class="btn btn-outline-secondary btn-sm mb-2" id="captureGps">
                <i class="bi bi-crosshair me-1"></i> Izinkan Akses Lokasi
            </button>
            <div id="gpsStatus" class="small"></div>
            <input type="hidden" name="latitude" id="lat">
            <input type="hidden" name="longitude" id="lng">
        </div>
    </div>

    {{-- Catatan --}}
    <div class="card mb-4">
        <div class="card-body">
            <label class="form-label">Catatan Tambahan</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Opsional">{{ old('notes') }}</textarea>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 mb-4" id="submitBtn">
        <i class="bi bi-check-lg me-1"></i> Buat Reservasi
    </button>
    </form>

    <div class="text-center mb-4">
        <a href="{{ route('partner.reserve.history', $token) }}" class="text-decoration-none small text-muted">
            <i class="bi bi-clock-history me-1"></i> Riwayat Reservasi Saya
        </a>
    </div>
</div>

{{-- Item template --}}
<template id="itemRowTpl">
    <tr class="item-row">
        <td>
            <select name="items[__IDX__][product_id]" class="form-select form-select-sm product-select" required>
                <option value="">— Pilih Produk —</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}"
                            data-publish="{{ $p->publish_rate }}"
                            data-nett="{{ $p->nett_price }}"
                            data-komisi="{{ $p->komisi }}">
                        {{ $p->product_name }} — Rp {{ number_format($p->publish_rate, 0, ',', '.') }}
                    </option>
                @endforeach
            </select>
            <input type="hidden" name="items[__IDX__][price_per_pax]" class="price-hidden" value="0">
        </td>
        <td>
            <input type="number" name="items[__IDX__][qty]" value="1" min="1"
                   class="form-control form-control-sm qty-input" required>
        </td>
        <td class="text-end fw-semibold subtotal">Rp 0</td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger remove-row">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
new TomSelect('#countrySelect', {
    create: false,
    allowEmptyOption: true,
    placeholder: 'Cari negara...',
});

// Device fingerprint
const dfp = [navigator.userAgent, screen.width, screen.height, Intl.DateTimeFormat().resolvedOptions().timeZone].join('|');
document.getElementById('dfp').value = btoa(dfp).substring(0, 64);

// Payment method -> channel
document.getElementById('pm').addEventListener('change', e => {
    document.getElementById('channelWrap').style.display = e.target.value === 'ON_THE_SPOT' ? '' : 'none';
});

// Items
let idx = 0;
const tpl = document.getElementById('itemRowTpl');
const tbody = document.getElementById('itemsBody');
const emptyRow = document.getElementById('emptyRow');

document.getElementById('addItemBtn').addEventListener('click', () => {
    emptyRow.style.display = 'none';
    const html = tpl.innerHTML.replaceAll('__IDX__', idx++);
    const wrap = document.createElement('tbody');
    wrap.innerHTML = html;
    const row = wrap.firstElementChild;
    tbody.appendChild(row);
    bindRow(row);
    recalc();
});

function bindRow(row) {
    row.querySelector('.product-select').addEventListener('change', e => {
        const opt = e.target.selectedOptions[0];
        row.querySelector('.price-hidden').value = opt.dataset.publish || 0;
        recalc();
    });
    row.querySelector('.qty-input').addEventListener('input', recalc);
    row.querySelector('.remove-row').addEventListener('click', () => {
        row.remove();
        if (!tbody.querySelector('.item-row')) emptyRow.style.display = '';
        recalc();
    });
}

function recalc() {
    let total = 0;
    tbody.querySelectorAll('.item-row').forEach(row => {
        const qty   = parseFloat(row.querySelector('.qty-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-hidden').value) || 0;
        const sub   = qty * price;
        total += sub;
        row.querySelector('.subtotal').textContent = 'Rp ' + Math.round(sub).toLocaleString('id-ID');
    });
    document.getElementById('grandTotal').textContent = 'Rp ' + Math.round(total).toLocaleString('id-ID');
}

// GPS
const dangerLat = {{ \App\Models\Setting::get('danger_zone_latitude', -8.7908) }};
const dangerLng = {{ \App\Models\Setting::get('danger_zone_longitude', 115.1553) }};
const dangerRad = {{ \App\Models\Setting::get('danger_zone_radius_meters', 500) }};

document.getElementById('captureGps').addEventListener('click', () => {
    const status = document.getElementById('gpsStatus');
    status.textContent = 'Mendeteksi lokasi...';
    navigator.geolocation.getCurrentPosition(pos => {
        document.getElementById('lat').value = pos.coords.latitude;
        document.getElementById('lng').value = pos.coords.longitude;
        status.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Lokasi berhasil ditangkap.</span>';
    }, err => {
        status.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Tidak bisa mendapatkan lokasi: ' + err.message + '</span>';
    }, { enableHighAccuracy: true, timeout: 10000 });
});

// Submit guard
document.getElementById('partnerForm').addEventListener('submit', () => {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';
});
</script>
</body>
</html>
