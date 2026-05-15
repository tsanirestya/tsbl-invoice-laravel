<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservasi Berhasil — {{ $reservation->reservation_no }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4fb; font-family: 'Segoe UI', sans-serif; }
        .success-icon { font-size: 64px; color: #059669; }
        .card { border-radius: 12px; border: none; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
    </style>
</head>
<body>
<div class="container py-5" style="max-width:540px">
    <div class="text-center mb-4">
        <div class="success-icon mb-2"><i class="bi bi-check-circle-fill"></i></div>
        <h4 class="fw-bold">Reservasi Berhasil!</h4>
        <p class="text-muted">Booking pass Anda siap diunduh.</p>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">No. Reservasi</span>
                <span class="fw-bold">{{ $reservation->reservation_no }}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Nama Tamu</span>
                <span>{{ $reservation->guest_name }}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Tanggal Kunjungan</span>
                <span>{{ $reservation->visit_date->format('d M Y') }}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Total</span>
                <span class="fw-bold">Rp {{ number_format($reservation->total_amount, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div class="d-grid gap-2">
        @if($reservation->booking_pass_file)
            <a href="{{ route('partner.reserve.booking-pass', [$token, $reservation->reservation_no]) }}"
               class="btn btn-success btn-lg">
                <i class="bi bi-file-pdf me-2"></i> Download Booking Pass (PDF)
            </a>
        @endif
        <a href="{{ route('partner.reserve.form', $token) }}" class="btn btn-outline-primary">
            <i class="bi bi-plus-lg me-1"></i> Buat Reservasi Baru
        </a>
        <a href="{{ route('partner.reserve.history', $token) }}" class="btn btn-outline-secondary">
            <i class="bi bi-clock-history me-1"></i> Riwayat Reservasi
        </a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
