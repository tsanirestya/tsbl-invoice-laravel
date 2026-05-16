@extends('layouts.app')

@section('title', 'Scan & Redeem')
@section('page-title', 'Scan & Redeem')

@push('styles')
<style>
#scanner-container {
    position: relative;
    width: 100%;
    max-width: 400px;
    margin: 0 auto;
    border-radius: 12px;
    overflow: hidden;
    background: #000;
    aspect-ratio: 1;
}
#reader { width: 100%; }
#result-panel { display: none; }
.pax-pill {
    display: inline-flex; align-items: center; gap: 4px;
    background: #f1f5f9; border-radius: 20px;
    padding: 3px 10px; font-size: .78rem; font-weight: 600;
}
</style>
@endpush

@section('content')
<div class="page-hdr d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="page-title">Scan & Redeem</h5>
        <p class="page-sub">Scan barcode Booking Pass atau input manual Reservation No.</p>
    </div>
    <a href="{{ route('admission.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Dashboard
    </a>
</div>

@if(session('redeemed_no'))
<div class="alert alert-success border-success shadow-sm mb-3 py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2" role="alert">
    <div>
        <i class="bi bi-check-circle-fill me-2 text-success fs-5"></i>
        <strong>Redeem berhasil!</strong>
        <span class="ms-2 text-muted small">Salin no. reservasi untuk diisi di POS:</span>
        <span class="ms-2 fw-bold fs-5 font-monospace text-dark" id="redeemed-no-display">{{ session('redeemed_no') }}</span>
    </div>
    <button type="button" id="btn-copy-res"
        class="btn btn-success btn-sm px-4 fw-semibold"
        onclick="copyReservationNo('{{ session('redeemed_no') }}')">
        <i class="bi bi-clipboard me-1"></i> Copy
    </button>
</div>
@endif

<div class="row g-3">
    {{-- Scanner / Input Panel --}}
    <div class="col-12 col-md-5">
        <div class="card card-clean mb-3">
            <div class="card-header">
                <i class="bi bi-upc-scan me-2 text-primary"></i>Kamera Barcode
            </div>
            <div class="card-body text-center">
                <div id="scanner-container" class="mb-3">
                    <div id="reader"></div>
                </div>
                <div id="camera-status" class="text-muted small mb-2">Kamera belum aktif.</div>
                <button id="btn-start-camera" class="btn btn-primary btn-sm" onclick="startCamera()">
                    <i class="bi bi-camera-fill me-1"></i> Buka Kamera
                </button>
                <button id="btn-stop-camera" class="btn btn-outline-secondary btn-sm d-none" onclick="stopCamera()">
                    <i class="bi bi-camera-video-off me-1"></i> Tutup Kamera
                </button>
            </div>
        </div>

        <div class="card card-clean">
            <div class="card-header">
                <i class="bi bi-keyboard me-2 text-secondary"></i>Input Manual
            </div>
            <div class="card-body">
                <div class="input-group">
                    <input type="text" id="manual-input" class="form-control text-uppercase fw-semibold"
                           placeholder="Contoh: RES-20260516-0001"
                           style="letter-spacing:1px; font-size:.85rem;">
                    <button class="btn btn-primary" onclick="doLookup(document.getElementById('manual-input').value)">
                        <i class="bi bi-search"></i> Cari
                    </button>
                </div>
                <div class="form-text mt-1">Enter juga berfungsi untuk mencari.</div>
            </div>
        </div>
    </div>

    {{-- Result Panel --}}
    <div class="col-12 col-md-7">
        <div id="result-panel">
            <div class="card card-clean">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-person-vcard me-2"></i>Detail Reservasi</span>
                    <span id="result-status-badge"></span>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fw-bold fs-6" id="result-guest-name"></span>
                                <small class="text-muted" id="result-country"></small>
                            </div>
                            <div class="text-muted small">
                                <i class="bi bi-calendar3 me-1"></i><span id="result-visit-date"></span>
                                &nbsp;·&nbsp;
                                <span id="result-type"></span>
                            </div>
                        </div>
                        <div class="col-12">
                            <span class="pax-pill"><i class="bi bi-person-fill"></i><span id="result-pax-adults"></span> Dewasa</span>
                            <span class="pax-pill ms-1" id="pax-kids-pill" style="display:none"><i class="bi bi-person-fill"></i><span id="result-pax-kids"></span> Anak</span>
                            <span class="pax-pill ms-1" id="pax-babies-pill" style="display:none"><i class="bi bi-person-fill"></i><span id="result-pax-babies"></span> Bayi</span>
                        </div>
                    </div>

                    {{-- Items --}}
                    <div class="mb-3">
                        <div class="sect-label mb-1">Item Reservasi</div>
                        <div id="result-items" class="border rounded" style="font-size:.82rem;"></div>
                    </div>

                    {{-- Date warning --}}
                    <div id="date-warning" class="alert alert-warning py-2 px-3 small d-none">
                        <i class="bi bi-calendar-x me-1"></i>
                        <span id="date-warning-text"></span>
                    </div>

                    {{-- Already redeemed info --}}
                    <div id="redeemed-info" class="alert alert-info py-2 px-3 small d-none">
                        <i class="bi bi-check-circle me-1"></i>
                        Sudah di-redeem pada <strong id="redeemed-at"></strong> oleh <strong id="redeemed-by"></strong>.
                    </div>

                    {{-- Other status warning --}}
                    <div id="status-warning" class="alert alert-secondary py-2 px-3 small d-none">
                        <i class="bi bi-exclamation-circle me-1"></i>
                        Reservasi ini berstatus <strong id="status-warning-value"></strong> — tidak bisa di-redeem.
                    </div>

                    {{-- Redeem form (only for CONFIRMED + within date range) --}}
                    <div id="redeem-form-area" class="d-none">
                        <form method="POST" action="{{ route('admission.redeem') }}">
                            @csrf
                            <input type="hidden" name="reservation_no" id="form-reservation-no">

                            <div class="mb-3">
                                <div class="sect-label mb-2">Status Transaksi</div>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="transaction_match"
                                               id="match-yes" value="MATCH" required onchange="toggleNotes()">
                                        <label class="form-check-label text-success fw-semibold" for="match-yes">
                                            <i class="bi bi-check-circle-fill me-1"></i> Sesuai Reservasi (MATCH)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="transaction_match"
                                               id="match-no" value="MISMATCH" onchange="toggleNotes()">
                                        <label class="form-check-label text-warning fw-semibold" for="match-no">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Tidak Sesuai (MISMATCH)
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div id="notes-area" class="mb-3 d-none">
                                <label class="form-label fw-semibold small">Catatan Perubahan <span class="text-danger">*</span></label>
                                <textarea name="transaction_notes" class="form-control" rows="3"
                                          placeholder="Contoh: Booking 3 dewasa, datang 2 dewasa + 1 anak. Produk ganti dari Bundle A ke Bundle B."></textarea>
                                <div class="form-text">Wajib diisi untuk MISMATCH — digunakan saat rekonsiliasi DSI.</div>
                            </div>

                            <button type="submit" class="btn btn-success w-100 py-2 fw-bold">
                                <i class="bi bi-check2-circle me-2"></i> REDEEM
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Empty state --}}
        <div id="empty-state" class="text-center text-muted py-5">
            <i class="bi bi-upc-scan d-block mb-2 opacity-25" style="font-size:3rem"></i>
            <p class="mb-0">Scan barcode atau masukkan Reservation No untuk memulai.</p>
        </div>

        {{-- Not found state --}}
        <div id="not-found-state" class="d-none">
            <div class="alert alert-danger d-flex align-items-center gap-2">
                <i class="bi bi-x-circle-fill fs-5"></i>
                <span>Reservasi tidak ditemukan. Periksa kembali nomor reservasi.</span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let html5QrCode = null;

