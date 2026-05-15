@extends('layouts.app')

@section('title', 'Anomali Detail')
@section('page-title', 'Detail Anomali')

@section('content')
<div class="page-hdr d-flex align-items-center justify-content-between mb-3">
    <div>
        <div class="page-title">{{ $anomaly->anomaly_type }}</div>
        <div class="page-sub">
            <span class="badge bg-{{ $anomaly->severityBadge() }}">{{ $anomaly->severity }}</span>
            +{{ $anomaly->score_impact }} poin
        </div>
    </div>
    <a href="{{ route('anomalies.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Detail Anomali</div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-muted small mb-1">Deskripsi</div>
                    <div class="alert alert-{{ $anomaly->severity === 'CRITICAL' ? 'danger' : 'warning' }} py-2">
                        {{ $anomaly->detail }}
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <div class="text-muted small">Reservasi</div>
                        <a href="{{ route('reservations.show', $anomaly->reservation) }}" class="fw-semibold text-decoration-none">
                            {{ $anomaly->reservation?->reservation_no }}
                        </a>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Partner</div>
                        <div>{{ $anomaly->reservation?->partner?->nama_partner ?? '—' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Terdeteksi</div>
                        <div>{{ $anomaly->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Status</div>
                        <div>
                            @if($anomaly->is_resolved)
                                <span class="badge bg-success">RESOLVED</span>
                                <small class="text-muted d-block">oleh {{ $anomaly->resolvedBy?->name }} — {{ $anomaly->resolved_at?->format('d/m/Y H:i') }}</small>
                                <small class="text-muted">{{ $anomaly->resolution_type }} — {{ $anomaly->resolution_notes }}</small>
                            @else
                                <span class="badge bg-secondary">PENDING</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(!$anomaly->is_resolved)
        <div class="card mb-3">
            <div class="card-header fw-semibold">Resolve Anomali</div>
            <div class="card-body">
                <form method="POST" action="{{ route('anomalies.resolve', $anomaly) }}">
                @csrf
                    <div class="mb-3">
                        <label class="form-label">Hasil Pemeriksaan <span class="text-danger">*</span></label>
                        <select name="resolution_type" class="form-select" required>
                            <option value="">— Pilih —</option>
                            <option value="FALSE_POSITIVE">False Positive (tidak ada masalah)</option>
                            <option value="CLEARED">Cleared (diselesaikan)</option>
                            <option value="CONFIRMED_FRAUD">Confirmed Fraud (fraud terkonfirmasi)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan <span class="text-danger">*</span></label>
                        <textarea name="resolution_notes" class="form-control" rows="3" required
                                  placeholder="Jelaskan hasil pemeriksaan..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-check-lg me-1"></i> Simpan Hasil Pemeriksaan
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-5">
        @if($anomaly->reservation?->partner)
        <div class="card mb-3">
            <div class="card-header fw-semibold">Partner</div>
            <div class="card-body">
                <div class="fw-semibold mb-1">{{ $anomaly->reservation->partner->nama_partner }}</div>
                <div class="d-flex gap-2 mb-2">
                    <span class="badge bg-{{ $anomaly->reservation->partner->fraudRiskBadge() }}">
                        {{ $anomaly->reservation->partner->fraudRiskLevel() }} (Score: {{ $anomaly->reservation->partner->fraud_score }})
                    </span>
                    @if($anomaly->reservation->partner->reservation_suspended)
                        <span class="badge bg-danger">SUSPENDED</span>
                    @endif
                </div>
                <a href="{{ route('partners.show', $anomaly->reservation->partner) }}" class="btn btn-sm btn-outline-secondary">
                    Lihat Partner
                </a>
            </div>
        </div>
        @endif

        <div class="card mb-3">
            <div class="card-header fw-semibold">Konteks Reservasi</div>
            <div class="card-body small">
                <div class="mb-1"><span class="text-muted">Tamu:</span> {{ $anomaly->reservation?->guest_name }}</div>
                <div class="mb-1"><span class="text-muted">Visit Date:</span> {{ $anomaly->reservation?->visit_date?->format('d/m/Y') }}</div>
                <div class="mb-1"><span class="text-muted">Tipe:</span> {{ $anomaly->reservation?->reservation_type }}</div>
                <div class="mb-1"><span class="text-muted">Pembayaran:</span> {{ $anomaly->reservation?->payment_method ?? '—' }}</div>
                @if($anomaly->reservation?->is_danger_zone)
                    <div class="badge bg-danger mt-1">Dalam Danger Zone</div>
                @endif
                @if($anomaly->reservation?->latitude)
                    <div class="mt-1">
                        <span class="text-muted">GPS:</span> {{ $anomaly->reservation->latitude }}, {{ $anomaly->reservation->longitude }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
