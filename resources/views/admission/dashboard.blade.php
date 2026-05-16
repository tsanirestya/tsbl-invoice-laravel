@extends('layouts.app')

@section('title', 'Dashboard Admission')
@section('page-title', 'Dashboard Admission')

@section('content')
<div class="page-hdr d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="page-title">Dashboard Admission</h5>
        <p class="page-sub">{{ $today->translatedFormat('l, d F Y') }} — statistik hari ini</p>
    </div>
    <a href="{{ route('admission.scan') }}" class="btn btn-primary">
        <i class="bi bi-upc-scan me-1"></i> Scan & Redeem
    </a>
</div>

{{-- Stats Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card stat-card gc-amber">
            <div class="card-body p-3 position-relative">
                <div class="stat-label">Menunggu</div>
                <div class="stat-value">{{ $confirmedToday }}</div>
                <i class="bi bi-hourglass-split stat-bg-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card stat-card gc-blue">
            <div class="card-body p-3 position-relative">
                <div class="stat-label">Sudah Masuk</div>
                <div class="stat-value">{{ $redeemedToday }}</div>
                <i class="bi bi-door-open-fill stat-bg-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card stat-card gc-green">
            <div class="card-body p-3 position-relative">
                <div class="stat-label">Match</div>
                <div class="stat-value">{{ $matchToday }}</div>
                <i class="bi bi-check-circle-fill stat-bg-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card stat-card gc-amber">
            <div class="card-body p-3 position-relative">
                <div class="stat-label">Mismatch</div>
                <div class="stat-value">{{ $mismatchToday }}</div>
                <i class="bi bi-exclamation-triangle-fill stat-bg-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card stat-card gc-purple">
            <div class="card-body p-3 position-relative">
                <div class="stat-label">Total PAX Masuk</div>
                <div class="stat-value">{{ $totalPaxIn }}</div>
                <i class="bi bi-people-fill stat-bg-icon"></i>
            </div>
        </div>
    </div>
</div>

{{-- Recent Activity --}}
<div class="card table-card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span class="fw-semibold"><i class="bi bi-clock-history me-2 text-primary"></i>Aktivitas Terakhir</span>
        <a href="{{ route('admission.history') }}" class="btn btn-sm btn-outline-secondary">Lihat Semua</a>
    </div>
    @if($recentActivity->isEmpty())
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
            Belum ada redeem hari ini.
        </div>
    @else
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Reservation No</th>
                        <th>Tamu</th>
                        <th>PAX</th>
                        <th>Waktu Redeem</th>
                        <th>Petugas</th>
                        <th>Match</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentActivity as $r)
                    <tr>
                        <td><code class="text-primary">{{ $r->reservation_no }}</code></td>
                        <td>{{ $r->guest_name }}</td>
                        <td>
                            <span class="text-muted small">
                                {{ $r->pax_adults }}A
                                @if($r->pax_kids > 0) · {{ $r->pax_kids }}K @endif
                                @if($r->pax_babies > 0) · {{ $r->pax_babies }}B @endif
                            </span>
                        </td>
                        <td>{{ $r->redeemed_at?->format('H:i') }}</td>
                        <td>{{ $r->redeemer?->full_name ?? '-' }}</td>
                        <td>
                            <span class="badge bg-{{ $r->transactionMatchBadge() }}">
                                {{ $r->transaction_match }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
