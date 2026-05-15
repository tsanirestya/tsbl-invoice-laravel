<!DOCTYPE html>
<html lang="id" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-In Berhasil / Check-In Success</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4fb; font-family: 'Segoe UI', sans-serif; }
        .brand-bar { background: linear-gradient(135deg, #0f1729, #1e3a5f); color: #fff; padding: 12px 16px; position: relative; }
        .lang-toggle { position: absolute; top: 10px; right: 12px; display: flex; gap: 4px; }
        .lang-btn { padding: 3px 10px; border-radius: 20px; border: 1.5px solid rgba(255,255,255,.4); background: transparent; color: rgba(255,255,255,.7); font-size: .75rem; font-weight: 600; cursor: pointer; transition: all .2s; }
        .lang-btn.active { background: #fff; color: #0f1729; border-color: #fff; }
        .card { border-radius: 12px; border: none; box-shadow: 0 1px 4px rgba(0,0,0,.07); }
        .success-icon { font-size: 64px; color: #059669; }
        .info-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9; }
        .info-row:last-child { border-bottom: none; }
        .btn-view { background: #0f1729; color: #fff; }
        .btn-view:hover { background: #1e3a5f; color: #fff; }
    </style>
</head>
<body>
<div class="brand-bar mb-4">
    <div class="lang-toggle">
        <button type="button" class="lang-btn" id="btnID" onclick="setLang('id')">ID</button>
        <button type="button" class="lang-btn" id="btnEN" onclick="setLang('en')">EN</button>
    </div>
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-qr-code-scan fs-5"></i>
        <span class="fw-bold">TSBL Check-In</span>
    </div>
</div>

<div class="container py-2" style="max-width:480px">
    <div class="text-center mb-4">
        <div class="success-icon mb-2"><i class="bi bi-check-circle-fill"></i></div>
        <h4 class="fw-bold">
            <span data-id="Check-In Berhasil!" data-en="Check-In Successful!">Check-In Berhasil!</span>
        </h4>
        <p class="text-muted">
            <span data-id="Booking pass Anda siap." data-en="Your booking pass is ready.">Booking pass Anda siap.</span>
        </p>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="info-row">
                <span class="text-muted">
                    <span data-id="No. Reservasi" data-en="Reservation No.">No. Reservasi</span>
                </span>
                <span class="fw-bold text-primary">{{ $reservation->reservation_no }}</span>
            </div>
            <div class="info-row">
                <span class="text-muted">
                    <span data-id="Nama Tamu" data-en="Guest Name">Nama Tamu</span>
                </span>
                <span class="fw-semibold">{{ $reservation->guest_name }}</span>
            </div>
            <div class="info-row">
                <span class="text-muted">
                    <span data-id="Tanggal" data-en="Date">Tanggal</span>
                </span>
                <span>{{ $reservation->visit_date->format('d M Y') }}</span>
            </div>
            @if($reservation->partner_name_input)
            <div class="info-row">
                <span class="text-muted">Hotel</span>
                <span>{{ $reservation->partner_name_input }}</span>
            </div>
            @endif
            <div class="info-row">
                <span class="text-muted">
                    <span data-id="Tamu" data-en="Guests">Tamu</span>
                </span>
                <span>
                    <span data-id="{{ $reservation->pax_adults }} Dewasa" data-en="{{ $reservation->pax_adults }} Adult(s)">
                        {{ $reservation->pax_adults }} Dewasa
                    </span>
                    @if($reservation->pax_kids > 0)
                        · <span data-id="{{ $reservation->pax_kids }} Anak" data-en="{{ $reservation->pax_kids }} Kid(s)">
                            {{ $reservation->pax_kids }} Anak
                          </span>
                    @endif
                </span>
            </div>
            @if($reservation->key_number)
            <div class="info-row">
                <span class="text-muted">
                    <span data-id="No. Kunci / Voucher" data-en="Key / Voucher No.">No. Kunci / Voucher</span>
                </span>
                <span>{{ $reservation->key_number }}</span>
            </div>
            @endif
        </div>
    </div>

    @if($reservation->booking_pass_file)
    <div class="d-grid gap-2 mb-3">
        <a href="{{ route('self-service.booking-pass-view', [$token, $reservation->reservation_no]) }}"
           target="_blank"
           class="btn btn-lg btn-view">
            <i class="bi bi-eye me-2"></i>
            <span data-id="Lihat Booking Pass" data-en="View Booking Pass">Lihat Booking Pass</span>
        </a>
        <a href="{{ route('self-service.booking-pass', [$token, $reservation->reservation_no]) }}"
           class="btn btn-lg btn-outline-success">
            <i class="bi bi-download me-2"></i>
            <span data-id="Unduh Booking Pass" data-en="Download Booking Pass">Unduh Booking Pass</span>
        </a>
    </div>
    @endif

    <a href="{{ route('self-service.scan', $token) }}" class="btn btn-outline-secondary w-100">
        <i class="bi bi-arrow-left me-1"></i>
        <span data-id="Kembali ke Form" data-en="Back to Form">Kembali ke Form</span>
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const LANG_KEY = 'ss_lang';

function setLang(lang) {
    localStorage.setItem(LANG_KEY, lang);
    document.getElementById('htmlRoot').lang = lang === 'en' ? 'en' : 'id';
    document.querySelectorAll('[data-id][data-en]').forEach(el => {
        el.textContent = el.dataset[lang];
    });
    document.getElementById('btnID').classList.toggle('active', lang === 'id');
    document.getElementById('btnEN').classList.toggle('active', lang === 'en');
}

setLang(localStorage.getItem(LANG_KEY) || 'id');
</script>
</body>
</html>
