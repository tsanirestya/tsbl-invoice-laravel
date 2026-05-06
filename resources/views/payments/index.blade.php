@extends('layouts.app')
@section('title', 'Pembayaran')
@section('page-title', 'Checklist Pembayaran')

@section('content')

{{-- Summary Cards --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="text-muted small mb-1">Outstanding</div>
            <div class="fw-bold text-danger fs-6">Rp {{ number_format($summary['total_outstanding'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="text-muted small mb-1">Jatuh Tempo</div>
            <div class="fw-bold text-danger fs-6">{{ $summary['overdue_count'] }} invoice</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="text-muted small mb-1">Bayar Sebagian</div>
            <div class="fw-bold text-warning fs-6">{{ $summary['partial_count'] }} invoice</div>
        </div>
    </div>
</div>

{{-- Mark Overdue --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0 fw-semibold">Invoice Finalized</h6>
    <form action="{{ route('invoices.mark-overdue') }}" method="POST"
          onsubmit="return confirm('Update semua invoice UNPAID yang sudah melewati due date menjadi OVERDUE?')">
        @csrf
        <button class="btn btn-sm btn-outline-danger">
            <i class="bi bi-clock-history me-1"></i> Update Status Overdue
        </button>
    </form>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-sm-4">
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control form-control-sm" placeholder="Cari invoice / tamu / partner...">
            </div>
            <div class="col-6 col-sm-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    @foreach(['UNPAID','PARTIAL','OVERDUE','PAID'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-3">
                <select name="partner_id" class="form-select form-select-sm">
                    <option value="">Semua Partner</option>
                    @foreach($partners as $p)
                        <option value="{{ $p->id }}" {{ request('partner_id') == $p->id ? 'selected' : '' }}>{{ $p->nama_partner }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-2 d-flex gap-1">
                <button class="btn btn-primary btn-sm flex-fill">Filter</button>
                <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Invoice</th>
                        <th>Partner</th>
                        <th class="d-none d-md-table-cell">Due Date</th>
                        <th class="text-end">Total</th>
                        <th class="text-end d-none d-sm-table-cell">Terbayar</th>
                        <th class="text-end d-none d-sm-table-cell">Sisa</th>
                        <th class="text-center">Status</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                    @php
                        $paid     = $inv->totalPaid();
                        $sisa     = max(0, $inv->grand_total - $paid);
                        $statusCls = match($inv->payment_status) {
                            'PAID'    => 'badge-paid',
                            'PARTIAL' => 'badge-partial',
                            'OVERDUE' => 'badge-overdue',
                            default   => 'badge-unpaid',
                        };
                    @endphp
                    <tr>
                        <td class="ps-3">
                            <a href="{{ route('invoices.show', $inv) }}" class="fw-semibold text-decoration-none">
                                {{ $inv->invoice_no }}
                            </a>
                            @if($inv->guest_name)
                                <div class="text-muted" style="font-size:.75rem">{{ $inv->guest_name }}</div>
                            @endif
                        </td>
                        <td>{{ $inv->partner?->nama_partner ?? '-' }}</td>
                        <td class="d-none d-md-table-cell">
                            <span class="{{ $inv->isOverdue() && $inv->payment_status !== 'PAID' ? 'text-danger fw-semibold' : '' }}">
                                {{ $inv->due_date?->format('d/m/Y') ?? '-' }}
                            </span>
                        </td>
                        <td class="text-end">Rp {{ number_format($inv->grand_total, 0, ',', '.') }}</td>
                        <td class="text-end text-success d-none d-sm-table-cell">
                            {{ $paid > 0 ? 'Rp '.number_format($paid, 0, ',', '.') : '-' }}
                        </td>
                        <td class="text-end text-danger d-none d-sm-table-cell">
                            {{ $sisa > 0 ? 'Rp '.number_format($sisa, 0, ',', '.') : '-' }}
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $statusCls }} small">{{ $inv->payment_status }}</span>
                        </td>
                        <td class="text-center pe-3">
                            <a href="{{ route('invoices.show', $inv) }}" class="btn btn-xs btn-outline-primary px-2 py-1"
                               style="font-size:.75rem">
                                <i class="bi bi-cash-coin me-1"></i>Bayar
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Tidak ada invoice.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
        <div class="px-3 py-2 border-top">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>
</div>

@endsection
