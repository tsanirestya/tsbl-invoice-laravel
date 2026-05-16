<!DOCTYPE html>
<html lang="id" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Permissions-Policy" content="geolocation=(self)">
    <title>Check-In Mandiri / Self Check-In</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4fb; font-family: 'Segoe UI', sans-serif; }
        .brand-bar { background: linear-gradient(135deg, #0f1729, #1e3a5f); color: #fff; padding: 16px; text-align: center; margin-bottom: 1.5rem; position: relative; }
        .lang-toggle { position: absolute; top: 12px; right: 12px; display: flex; gap: 4px; }
        .lang-btn { padding: 3px 10px; border-radius: 20px; border: 1.5px solid rgba(255,255,255,.4); background: transparent; color: rgba(255,255,255,.7); font-size: .75rem; font-weight: 600; cursor: pointer; transition: all .2s; }
        .lang-btn.active { background: #fff; color: #0f1729; border-color: #fff; }
        .card { border-radius: 12px; border: none; box-shadow: 0 1px 4px rgba(0,0,0,.07); }
        .form-control, .form-select { min-height: 48px; border-radius: 8px; font-size: 15px; }
        .btn-primary { min-height: 48px; font-size: 16px; border-radius: 8px; }
        .ts-wrapper { min-height: 48px; }
        .ts-control { min-height: 48px; border-radius: 8px !important; font-size: 15px; align-items: center; }
        .location-box { background: #f8f9fa; border: 1.5px solid #dee2e6; border-radius: 10px; padding: 12px 14px; }
        .location-box.detecting { border-color: #ffc107; background: #fffbf0; }
        .location-box.success { border-color: #198754; background: #f0faf4; }
        .location-box.error { border-color: #dc3545; background: #fff5f5; }
        .gps-guide { display: none; margin-top: 10px; padding: 10px; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; font-size: 0.85rem; }
        .gps-guide.show { display: block; }
        .gps-guide i { color: #0d6efd; }
        #submitBtn:disabled { opacity: .6; cursor: not-allowed; }
    </style>
</head>
<body>
<div class="brand-bar">
    <div class="lang-toggle">
        <button type="button" class="lang-btn" id="btnID" onclick="setLang('id')">ID</button>
        <button type="button" class="lang-btn" id="btnEN" onclick="setLang('en')">EN</button>
    </div>
    <i class="bi bi-qr-code-scan fs-2 mb-1 d-block"></i>
    <h5 class="fw-bold mb-0">
        <span data-id="Check-In Mandiri" data-en="Self Check-In">Check-In Mandiri</span>
    </h5>
    <small class="opacity-75">
        <span data-id="Isi data tamu untuk mendapatkan Booking Pass"
              data-en="Fill in guest details to receive your Booking Pass">
            Isi data tamu untuk mendapatkan Booking Pass
        </span>
    </small>
</div>

<div class="container" style="max-width:500px">
    @if($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('self-service.store', $token) }}" enctype="multipart/form-data" id="ssForm">
    @csrf

    {{-- Lokasi — wajib, auto-detect, tampil di atas --}}
    <div class="card mb-3">
        <div class="card-header fw-semibold">
            <i class="bi bi-geo-alt-fill me-2 text-primary"></i>
            <span data-id="Lokasi Anda" data-en="Your Location">Lokasi Anda</span>
            <span class="text-danger">*</span>
        </div>
        <div class="card-body p-3">
            <div id="locationBox" class="location-box detecting">
                <div id="locationContent">
                    <div class="d-flex align-items-center gap-2 text-warning-emphasis">
                        <span class="spinner-border spinner-border-sm"></span>
                        <span id="locationStatus" data-id="Mendeteksi lokasi..." data-en="Detecting location...">
                            Mendeteksi lokasi...
                        </span>
                    </div>
                </div>
            </div>
            <div id="locationError" class="mt-2 d-none">
                <button type="button" class="btn btn-sm btn-outline-danger w-100" id="retryGps">
                    <i class="bi bi-arrow-clockwise me-1"></i>
                    <span data-id="Coba Lagi" data-en="Retry">Coba Lagi</span>
                </button>
                <div id="gpsGuide" class="gps-guide">
                    <div class="fw-bold mb-1"><i class="bi bi-info-circle-fill me-1"></i> <span data-id="Cara Mengaktifkan:" data-en="How to Enable:">Cara Mengaktifkan:</span></div>
                    <ol class="ps-3 mb-0">
                        <li data-id="Klik ikon gembok/pengaturan di sebelah alamat situs (URL) di atas." data-en="Click the lock/settings icon next to the site address (URL) above.">Klik ikon gembok/pengaturan di sebelah alamat situs (URL) di atas.</li>
                        <li data-id="Pilih 'Izin' atau 'Site Settings'." data-en="Select 'Permissions' or 'Site Settings'.">Pilih 'Izin' atau 'Site Settings'.</li>
                        <li data-id="Aktifkan 'Lokasi' atau 'Location' ke 'Izinkan/Allow'." data-en="Switch 'Location' to 'Allow'.">Aktifkan 'Lokasi' atau 'Location' ke 'Izinkan/Allow'.</li>
                        <li data-id="Refresh halaman ini." data-en="Refresh this page.">Refresh halaman ini.</li>
                    </ol>
                </div>
            </div>
            <input type="hidden" name="latitude" id="lat">
            <input type="hidden" name="longitude" id="lng">
            <input type="hidden" name="location_name" id="locationName">
        </div>
    </div>

    {{-- Data Tamu --}}
    <div class="card mb-3">
        <div class="card-header fw-semibold">
            <i class="bi bi-person me-2"></i>
            <span data-id="Data Tamu" data-en="Guest Information">Data Tamu</span>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">
                    <span data-id="Nama Lengkap" data-en="Full Name">Nama Lengkap</span>
                    <span class="text-danger">*</span>
                </label>
                <input type="text" name="guest_name" value="{{ old('guest_name') }}" class="form-control"
                       required
                       data-placeholder-id="Nama tamu"
                       data-placeholder-en="Guest name"
                       placeholder="Nama tamu">
            </div>
            <div class="mb-3">
                <label class="form-label">
                    <span data-id="Hotel / Partner" data-en="Hotel / Partner">Hotel / Partner</span>
                </label>
                <select name="partner_id" id="hotelSelect">
                    <option value="">— Tanpa hotel / walk-in —</option>
                    @foreach($hotelPartners as $h)
                        <option value="{{ $h->id }}" {{ old('partner_id') == $h->id ? 'selected' : '' }}>
                            {{ $h->nama_partner }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label">
                        <span data-id="Negara" data-en="Country">Negara</span>
                    </label>
                    <select name="guest_country" id="countrySelect">
                        @include('partials._country_options', ['selected' => old('guest_country', 'Indonesia')])
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label">
                        <span data-id="Tanggal Kunjungan" data-en="Visit Date">Tanggal Kunjungan</span>
                        <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="visit_date" value="{{ old('visit_date', date('Y-m-d')) }}"
                           class="form-control" required min="{{ date('Y-m-d') }}">
                </div>
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label">
                        <span data-id="Dewasa" data-en="Adults">Dewasa</span>
                        <span class="text-danger">*</span>
                    </label>
                    <input type="number" name="pax_adults" value="{{ old('pax_adults', 1) }}"
                           class="form-control" required min="1" max="999">
                </div>
                <div class="col-6">
                    <label class="form-label">
                        <span data-id="Anak-anak" data-en="Kids">Anak-anak</span>
                        <span class="text-danger">*</span>
                    </label>
                    <input type="number" name="pax_kids" value="{{ old('pax_kids', 0) }}"
                           class="form-control" required min="0" max="999">
                </div>
            </div>
        </div>
    </div>

    {{-- No. Kunci / Voucher --}}
    <div class="card mb-3">
        <div class="card-header fw-semibold">
            <i class="bi bi-upc-scan me-2"></i>
            <span data-id="No. Kunci / Voucher" data-en="Key No. / Voucher No.">No. Kunci / Voucher</span>
            <span class="text-danger">*</span>
        </div>
        <div class="card-body">
            <input type="text" name="key_number" value="{{ old('key_number') }}" class="form-control"
                   required
                   data-placeholder-id="Nomor kunci kamar atau nomor voucher"
                   data-placeholder-en="Room key number or voucher number"
                   placeholder="Nomor kunci kamar atau nomor voucher">
        </div>
    </div>

    {{-- Foto Kunci / Kartu Kamar --}}
    <div class="card mb-3">
        <div class="card-header fw-semibold">
            <i class="bi bi-image me-2"></i>
            <span data-id="Foto Kunci / Kartu Kamar" data-en="Room Key / Key Card Photo">Foto Kunci / Kartu Kamar</span>
            <span class="text-danger">*</span>
        </div>
        <div class="card-body">
            <input type="file" name="room_key_photo" class="form-control" accept="image/*" capture="environment" required>
            <small class="text-muted">
                <span data-id="Wajib — foto kunci atau kartu kamar hotel."
                      data-en="Required — photo of room key or hotel key card.">
                    Wajib — foto kunci atau kartu kamar hotel.
                </span>
            </small>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 mb-4" id="submitBtn" disabled>
        <i class="bi bi-check-lg me-1"></i>
        <span data-id="Dapatkan Booking Pass" data-en="Get Booking Pass">Dapatkan Booking Pass</span>
    </button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
// ── Language System ───────────────────────────────────────────────────────────
const tsHotel   = new TomSelect('#hotelSelect', { create: false, allowEmptyOption: true });
const tsCountry = new TomSelect('#countrySelect', { create: false, allowEmptyOption: true });
const LANG_KEY  = 'ss_lang';

const T = {
    hotel_ph:   { id: 'Cari hotel...',  en: 'Search hotel...' },
    country_ph: { id: 'Cari negara...', en: 'Search country...' },
    detecting:  { id: 'Mendeteksi lokasi...', en: 'Detecting location...' },
    gps_ok:     { id: 'Lokasi terdeteksi', en: 'Location detected' },
    gps_fail:   { id: 'Sinyal GPS lemah atau tidak tersedia. Pastikan Anda tidak berada di dalam ruangan tertutup rapat.', en: 'GPS signal is weak or unavailable. Please ensure you are not deep indoors.' },
    gps_deny:   { id: 'Akses lokasi ditolak. Aktifkan izin lokasi di Chrome DAN pengaturan privasi HP Anda.', en: 'Location access denied. Please enable location in Chrome AND your phone\'s privacy settings.' },
    gps_timeout: { id: 'Waktu pencarian lokasi habis (Timeout). Coba lagi di tempat yang lebih terbuka.', en: 'Location search timed out. Please try again in a more open area.' },
    processing: { id: 'Memproses...', en: 'Processing...' },
};

function setLang(lang) {
    localStorage.setItem(LANG_KEY, lang);
    document.getElementById('htmlRoot').lang = lang === 'en' ? 'en' : 'id';

    document.querySelectorAll('[data-id][data-en]').forEach(el => {
        el.textContent = el.dataset[lang];
    });
    document.querySelectorAll('[data-placeholder-id]').forEach(el => {
        el.placeholder = lang === 'en' ? el.dataset.placeholderEn : el.dataset.placeholderId;
    });

    tsHotel.control_input.placeholder   = T.hotel_ph[lang];
    tsCountry.control_input.placeholder = T.country_ph[lang];

    document.getElementById('btnID').classList.toggle('active', lang === 'id');
    document.getElementById('btnEN').classList.toggle('active', lang === 'en');
}

function lang() { return localStorage.getItem(LANG_KEY) || 'id'; }

setLang(lang());

// ── GPS — auto, mandatory ─────────────────────────────────────────────────────
const locationBox  = document.getElementById('locationBox');
const locationErr  = document.getElementById('locationError');
const submitBtn    = document.getElementById('submitBtn');
const geocodeUrl   = '{{ route('geocode.public') }}';

function setLocationDetecting() {
    locationBox.className = 'location-box detecting';
    locationErr.classList.add('d-none');
    submitBtn.disabled = true;
    document.getElementById('locationContent').innerHTML = `
        <div class="d-flex align-items-center gap-2 text-warning-emphasis">
            <span class="spinner-border spinner-border-sm"></span>
            <span>${T.detecting[lang()]}</span>
        </div>`;
}

function setLocationSuccess(lat, lng, areaName) {
    document.getElementById('lat').value          = lat;
    document.getElementById('lng').value          = lng;
    document.getElementById('locationName').value = areaName;

    locationBox.className = 'location-box success';
    locationErr.classList.add('d-none');
    submitBtn.disabled = false;

    document.getElementById('locationContent').innerHTML = `
        <div class="d-flex align-items-start gap-2">
            <i class="bi bi-geo-alt-fill text-success mt-1"></i>
            <div>
                <div class="fw-semibold text-success lh-sm">${areaName}</div>
                <div class="small text-muted">${lat.toFixed(6)}, ${lng.toFixed(6)}</div>
            </div>
        </div>`;
}

function setLocationError(msg, isDeny = false) {
    locationBox.className = 'location-box error';
    locationErr.classList.remove('d-none');
    submitBtn.disabled = true;

    if (isDeny) {
        document.getElementById('gpsGuide').classList.add('show');
    } else {
        document.getElementById('gpsGuide').classList.remove('show');
    }

    document.getElementById('locationContent').innerHTML = `
        <div class="d-flex align-items-start gap-2 text-danger">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <span class="small">${msg}</span>
        </div>`;
}

function captureGPS(options = { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }) {
    setLocationDetecting();

    if (!navigator.geolocation) {
        setLocationError(lang() === 'en'
            ? 'Geolocation is not supported by your browser.'
            : 'Browser tidak mendukung geolocation.');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        pos => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;

            // Show coords first, then resolve area name
            setLocationSuccess(lat, lng, `${lat.toFixed(6)}, ${lng.toFixed(6)}`);

            fetch(`${geocodeUrl}?lat=${lat}&lng=${lng}`)
                .then(r => r.json())
                .then(data => {
                    const addr = data.address || {};
                    const area = addr.village || addr.suburb || addr.quarter
                               || addr.neighbourhood || addr.town || addr.city
                               || addr.county || addr.state || 'Lokasi terdeteksi';
                    const district = addr.city || addr.town || addr.county || '';
                    const areaFull = district && district !== area ? `${area}, ${district}` : area;
                    setLocationSuccess(lat, lng, areaFull);
                })
                .catch(() => {
                    // Keep coords as fallback — location is still valid
                    setLocationSuccess(lat, lng, `${lat.toFixed(5)}, ${lng.toFixed(5)}`);
                });
        },
        err => {
            console.error('GPS Error:', err);
            if (err.code === 1) { // PERMISSION_DENIED
                setLocationError(T.gps_deny[lang()], true);
            } else if (err.code === 3) { // TIMEOUT
                // Fallback to low accuracy once if high accuracy times out
                if (options.enableHighAccuracy) {
                    console.warn('High accuracy timed out, retrying with low accuracy...');
                    captureGPS({ enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 });
                } else {
                    setLocationError(T.gps_timeout[lang()], false);
                }
            } else { // POSITION_UNAVAILABLE
                setLocationError(T.gps_fail[lang()], false);
            }
        },
        options
    );
}

document.getElementById('retryGps').addEventListener('click', captureGPS);

// ── Submit guard ──────────────────────────────────────────────────────────────
document.getElementById('ssForm').addEventListener('submit', e => {
    if (!document.getElementById('lat').value) {
        e.preventDefault();
        captureGPS();
        return;
    }
    submitBtn.disabled = true;
    submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> ${T.processing[lang()]}`;
});

// ── Auto-start GPS on load ────────────────────────────────────────────────────
window.addEventListener('load', captureGPS);
</script>
</body>
</html>
