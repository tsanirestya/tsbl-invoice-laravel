@extends('layouts.app')

@section('title', 'Anomali & Fraud')
@section('page-title', 'Anomali & Fraud Prevention')

@section('content')
<div class="page-hdr d-flex align-items-center justify-content-between mb-3">
    <div>
        <div class="page-title">Anomali & Fraud Prevention</div>
        <div class="page-sub">Monitor dan tindak lanjuti anomali reservasi</div>
    </div>
    <a href="{{ route('commission-review.index') }}" class="btn btn-outline-warning btn-sm">
        <i class="bi bi-lock me-1"></i> Komisi Di-hold ({{ \App\Models\ReservationPayment::where('is_commission_held',true)->whereNull('commission_released_at')->count() }})
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- Stats --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card text-center py-3">
            <div class="fs-4 fw-bold text-danger">{{ $stats['critical'] }}</div>
            <div class="small text-muted">CRITICAL Pending</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center py-3">
            <div class="fs-4 fw-bold text-warning">{{ $stats['warning'] }}</div>
            <div class="small text-muted">WARNING Pending</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center py-3">
            <div class="fs-4 fw-bold text-secondary">{{ $stats['pending'] }}</div>
            <div class="small text-muted">Total Unresolved</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center py-3">
            <div class="fs-4 fw-bold text-success">{{ $stats['resolved'] }}</div>
            <div class="small text-muted">Resolved</div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- High-risk partners --}}
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Partner Berisiko Tinggi</div>
            <div class="card-body p-0">
                @if($highRiskPartners->isEmpty())
                    <p class="text-center text-muted py-3 small">Tidak ada partner berisiko.</p>
                @else
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Partner</th><th class="text-center">Score</th><th class="text-center">Level</th></tr></thead>
                        <tbody>
                            @foreach($highRiskPartners as $p)
                            <tr>
                                <td>
                                    <a href="{{ route('partners.show', $p) }}" class="text-decoration-none small">
                                        {{ $p->nama_partner }}
                                    </a>
                                    @if($p->reservation_suspended)
                                        <span class="badge bg-danger ms-1" style="font-size:.6rem">SUSPENDED</span>
                                    @endif
                                </td>
                                <td class="text-center fw-semibold">{{ $p->fraud_score }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $p->fraudRiskBadge() }}">{{ $p->fraudRiskLevel() }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        @if($pendingChecks > 0)
        <div class="card mb-3 border-warning">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="fw-semibold text-warning">Employee-Partner Match</div>
                    <div class="small text-muted">{{ $pendingChecks }} kecocokan perlu direview</div>
                </div>
                <a href="{{ route('admin.employee-partner-checks.index') }}" class="btn btn-sm btn-warning">
                    Review
                </a>
            </div>
        </div>
        @endif
    </div>

    {{-- Anomaly list --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Anomali Terbaru</span>
                <form method="GET" class="d-flex gap-2">
                    <select name="severity" class="form-select form-select-sm" style="width:auto">
                        <option value="">Semua Severity</option>
                        <option value="CRITICAL" {{ request('severity') === 'CRITICAL' ? 'selected' : '' }}>CRITICAL</option>
                        <option value="WARNING" {{ request('severity') === 'WARNING' ? 'selected' : '' }}>WARNING</option>
                    </select>
                    <select name="type" class="form-select form-select-sm" style="width:auto">
                        <option value="">Semua Tipe</option>
                        @foreach($anomalyTypes as $t)
                            <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                    </select>
                    <div class="form-check my-auto ms-1">
                        <input type="checkbox" name="unresolved_only" value="1" id="uo" class="form-check-input"
                               {{ request('unresolved_only') ? 'checked' : '' }}>
                        <label class="form-check-label small" for="uo">Unresolved</label>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
                </form>
            </div>
            <div class="card-body p-0">
                @if($anomalies->isEmpty())
                    <div class="text-center text-muted py-4">Tidak ada anomali.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Tipe</th>
                                <th>Reservasi</th>
                                <th>Partner</th>
                                <th class="text-center">Sev.</th>
                                <th class="text-center">+Skor</th>
                                <th class="text-center">Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($anomalies as $a)
                            <tr>
                                <td><code class="small">{{ $a->anomaly_type }}</code></td>
                                <td>
                                    <a href="{{ route('reservations.show', $a->reservation) }}" class="small text-decoration-none">
                                        {{ $a->reservation?->reservation_no }}
                                    </a>
                                </td>
                                <td class="small">{{ $a->reservation?->partner?->nama_partner ?? '—' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $a->severityBadge() }}">{{ $a->severity }}</span>
                                </td>
                                <td class="text-center fw-semibold text-danger">+{{ $a->score_impact }}</td>
                                <td class="text-center">
                                    @if($a->is_resolved)
                                        <span class="badge bg-success">Resolved</span>
                                    @else
                                        <span class="badge bg-secondary">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('anomalies.show', $a) }}" class="btn btn-xs btn-outline-secondary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $anomalies->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
