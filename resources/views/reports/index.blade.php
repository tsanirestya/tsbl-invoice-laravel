@extends('layouts.app')

@section('title', 'Laporan')
@section('page-title', 'Laporan Keuangan')

@section('content')

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-receipt"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Invoice</div>
                    <div class="fw-bold fs-5">{{ number_format($summary['total_invoice']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <div class="text-muted small">Revenue (PAID)</div>
                    <div class="fw-bold fs-6 text-success">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <div class="text-muted small">Outstanding</div>
                    <div class="fw-bold fs-6 text-warning">Rp {{ number_format($summary['total_outstanding'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div>
                    <div class="text-muted small">Overdue</div>
                    <div class="fw-bold fs-6 text-danger">Rp {{ number_format($summary['total_overdue'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Status count badges --}}
<div class="d-flex flex-wrap gap-2 mb-4">
    <span class="badge bg-success fs-6 px-3 py-2">PAID {{ $summary['count_paid'] }}</span>
    <span class="badge bg-secondary fs-6 px-3 py-2">UNPAID {{ $summary['count_unpaid'] }}</span>
    <span class="badge bg-warning text-dark fs-6 px-3 py-2">PARTIAL {{ $summary['count_partial'] }}</span>
    <span class="badge bg-danger fs-6 px-3 py-2">OVERDUE {{ $summary['count_overdue'] }}</span>
</div>

{{-- Filter + Export --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.index') }}" id="filter-form">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Dari Tanggal</label>
                    <input type="date" name="date_from" class="form-control form-control-sm"
                        value="{{ request('date_from') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Sampai</label>
                    <input type="date" name="date_to" class="form-control form-control-sm"
                        value="{{ request('date_to') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        @foreach(['PAID','UNPAID','PARTIAL','OVERDUE'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
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
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Cari</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                        placeholder="No, tamu, partner..." value="{{ request('search') }}">
                </div>
                <div class="col-6 col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x"></i>
                    </a>
                </div>
            </div>
        </form>
        <div class="d-flex gap-2 mt-3 pt-3 border-top">
            <span class="text-muted small me-auto">Export hasil filter saat ini:</span>
            <a href="{{ route('reports.export-csv', request()->query()) }}"
               class="btn btn-sm btn-outline-success">
                <i class="bi bi-filetype-csv me-1"></i> Export CSV
            </a>
            <a href="{{ route('reports.export-pdf', request()->query()) }}"
               class="btn btn-sm btn-outline-danger">
                <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
            </a>
        </div>
    </div>
</div>

{{-- Tabs --}}
<ul class="nav nav-tabs mb-3" id="reportTabs">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-invoices">
            Invoice List
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-partners">
            Per Partner
        </button>
    </li>
</ul>

<div class="tab-content">
    {{-- Invoice List --}}
    <div class="tab-pane fade show active" id="tab-invoices">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No Invoice</th>
                            <th>Partner</th>
                            <th class="d-none d-md-table-cell">Tamu</th>
                            <th>Tgl Invoice</th>
                            <th class="d-none d-md-table-cell">Jatuh Tempo</th>
                            <th class="text-end">Grand Total</th>
                            <th class="text-end d-none d-md-table-cell">Dibayar</th>
                            <th class="text-end d-none d-md-table-cell">Sisa</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $inv)
                        <tr>
                            <td>
                                <a href="{{ route('invoices.show', $inv) }}" class="fw-semibold text-decoration-none">
                                    {{ $inv->invoice_no }}
                                </a>
                                @if(!$inv->is_finalized)
                                    <span class="badge bg-secondary bg-opacity-50 text-dark ms-1" style="font-size:.65rem">DRAFT</span>
                                @endif
                            </td>
                            <td class="text-truncate" style="max-width:140px">{{ $inv->partner->nama_partner ?? '-' }}</td>
                            <td class="d-none d-md-table-cell text-truncate" style="max-width:120px">{{ $inv->guest_name ?? '-' }}</td>
                            <td>{{ $inv->invoice_date?->format('d/m/y') }}</td>
                            <td class="d-none d-md-table-cell {{ $inv->isOverdue() ? 'text-danger fw-semibold' : '' }}">
                                {{ $inv->due_date?->format('d/m/y') ?? '-' }}
                            </td>
                            <td class="text-end fw-semibold">{{ number_format($inv->grand_total, 0, ',', '.') }}</td>
                            <td class="text-end d-none d-md-table-cell text-success">{{ number_format($inv->totalPaid(), 0, ',', '.') }}</td>
                            <td class="text-end d-none d-md-table-cell {{ $inv->grand_total - $inv->totalPaid() > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format(max(0, $inv->grand_total - $inv->totalPaid()), 0, ',', '.') }}
                            </td>
                            <td>
                                <span class="badge badge-{{ strtolower($inv->payment_status) }}">
                                    {{ $inv->payment_status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                Tidak ada data dengan filter ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($invoices->count() > 0)
                    <tfoot class="table-light fw-semibold">
                        <tr>
                            <td colspan="5" class="d-none d-md-table-cell">Total (halaman ini)</td>
                            <td colspan="3" class="d-md-none">Total</td>
                            <td class="text-end">{{ number_format($invoices->sum('grand_total'), 0, ',', '.') }}</td>
                            <td class="text-end d-none d-md-table-cell text-success">{{ number_format($invoices->sum(fn($i) => $i->totalPaid()), 0, ',', '.') }}</td>
                            <td class="text-end d-none d-md-table-cell text-danger">{{ number_format($invoices->sum(fn($i) => max(0, $i->grand_total - $i->totalPaid())), 0, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
        <div class="mt-3">
            {{ $invoices->links() }}
        </div>
    </div>

    {{-- Partner Summary --}}
    <div class="tab-pane fade" id="tab-partners">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Partner</th>
                            <th class="text-center">Invoice</th>
                            <th class="text-end">Total Ditagihkan</th>
                            <th class="text-end">Total Dibayar</th>
                            <th class="text-end">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($partnerSummary as $i => $row)
                        <tr>
                            <td class="text-muted small">{{ $i + 1 }}</td>
                            <td class="fw-semibold">{{ $row->partner->nama_partner ?? 'Tanpa Partner' }}</td>
                            <td class="text-center">{{ $row->invoice_count }}</td>
                            <td class="text-end">{{ number_format($row->total_billed, 0, ',', '.') }}</td>
                            <td class="text-end text-success">{{ number_format($row->total_paid, 0, ',', '.') }}</td>
                            <td class="text-end {{ $row->total_outstanding > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                {{ number_format($row->total_outstanding, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Tidak ada data.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($partnerSummary->count() > 0)
                    <tfoot class="table-light fw-semibold">
                        <tr>
                            <td colspan="3">Total</td>
                            <td class="text-end">{{ number_format($partnerSummary->sum('total_billed'), 0, ',', '.') }}</td>
                            <td class="text-end text-success">{{ number_format($partnerSummary->sum('total_paid'), 0, ',', '.') }}</td>
                            <td class="text-end text-danger">{{ number_format($partnerSummary->sum('total_outstanding'), 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
