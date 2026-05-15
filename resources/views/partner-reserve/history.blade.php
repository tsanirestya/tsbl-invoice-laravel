<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Reservasi — {{ $partner->nama_partner }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4fb; font-family: 'Segoe UI', sans-serif; }
        .brand-bar { background: #0f1729; color: #fff; padding: 12px 16px; margin-bottom: 1.5rem; }
        .card { border-radius: 12px; border: none; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
    </style>
</head>
<body>
<div class="brand-bar">
    <div class="d-flex justify-content-between align-items-center">
        <div class="fw-bold"><i class="bi bi-clock-history me-2"></i>Riwayat Reservasi</div>
        <small class="text-white-50">{{ $partner->nama_partner }}</small>
    </div>
</div>

<div class="container" style="max-width:640px">
    <div class="mb-3">
        <a href="{{ route('partner.reserve.form', $token) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Buat Reservasi Baru
        </a>
    </div>

    @if($reservations->isEmpty())
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                Belum ada riwayat reservasi.
            </div>
        </div>
    @else
        @foreach($reservations as $res)
        <div class="card mb-2">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <span class="fw-semibold">{{ $res->reservation_no }}</span>
                    <span class="badge bg-{{ $res->statusBadge() }}">{{ $res->status }}</span>
                </div>
                <div class="small text-muted mb-1">
                    {{ $res->guest_name }} — {{ $res->visit_date->format('d M Y') }}
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Rp {{ number_format($res->total_amount, 0, ',', '.') }}</span>
                    @if($res->booking_pass_file)
                        <a href="{{ route('partner.reserve.booking-pass', [$token, $res->reservation_no]) }}"
                           class="btn btn-sm btn-outline-success">
                            <i class="bi bi-file-pdf"></i> BP
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
        <div class="mt-3">{{ $reservations->links() }}</div>
    @endif
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
