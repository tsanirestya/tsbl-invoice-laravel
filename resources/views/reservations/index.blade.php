@extends('layouts.app')

@section('title', 'Reservasi')
@section('page-title', 'Reservasi')

@section('content')
<div class="page-hdr d-flex align-items-center justify-content-between mb-3">
    <div>
        <div class="page-title">Reservasi</div>
        <div class="page-sub">Kelola semua reservasi tamu</div>
    </div>
    <a href="{{ route('reservations.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Buat Reservasi
    </a>
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-3">
                <label class="form-label small mb-1">Partner</label>
                <select name="partner_id" class="form-select form-select-sm">
                    <option value="">Semua Partner</option>
                    @foreach($partners as $p)
                        <option value="{{ $p->id }}" {{ request('partner_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->nama_partner }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    @foreach(['PENDING','CONFIRMED','CANCELLED','NO_SHOW','COMPLETED'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1">Tipe</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">Semua Tipe</option>
                    @foreach(['PARTNER','INTERNAL','SELF_SERVICE'] as $t)
                        <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1">Visit Dari</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1">Visit s/d</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
            </div>
            <div class="col-sm-1">
                <button type="submit" class="btn btn-sm btn-outline-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="card-body p-0">
        @if($reservations->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                Belum ada reservasi.
            </div>
        @else
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>No. Reservasi</th>
                        <th>Tamu</th>
                        <th>Partner</th>
                        <th>Visit Date</th>
                        <th>Tipe</th>
                        <th>Pembayaran</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Anomali</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reservations as $res)
                    <tr>
                        <td>
                            <a href="{{ route('reservations.show', $res) }}" class="fw-semibold text-decoration-none">
                                {{ $res->reservation_no }}
                            </a>
                            @if($res->is_danger_zone)
                                <i class="bi bi-geo-alt-fill text-danger ms-1" title="Danger Zone"></i>
                            @endif
                            @if($res->is_spot_check)
                                <i class="bi bi-shield-check text-warning ms-1" title="Spot Check"></i>
                            @endif
                        </td>
                        <td>
                            {{ $res->guest_name }}
                            @if($res->guest_country)
                                <small class="text-muted">({{ $res->guest_country }})</small>
                            @endif
                        </td>
                        <td>{{ $res->partner?->nama_partner ?? ($res->partner_name_input ?? '-') }}</td>
                        <td>{{ $res->visit_date->format('d/m/Y') }}</td>
                        <td>
                            <span class="badge bg-{{ $res->reservation_type === 'INTERNAL' ? 'primary' : ($res->reservation_type === 'PARTNER' ? 'info' : 'secondary') }}">
                                {{ $res->reservation_type }}
                            </span>
                        </td>
                        <td>
                            @if($res->payment_method)
                                <small>{{ str_replace('_', ' ', $res->payment_method) }}</small>
                            @else
                                <small class="text-muted">-</small>
                            @endif
                        </td>
                        <td class="text-end fw-semibold">
                            Rp {{ number_format($res->total_amount, 0, ',', '.') }}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-{{ $res->statusBadge() }}">{{ $res->status }}</span>
                        </td>
                        <td class="text-center">
                            @php $anomalyCount = $res->anomalies->where('is_resolved', false)->count(); @endphp
                            @if($anomalyCount > 0)
                                <span class="badge bg-danger">{{ $anomalyCount }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('reservations.show', $res) }}" class="btn btn-xs btn-outline-secondary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $reservations->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
