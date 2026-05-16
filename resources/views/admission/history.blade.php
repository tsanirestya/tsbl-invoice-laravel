@extends('layouts.app')

@section('title', 'History Redeem')
@section('page-title', 'History Redeem')

@section('content')
<div class="page-hdr d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="page-title">History Redeem</h5>
        <p class="page-sub">{{ $date->translatedFormat('l, d F Y') }}</p>
    </div>
    <a href="{{ route('admission.scan') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-upc-scan me-1"></i> Scan & Redeem
    </a>
</div>

{{-- Filters --}}
<div class="card card-clean mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label small mb-1">Tanggal</label>
                <input type="date" name="date" class="form-control form-control-sm"
                       value="{{ $date->format('Y-m-d') }}" onchange="this.form.submit()">
            </div>
            <div>
                <label class="form-label small mb-1">Match Status</label>
                <select name="match" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="" {{ !$match ? 'selected' : '' }}>Semua</option>
                    <option value="MATCH" {{ $match === 'MATCH' ? 'selected' : '' }}>MATCH</option>
                    <option value="MISMATCH" {{ $match === 'MISMATCH' ? 'selected' : '' }}>MISMATCH</option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-outline-secondary btn-sm">Filter</button>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card table-card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span class="fw-semibold"><i class="bi bi-clock-history me-2 text-primary"></i>Redeem Log</span>
        <span class="text-muted small">{{ $reservations->total() }} entri</span>
    </div>

    @if($reservations->isEmpty())
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
            Tidak ada data untuk filter ini.
        </div>
    @else
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Reservation No</th>
                        <th>Tamu</th>
                        <th>PAX</th>
                        <th>Visit Date</th>
                        <th>Redeemed At</th>
                        <th>Petugas</th>
                        <th>Match</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reservations as $r)
                    <tr>
                        <td><code class="text-primary">{{ $r->reservation_no }}</code></td>
                        <td>
                            <div class="fw-semibold">{{ $r->guest_name }}</div>
                            @if($r->guest_country)
                                <small class="text-muted">{{ $r->guest_country }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="text-muted small">
                                {{ $r->pax_adults }}A
                                @if($r->pax_kids > 0) · {{ $r->pax_kids }}K @endif
                                @if($r->pax_babies > 0) · {{ $r->pax_babies }}B @endif
                            </span>
                        </td>
                        <td>{{ $r->visit_date->format('d M Y') }}</td>
                        <td>{{ $r->redeemed_at?->format('H:i:s') }}</td>
                        <td>{{ $r->redeemer?->full_name ?? '-' }}</td>
                        <td>
                            <span class="badge bg-{{ $r->transactionMatchBadge() }}">
                                {{ $r->transaction_match }}
                            </span>
                        </td>
                        <td>
                            @if($r->transaction_notes)
                                <span class="text-muted small" title="{{ $r->transaction_notes }}">
                                    {{ Str::limit($r->transaction_notes, 50) }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-body py-2">
            {{ $reservations->links() }}
        </div>
    @endif
</div>
@endsection