function startCamera() {
    html5QrCode = new Html5Qrcode("reader");
    const config = { fps: 10, qrbox: { width: 250, height: 150 } };

    html5QrCode.start(
        { facingMode: "environment" },
        config,
        (decodedText) => {
            // Extract reservation_no from decoded barcode
            const match = decodedText.match(/RES-\d{8}-\d{4}/i);
            const resNo = match ? match[0] : decodedText.trim();
            doLookup(resNo);
        },
        (err) => {}
    ).then(() => {
        document.getElementById('camera-status').textContent = 'Kamera aktif — arahkan ke barcode Booking Pass.';
        document.getElementById('btn-start-camera').classList.add('d-none');
        document.getElementById('btn-stop-camera').classList.remove('d-none');
    }).catch((err) => {
        document.getElementById('camera-status').textContent = 'Kamera tidak tersedia: ' + err;
    });
}

function stopCamera() {
    if (html5QrCode) {
        html5QrCode.stop().then(() => {
            document.getElementById('camera-status').textContent = 'Kamera dimatikan.';
            document.getElementById('btn-start-camera').classList.remove('d-none');
            document.getElementById('btn-stop-camera').classList.add('d-none');
        });
    }
}

function doLookup(resNo) {
    if (!resNo || !resNo.trim()) return;

    fetch('{{ route('admission.lookup') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ reservation_no: resNo.trim().toUpperCase() }),
    })
    .then(r => r.json())
    .then(data => renderResult(data))
    .catch(() => alert('Gagal menghubungi server. Coba lagi.'));
}

