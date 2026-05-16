@extends('layouts.app')

@section('title', 'QR Self-Service')
@section('page-title', 'QR Self-Service')

@push('styles')
<style>
.qr-preview-wrapper {
    background: #f1f5f9;
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    justify-content: center;
}

/* A5 = 148mm × 210mm → 96dpi ≈ 559 × 794px */
.a5-flyer {
    width: 559px;
    height: 794px;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 6px 32px rgba(0,0,0,.14);
    font-family: 'Segoe UI', system-ui, sans-serif;
    display: flex;
    flex-direction: column;
}

/* ── Header ─────────────────────────────────────────── */
.flyer-header {
    background: linear-gradient(135deg, #0c2340 0%, #1a4480 60%, #0e5fa8 100%);
    padding: 20px 28px 16px;
    text-align: center;
    flex-shrink: 0;
    position: relative;
    overflow: hidden;
}
.flyer-header::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 130px; height: 130px;
    background: rgba(255,255,255,.05);
    border-radius: 50%;
}
.flyer-header::after {
    content: '';
    position: absolute;
    bottom: -20px; left: -20px;
    width: 80px; height: 80px;
    background: rgba(255,255,255,.04);
    border-radius: 50%;
}
.flyer-logo {
    max-height: 48px;
    max-width: 180px;
    object-fit: contain;
    display: block;
    margin: 0 auto 12px;
    position: relative; z-index: 1;
}
.flyer-logo-text {
    color: #fff;
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: 1px;
    margin-bottom: 12px;
    position: relative; z-index: 1;
}
.flyer-divider {
    border: none;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.4), transparent);
    margin: 0 0 12px;
    position: relative; z-index: 1;
}
.flyer-title {
    color: #fff;
    font-size: 1.3rem;
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 5px;
    position: relative; z-index: 1;
}
.flyer-subtitle {
    color: #fbbf24;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    position: relative; z-index: 1;
}

/* ── QR + CTA ───────────────────────────────────────── */
.flyer-qr-section {
    padding: 16px 28px 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.qr-cta-en {
    font-size: 1rem;
    font-weight: 800;
    color: #0c2340;
    line-height: 1.2;
    text-align: center;
}
.qr-cta-id {
    font-size: .8rem;
    color: #475569;
    font-weight: 500;
    line-height: 1.3;
    text-align: center;
}
.qr-frame {
    background: #fff;
    border: 3px solid #0c2340;
    border-radius: 12px;
    padding: 12px;
    flex-shrink: 0;
    position: relative;
    box-shadow: 0 4px 16px rgba(12,35,64,.15);
}
.qr-frame::before, .qr-frame::after {
    content: '';
    position: absolute;
    width: 18px; height: 18px;
    border-color: #fbbf24;
    border-style: solid;
}
.qr-frame::before { top:-3px; left:-3px; border-width:3.5px 0 0 3.5px; border-radius:4px 0 0 0; }
.qr-frame::after  { bottom:-3px; right:-3px; border-width:0 3.5px 3.5px 0; border-radius:0 0 4px 0; }
.qr-valid-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #dcfce7;
    color: #166534;
    font-size: .72rem;
    font-weight: 700;
    padding: 4px 14px;
    border-radius: 20px;
}

