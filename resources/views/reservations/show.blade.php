@extends('layouts.app')

@section('title', 'Reservasi ' . $reservation->reservation_no)
@section('page-title', 'Detail Reservasi')

@section('content')
<div class="page-hdr d-flex align-items-center justify-content-between mb-3">
    <div>
        <div class="page-title">{{ $reservation->reservation_no }}</div>
        <div class="page-sub">{{ $reservation->guest_name }} — {{ $reservation->visit_date->format('d M Y') }}</div>
    </div>
    <div class="d-flex gap-2">
        @if($reservation->booking_pass_file)
            <a href="{{ route('reservations.booking-pass', $reservation) }}" class="btn btn-sm btn-outline-success" target="_blank">
                <i class="bi bi-file-pdf me-1"></i> Booking Pass
            </a>
        @endif
        @if(!in_array($reservation->status, ['CANCELLED','COMPLETED']))
            <a href="{{ route('reservations.edit', $reservation) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            @if(auth()->user()->status !== 'VIEWER')
            <form method="POST" action="{{ route('reservations.cancel', $reservation) }}"
                  onsubmit="return confirm('Batalkan reservasi ini?')">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-x-lg me-1"></i> Batalkan
                </button>
            </form>
            @endif
        @endif
        <a href="{{ route('reservations.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        {{-- Info Card --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Informasi Reservasi</span>
                <span class="badge bg-{{ $reservation->statusBadge() }}">{{ $reservation->status }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small">Tamu</div>
                        <div class="fw-semibold">{{ $reservation->guest_name }}</div>
                        @if($reservation->guest_country)
                            <small class="text-muted">{{ $reservation->guest_country }}</small>
                        @endif
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Partner</div>
                        <div>{{ $reservation->partner?->nama_partner ?? ($reservation->partner_name_input ?? '—') }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Tanggal Kunjungan</div>
                        <div class="fw-semibold">{{ $reservation->visit_date->format('d M Y') }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Tipe Reservasi</div>
                        <div>
                            <span class="badge bg-{{ $reservation->reservation_type === 'INTERNAL' ? 'primary' : ($reservation->reservation_type === 'PARTNER' ? 'info' : 'secondary') }}">
                                {{ $reservation->reservation_type }}
                            </span>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Asal Tamu</div>
                        <div>{{ $reservation->customer_origin ? str_replace('_', ' ', $reservation->customer_origin) : '—' }}</div>
                        @if($reservation->customer_origin_detail)
                            <small class="text-muted">{{ $reservation->customer_origin_detail }}</small>
                        @endif
                    </div>
                    @if($reservation->notes)
                    <div class="col-12">
                        <div class="text-muted small">Catatan</div>
                        <div>{{ $reservation->notes }}</div>
                    </div>
                    @endif
                    <div class="col-sm-6">
                        <div class="text-muted small">Dibuat oleh</div>
                        <div>{{ $reservation->creator?->name ?? 'System' }}</div>
                        <small class="text-muted">{{ $reservation->created_at->format('d/m/Y H:i') }}</small>
                    </div>
                    @if($reservation->is_danger_zone || $reservation->is_spot_check)
                    <div class="col-sm-6">
                        <div class="text-muted small">Flags</div>
                        <div class="d-flex gap-2 flex-wrap">
                            @if($reservation->is_danger_zone)
                                <span class="badge bg-danger"><i class="bi bi-geo-alt-fill me-1"></i>Danger Zone</span>
                            @endif
                            @if($reservation->is_spot_check)
                                <span class="badge bg-warning text-dark"><i class="bi bi-shield-check me-1"></i>Spot Check</span>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Items --}}
        <div class="card mb-3">
            <div class="card-header fw-semibold">Produk</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Harga/Pax</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reservation->items as $item)
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td class="text-center">{{ $item->qty }}</td>
                            <td class="text-end">Rp {{ number_format($item->price_per_pax, 0, ',', '.') }}</td>
                            <td class="text-end fw-semibold">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-semibold">
                            <td colspan="3" class="text-end">Total</td>
                            <td class="text-end">Rp {{ number_format($reservation->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Payment --}}
        @if($reservation->payment)
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center fw-semibold">
                <span>Pembayaran</span>
                <span class="badge bg-{{ $reservation->payment->payment_status === 'VERIFIED' ? 'success' : ($reservation->payment->payment_status === 'PAID' ? 'info' : 'warning') }}">
                    {{ $reservation->payment->payment_status }}
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-4">
                        <div class="text-muted small">Metode</div>
                        <div>{{ str_replace('_', ' ', $reservation->payment->payment_method) }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Gross Amount</div>
                        <div class="fw-semibold">Rp {{ number_format($reservation->payment->gross_amount, 0, ',', '.') }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Komisi</div>
                        <div class="{{ $reservation->payment->is_commission_held ? 'text-danger' : '' }}">
                            Rp {{ number_format($reservation->payment->commission_amount, 0, ',', '.') }}
                            @if($reservation->payment->is_commission_held)
                                <span class="badge bg-danger ms-1">HELD</span>
                            @endif
                        </div>
                    </div>
                    @if($reservation->payment->is_commission_held && $reservation->payment->commission_hold_reason)
                    <div class="col-12">
                        <div class="alert alert-warning py-2 small mb-0">
                            <i class="bi bi-lock me-1"></i> {{ $reservation->payment->commission_hold_reason }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Anomalies --}}
        @if($reservation->anomalies->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header fw-semibold text-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>Anomali Terdeteksi ({{ $reservation->anomalies->count() }})
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Tipe</th>
                            <th>Severity</th>
                            <th>Skor</th>
                            <th>Detail</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reservation->anomalies as $a)
                        <tr>
                            <td><code class="small">{{ $a->anomaly_type }}</code></td>
                            <td><span class="badge bg-{{ $a->severityBadge() }}">{{ $a->severity }}</span></td>
                            <td>+{{ $a->score_impact }}</td>
                            <td><small>{{ $a->detail }}</small></td>
                            <td>
                                @if($a->is_resolved)
                                    <span class="badge bg-success">Resolved</span>
                                @else
                                    <span class="badge bg-secondary">Pending</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    {{-- Right column: Location & metadata --}}
    <div class="col-lg-4">
        @if($reservation->latitude && $reservation->longitude)
        <div class="card mb-3">
            <div class="card-header fw-semibold">Lokasi GPS</div>
            <div class="card-body">
                <div class="small text-muted mb-1">Koordinat</div>
                <div class="fw-semibold mb-1">
                    {{ $reservation->latitude }}, {{ $reservation->longitude }}
                </div>
                @if($reservation->location_name)
                    <div class="small">{{ $reservation->location_name }}</div>
                @endif
                @if($reservation->is_danger_zone)
                    <div class="alert alert-danger py-2 small mt-2 mb-0">
                        <i class="bi bi-geo-alt-fill me-1"></i> Dalam Danger Zone
                    </div>
                @endif
                <div class="mt-2">
                    <a href="https://maps.google.com/?q={{ $reservation->latitude }},{{ $reservation->longitude }}"
                       target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-map me-1"></i> Lihat di Maps
                    </a>
                </div>
            </div>
        </div>
        @endif

        @if($reservation->partner)
        <div class="card mb-3">
            <div class="card-header fw-semibold">Partner</div>
            <div class="card-body">
                <div class="fw-semibold">{{ $reservation->partner->nama_partner }}</div>
                <div class="small text-muted mb-2">{{ $reservation->partner->category }}</div>
                <div class="d-flex gap-2">
                    <span class="badge bg-{{ $reservation->partner->fraudRiskBadge() }}">
                        Fraud: {{ $reservation->partner->fraudRiskLevel() }} ({{ $reservation->partner->fraud_score }})
                    </span>
                    @if($reservation->partner->reservation_suspended)
                        <span class="badge bg-danger">SUSPENDED</span>
                    @endif
                </div>
                <a href="{{ route('partners.show', $reservation->partner) }}" class="btn btn-sm btn-outline-secondary mt-2">
                    <i class="bi bi-arrow-right me-1"></i> Lihat Partner
                </a>
            </div>
        </div>
        @endif

        <div class="card mb-3">
            <div class="card-header fw-semibold">Metadata</div>
            <div class="card-body small">
                @if($reservation->ip_address)
                    <div class="mb-1"><span class="text-muted">IP:</span> {{ $reservation->ip_address }}</div>
                @endif
                @if($reservation->device_fingerprint)
                    <div class="mb-1"><span class="text-muted">Device:</span> <code>{{ $reservation->device_fingerprint }}</code></div>
                @endif
                @if($reservation->booking_pass_data)
                    <hr>
                    <div class="text-muted mb-1 fw-semibold">Custom Fields</div>
                    @foreach($reservation->booking_pass_data as $k => $v)
                        <div><span class="text-muted">{{ $k }}:</span> {{ $v }}</div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