function renderResult(data) {
    document.getElementById('empty-state').classList.add('d-none');
    document.getElementById('not-found-state').classList.add('d-none');
    document.getElementById('result-panel').style.display = 'none';
    document.getElementById('redeemed-info').classList.add('d-none');
    document.getElementById('status-warning').classList.add('d-none');
    document.getElementById('date-warning').classList.add('d-none');
    document.getElementById('redeem-form-area').classList.add('d-none');

    if (!data.found) {
        document.getElementById('not-found-state').classList.remove('d-none');
        return;
    }

    document.getElementById('result-panel').style.display = 'block';
    document.getElementById('result-guest-name').textContent = data.guest_name;
    document.getElementById('result-country').textContent = data.guest_country || '';
    document.getElementById('result-visit-date').textContent = data.visit_date;
    document.getElementById('result-type').textContent = data.reservation_type;
    document.getElementById('result-pax-adults').textContent = data.pax_adults;

    // PAX kids/babies
    const kidsEl = document.getElementById('pax-kids-pill');
    const babiesEl = document.getElementById('pax-babies-pill');
    if (data.pax_kids > 0) {
        document.getElementById('result-pax-kids').textContent = data.pax_kids;
        kidsEl.style.display = 'inline-flex';
    } else { kidsEl.style.display = 'none'; }
    if (data.pax_babies > 0) {
        document.getElementById('result-pax-babies').textContent = data.pax_babies;
        babiesEl.style.display = 'inline-flex';
    } else { babiesEl.style.display = 'none'; }

    // Status badge
    const badgeColor = {'CONFIRMED':'success','PENDING':'warning','REDEEMED':'info','CANCELLED':'secondary','NO_SHOW':'danger','COMPLETED':'primary'};
    const color = badgeColor[data.status] || 'secondary';
    document.getElementById('result-status-badge').innerHTML =
        `<span class="badge bg-${color}">${data.status}</span>`;

    // Items table
    let itemsHtml = '<table class="table table-sm mb-0"><thead><tr><th>Produk</th><th class="text-center">Qty</th><th class="text-end">Harga</th></tr></thead><tbody>';
    data.items.forEach(item => {
        itemsHtml += `<tr><td>${item.product_name}</td><td class="text-center">${item.qty}</td><td class="text-end">${new Intl.NumberFormat('id-ID').format(item.amount)}</td></tr>`;
    });
    itemsHtml += '</tbody></table>';
    document.getElementById('result-items').innerHTML = itemsHtml;

    // Status-specific UI
    if (data.status === 'CONFIRMED') {
        if (!data.within_date_range) {
            const msg = data.tolerance_days > 0
                ? `Visit date ${data.visit_date} — redeem hanya bisa H-${data.tolerance_days} sampai H+${data.tolerance_days}. Hari ini di luar range.`
                : `Visit date adalah ${data.visit_date}. Hari ini tidak sesuai — tidak bisa di-redeem.`;
            document.getElementById('date-warning-text').textContent = msg;
            document.getElementById('date-warning').classList.remove('d-none');
        } else {
            document.getElementById('form-reservation-no').value = data.reservation_no;
            document.getElementById('redeem-form-area').classList.remove('d-none');
            // Reset form
            document.getElementById('match-yes').checked = false;
            document.getElementById('match-no').checked = false;
            document.getElementById('notes-area').classList.add('d-none');
        }
    } else if (data.status === 'REDEEMED') {
        document.getElementById('redeemed-at').textContent = data.redeemed_at;
        document.getElementById('redeemed-by').textContent = data.redeemed_by_name || '-';
        document.getElementById('redeemed-info').classList.remove('d-none');
    } else {
        document.getElementById('status-warning-value').textContent = data.status;
        document.getElementById('status-warning').classList.remove('d-none');
    }
}

function toggleNotes() {
    const isMismatch = document.getElementById('match-no').checked;
    const notesArea = document.getElementById('notes-area');
    notesArea.classList.toggle('d-none', !isMismatch);
    notesArea.querySelector('textarea').required = isMismatch;
}

function copyReservationNo(resNo) {
    navigator.clipboard.writeText(resNo).then(() => {
        const btn = document.getElementById('btn-copy-res');
        btn.innerHTML = '<i class="bi bi-clipboard-check me-1"></i> Copied!';
        btn.classList.replace('btn-success', 'btn-outline-success');
        setTimeout(() => {
            btn.innerHTML = '<i class="bi bi-clipboard me-1"></i> Copy';
            btn.classList.replace('btn-outline-success', 'btn-success');
        }, 2000);
    }).catch(() => {
        // Fallback for browsers without clipboard API
        const el = document.createElement('textarea');
        el.value = resNo;
        el.style.position = 'fixed';
        el.style.opacity = '0';
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        const btn = document.getElementById('btn-copy-res');
        btn.innerHTML = '<i class="bi bi-clipboard-check me-1"></i> Copied!';
        setTimeout(() => { btn.innerHTML = '<i class="bi bi-clipboard me-1"></i> Copy'; }, 2000);
    });
}

// Manual input — Enter key
document.getElementById('manual-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        doLookup(this.value);
    }
});
</script>
@endpush
