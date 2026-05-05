@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- Stat Cards --}}
<div class="row g-3 mb-4">

    <div class="col-6 col-md-4 col-lg-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon bg-primary bg-opacity-10">
                    <i class="bi bi-file-earmark-text text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small">Total</div>
                    <div class="fw-bold fs-5">{{ number_format($stats['total']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon bg-secondary bg-opacity-10">
                    <i class="bi bi-clock text-secondary"></i>
                </div>
                <div>
                    <div class="text-muted small">Belum Bayar</div>
                    <div class="fw-bold fs-5">{{ number_format($stats['unpaid']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon" style="background:rgba(253,126,20,.1)">
                    <i class="bi bi-hourglass-split" style="color:#fd7e14"></i>
                </div>
                <div>
                    <div class="text-muted small">Partial</div>
                    <div class="fw-bold fs-5">{{ number_format($stats['partial']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon bg-success bg-opacity-10">
                    <i class="bi bi-check-circle text-success"></i>
                </div>
                <div>
                    <div class="text-muted small">Lunas</div>
                    <div class="fw-bold fs-5">{{ number_format($stats['paid']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon bg-danger bg-opacity-10">
                    <i class="bi bi-exclamation-triangle text-danger"></i>
                </div>
                <div>
                    <div class="text-muted small">Jatuh Tempo</div>
                    <div class="fw-bold fs-5 text-danger">{{ number_format($stats['overdue']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon bg-info bg-opacity-10">
                    <i class="bi bi-people text-info"></i>
                </div>
                <div>
                    <div class="text-muted small">Partner</div>
                    <div class="fw-bold fs-5">{{ number_format($totalPartners) }}</div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Revenue + Outstanding --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card stat-card border-start border-success border-4">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Pendapatan (Lunas)</div>
                        <div class="fw-bold fs-4 text-success">
                            Rp {{ number_format($stats['revenue'], 0, ',', '.') }}
                        </div>
                    </div>
                    <i class="bi bi-graph-up-arrow text-success" style="font-size:2rem;opacity:.4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card stat-card border-start border-warning border-4">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Outstanding Piutang</div>
                        <div class="fw-bold fs-4 text-warning">
                            Rp {{ number_format($stats['outstanding'], 0, ',', '.') }}
                        </div>
                    </div>
                    <i class="bi bi-wallet2 text-warning" style="font-size:2rem;opacity:.4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Recent Invoices --}}
<div class="card shadow-sm">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <span class="fw-semibold"><i class="bi bi-clock-history me-2 text-primary"></i>Invoice Terbaru</span>
        <a href="#" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">No. Invoice</th>
                        <th>Partner</th>
                        <th class="d-none d-md-table-cell">Tanggal</th>
                        <th class="d-none d-md-table-cell">Jatuh Tempo</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentInvoices as $inv)
                    <tr>
                        <td class="ps-3">
                            <a href="#" class="text-decoration-none fw-semibold">{{ $inv->invoice_no }}</a>
                        </td>
                        <td class="text-truncate" style="max-width:150px">{{ $inv->partner->nama_partner ?? '-' }}</td>
                        <td class="d-none d-md-table-cell text-muted small">
                            {{ $inv->invoice_date?->format('d/m/Y') }}
                        </td>
                        <td class="d-none d-md-table-cell text-muted small">
                            {{ $inv->due_date?->format('d/m/Y') }}
                        </td>
                        <td class="text-end fw-semibold">
                            Rp {{ number_format($inv->grand_total, 0, ',', '.') }}
                        </td>
                        <td class="text-center">
                            @php
                                $badge = match($inv->payment_status) {
                                    'PAID'    => 'badge-paid',
                                    'OVERDUE' => 'badge-overdue',
                                    'PARTIAL' => 'badge-partial',
                                    default   => 'badge-unpaid',
                                };
                                $label = match($inv->payment_status) {
                                    'PAID'    => 'Lunas',
                                    'OVERDUE' => 'Jatuh Tempo',
                                    'PARTIAL' => 'Partial',
                                    default   => 'Belum Bayar',
                                };
                            @endphp
                            <span class="badge {{ $badge }}">{{ $label }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Belum ada invoice
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