/* ── Steps ──────────────────────────────────────────── */
.flyer-steps {
    padding: 0 28px 16px;
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}
.step-item {
    flex: 1;
    text-align: center;
    padding: 10px 6px;
    background: #f8fafc;
    border-radius: 8px;
    border-top: 3px solid #1a4480;
}
.step-num {
    width: 22px; height: 22px;
    background: #0c2340;
    color: #fff;
    border-radius: 50%;
    font-size: .65rem;
    font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 5px;
}
.step-en { font-size: .68rem; font-weight: 700; color: #1e293b; line-height: 1.3; }
.step-id { font-size: .62rem; color: #64748b; line-height: 1.3; margin-top: 2px; }

/* ── Instructions (flex-grow fills remaining space) ─── */
.flyer-instructions {
    margin: 0 28px 0;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}
.instr-header {
    background: #0c2340;
    color: #fff;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    padding: 7px 16px;
    text-align: center;
    flex-shrink: 0;
}
.instr-body {
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    justify-content: space-around;
    flex-grow: 1;
    gap: 0;
}
.instr-row {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
}
.instr-row:last-child { border-bottom: none; }
.instr-icon { font-size: .9rem; flex-shrink: 0; line-height: 1.6; }
.instr-en { font-size: .72rem; font-weight: 700; color: #1e293b; line-height: 1.4; }
.instr-id { font-size: .67rem; color: #64748b; line-height: 1.4; font-weight: 400; margin-top: 2px; }

/* ── Footer ─────────────────────────────────────────── */
.flyer-footer {
    background: linear-gradient(135deg, #0c2340, #1a4480);
    padding: 10px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
    margin-top: 14px;
}
.footer-brand { color: rgba(255,255,255,.9); font-size: .68rem; font-weight: 700; letter-spacing: .3px; }
.footer-date  { color: #fbbf24; font-size: .65rem; font-weight: 600; }

/* ── Print ──────────────────────────────────────────── */
@media print {
    @page { size: A5 portrait; margin: 0; }
    body * { visibility: hidden !important; }
    #print-flyer-target, #print-flyer-target * { visibility: visible !important; }
    #print-flyer-target {
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .a5-flyer {
        width: 148mm;
        height: 210mm;
        border-radius: 0;
        box-shadow: none;
    }
    .screen-only { display: none !important; }
}
</style>
@endpush

@section('content')
<div class="page-hdr d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="page-title">QR Self-Service — Print A5</h5>
        <p class="page-sub">Flyer QR harian untuk gate / front desk</p>
    </div>
    <a href="{{ route('admission.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Dashboard
    </a>
</div>

@if($todayQr && $qrUrl)

<div class="d-flex gap-2 align-items-center mb-3 screen-only">
    <button onclick="window.print()" class="btn btn-primary">
        <i class="bi bi-printer-fill me-1"></i> Print A5
    </button>
    <button onclick="copyQrUrl()" id="btn-copy-qr" class="btn btn-outline-secondary">
        <i class="bi bi-clipboard me-1"></i> Copy Link
    </button>
    <span class="text-muted small ms-2">
        <i class="bi bi-exclamation-circle me-1"></i>
        Aktifkan <strong>Background graphics</strong> agar warna muncul saat print
    </span>
</div>

<div id="print-flyer-target" class="qr-preview-wrapper">
    <div class="a5-flyer">

        {{-- Header --}}
        <div class="flyer-header">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="Trans Studio Bali" class="flyer-logo">
            @else
                <div class="flyer-logo-text">TRANS STUDIO BALI</div>
            @endif
            <hr class="flyer-divider">
            <div class="flyer-title">QR Self-Service Redeem</div>
            <div class="flyer-subtitle">Room Key &amp; Voucher · Partner Guests</div>
        </div>

        {{-- QR + CTA --}}
        <div class="flyer-qr-section">
            <div class="qr-cta-en">Scan to Register Your Visit</div>
            <div class="qr-cta-id">Scan untuk mendaftarkan kunjungan Anda ke Trans Studio Bali</div>
            <div class="qr-frame">
                <div id="qr-canvas"></div>
            </div>
            <div class="qr-valid-badge">✓ Valid {{ now()->format('d M Y') }}</div>
        </div>

        {{-- Steps --}}
        <div class="flyer-steps">
            <div class="step-item">
                <div class="step-num">1</div>
                <div class="step-en">Scan QR Code</div>
                <div class="step-id">Scan QR ini</div>
            </div>
            <div class="step-item">
                <div class="step-num">2</div>
                <div class="step-en">Fill Your Details</div>
                <div class="step-id">Isi data kunjungan</div>
            </div>
            <div class="step-item">
                <div class="step-num">3</div>
                <div class="step-en">Photo Room Key</div>
                <div class="step-id">Foto Room Key</div>
            </div>
            <div class="step-item">
                <div class="step-num">4</div>
                <div class="step-en">Show Booking Pass</div>
                <div class="step-id">Tunjukkan Pass</div>
            </div>
        </div>

        {{-- Instructions --}}
        <div class="flyer-instructions">
            <div class="instr-header">Important / Perhatian</div>
            <div class="instr-body">
                <div class="instr-row">
                    <span class="instr-icon">🏨</span>
                    <div>
                        <div class="instr-en">Exclusively for guests of Trans Studio Bali partners.</div>
                        <div class="instr-id">Khusus tamu yang bermitra dengan Trans Studio Bali.</div>
                    </div>
                </div>
                <div class="instr-row">
                    <span class="instr-icon">🔑</span>
                    <div>
                        <div class="instr-en">Have your Room Key or Hotel Voucher ready before scanning.</div>
                        <div class="instr-id">Siapkan Room Key atau Voucher Hotel sebelum scan.</div>
                    </div>
                </div>
                <div class="instr-row">
                    <span class="instr-icon">📸</span>
                    <div>
                        <div class="instr-en">A photo of your Room Key is required to complete registration.</div>
                        <div class="instr-id">Foto Room Key wajib diunggah saat mengisi formulir.</div>
                    </div>
                </div>
                <div class="instr-row">
                    <span class="instr-icon">🎟️</span>
                    <div>
                        <div class="instr-en">Show your Booking Pass to our staff at the ticketing area.</div>
                        <div class="instr-id">Tunjukkan Booking Pass kepada petugas di area tiket.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flyer-footer">
            <div class="footer-brand">© Trans Studio Bali</div>
            <div class="footer-date">{{ now()->format('d M Y') }} · QR valid today only</div>
        </div>

    </div>
</div>

<div class="alert alert-light border small mt-3 screen-only">
    <strong>Tips print:</strong> Kertas <strong>A5</strong> · Orientasi <strong>Portrait</strong> · Margin <strong>None</strong> · Centang <strong>Background graphics</strong>
</div>

@else
<div class="row justify-content-center">
    <div class="col-12 col-md-6">
        <div class="card card-clean text-center">
            <div class="card-body py-5">
                <i class="bi bi-qr-code d-block mb-3 text-muted" style="font-size:3rem;opacity:.3"></i>
                <h6 class="fw-bold text-muted">QR Hari Ini Belum Tersedia</h6>
                <p class="text-muted small mb-3">QR Self-Service untuk hari ini belum di-generate.</p>
                <form method="POST" action="{{ route('self-service.generate-qr') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-qr-code me-1"></i> Generate QR Hari Ini
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
@if($todayQr && $qrUrl)
new QRCode(document.getElementById('qr-canvas'), {
    text: '{{ $qrUrl }}',
    width: 250,
    height: 250,
    colorDark: '#0c2340',
    colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.H,
});

function copyQrUrl() {
    navigator.clipboard.writeText('{{ $qrUrl }}').then(() => {
        const btn = document.getElementById('btn-copy-qr');
        btn.innerHTML = '<i class="bi bi-clipboard-check me-1"></i> Copied!';
        btn.classList.replace('btn-outline-secondary', 'btn-outline-success');
        setTimeout(() => {
            btn.innerHTML = '<i class="bi bi-clipboard me-1"></i> Copy Link';
            btn.classList.replace('btn-outline-success', 'btn-outline-secondary');
        }, 2000);
    });
}
@endif
</script>
@endpush
