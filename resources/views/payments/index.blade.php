@extends('layouts.app')
@section('title', 'Pembayaran')
@section('page-title', 'Checklist Pembayaran')

@push('styles')
<style>
    .pay-list-item {
        padding: .85rem 1rem; border-bottom: 1px solid #f1f5f9;
        text-decoration: none; color: inherit; display: block;
    }
    .pay-list-item:last-child { border-bottom: none; }
    .pay-list-item:hover { background: #fafbff; }
</style>
@endpush

@section('content')

{{-- Summary Cards --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-md-4">
        <div class="card card-clean text-center py-3">
            <div class="text-muted mb-1" style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Outstanding</div>
            <div class="fw-bold text-danger" style="font-size:.95rem;">Rp {{ number_format($summary['total_outstanding'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card card-clean text-center py-3">
            <div class="text-muted mb-1" style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Jatuh Tempo</div>
            <div class="fw-bold text-danger" style="font-size:.95rem;">{{ $summary['overdue_count'] }} invoice</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card card-clean text-center py-3">
            <div class="text-muted mb-1" style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Bayar Sebagian</div>
            <div class="fw-bold text-warning" style="font-size:.95rem;">{{ $summary['partial_count'] }} invoice</div>
        </div>
    </div>
</div>

{{-- Header + Action --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 page-hdr">
    <div class="page-title">Invoice Finalized</div>
    <form action="{{ route('invoices.mark-overdue') }}" method="POST"
          onsubmit="return confirm('Update semua invoice UNPAID yang sudah melewati due date menjadi OVERDUE?')">
        @csrf
        <button class="btn btn-sm btn-outline-danger" style="border-radius:8px;">
            <i class="bi bi-clock-history me-1"></i>
            <span class="d-none d-sm-inline">Update </span>Overdue
        </button>
    </form>
</div>

{{-- Filters --}}
<div class="filter-panel mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-12 col-sm-4">
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control form-control-sm" placeholder="Cari invoice / tamu / partner..."
                   style="border-radius:8px;border-color:#e2e8f0;font-size:.82rem;">
        </div>
        <div class="col-6 col-sm-3">
            <select name="status" class="form-select form-select-sm" style="border-radius:8px;border-color:#e2e8f0;font-size:.82rem;">
                <option value="">Semua Status</option>
                @foreach(['UNPAID','PARTIAL','OVERDUE','PAID'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-sm-3">
            <select name="partner_id" class="form-select form-select-sm" style="border-radius:8px;border-color:#e2e8f0;font-size:.82rem;">
                <option value="">Semua Partner</option>
                @foreach($partners as $p)
                    <option value="{{ $p->id }}" {{ request('partner_id') == $p->id ? 'selected' : '' }}>{{ $p->nama_partner }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-sm-2 d-flex gap-2">
            <button class="btn btn-primary btn-sm flex-fill" style="border-radius:8px;">Filter</button>
            <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;">Reset</a>
        </div>
    </form>
</div>

{{-- List --}}
<div class="card card-clean overflow-hidden">

    {{-- Desktop table --}}
    <div class="d-none d-sm-block">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th style="background:#f8fafc;font-size:.65rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#64748b;padding:.65rem 1rem;">Invoice</th>
                    <th style="background:#f8fafc;font-size:.65rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#64748b;padding:.65rem 1rem;">Partner</th>
                    <th class="d-none d-md-table-cell" style="background:#f8fafc;font-size:.65rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#64748b;padding:.65rem 1rem;">Due Date</th>
                    <th class="text-end" style="background:#f8fafc;font-size:.65rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#64748b;padding:.65rem 1rem;">Total</th>
                    <th class="text-end" style="background:#f8fafc;font-size:.65rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#64748b;padding:.65rem 1rem;">Sisa</th>
                    <th class="text-center" style="background:#f8fafc;font-size:.65rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#64748b;padding:.65rem 1rem;">Status</th>
                    <th style="background:#f8fafc;padding:.65rem 1rem;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                @php
                    $paid     = $inv->totalPaid();
                    $sisa     = max(0, $inv->grand_total - $paid);
                    $statusCls = match($inv->payment_status) { 'PAID'=>'badge-paid','PARTIAL'=>'badge-partial','OVERDUE'=>'badge-overdue',default=>'badge-unpaid' };
                    $statusLbl = match($inv->payment_status) { 'PAID'=>'Lunas','PARTIAL'=>'Partial','OVERDUE'=>'Jatuh Tempo',default=>'Belum Bayar' };
                @endphp
                <tr>
                    <td style="padding:.65rem 1rem;font-size:.83rem;border-bottom:1px solid #f8fafc;vertical-align:middle;">
                        <a href="{{ route('invoices.show', $inv) }}" class="fw-semibold text-decoration-none" style="color:#1e40af;">{{ $inv->invoice_no }}</a>
                        @if($inv->guest_name)
                            <div class="text-muted" style="font-size:.74rem;">{{ $inv->guest_name }}</div>
                        @endif
                    </td>
                    <td style="padding:.65rem 1rem;font-size:.83rem;border-bottom:1px solid #f8fafc;vertical-align:middle;color:#64748b;">{{ $inv->partner?->nama_partner ?? '-' }}</td>
                    <td class="d-none d-md-table-cell" style="padding:.65rem 1rem;font-size:.83rem;border-bottom:1px solid #f8fafc;vertical-align:middle;">
                        <span class="{{ $inv->isOverdue() && $inv->payment_status !== 'PAID' ? 'text-danger fw-semibold' : 'text-muted' }}">
                            {{ $inv->due_date?->format('d/m/Y') ?? '-' }}
                        </span>
                    </td>
                    <td class="text-end" style="padding:.65rem 1rem;font-size:.83rem;border-bottom:1px solid #f8fafc;vertical-align:middle;font-weight:600;">Rp {{ number_format($inv->grand_total, 0, ',', '.') }}</td>
                    <td class="text-end" style="padding:.65rem 1rem;font-size:.83rem;border-bottom:1px solid #f8fafc;vertical-align:middle;color:#dc2626;font-weight:600;">
                        {{ $sisa > 0 ? 'Rp '.number_format($sisa, 0, ',', '.') : '-' }}
                    </td>
                    <td class="text-center" style="padding:.65rem 1rem;border-bottom:1px solid #f8fafc;vertical-align:middle;">
                        <span class="badge {{ $statusCls }}">{{ $statusLbl }}</span>
                    </td>
                    <td style="padding:.65rem 1rem;border-bottom:1px solid #f8fafc;vertical-align:middle;">
                        <a href="{{ route('invoices.show', $inv) }}" class="btn btn-sm btn-outline-primary" style="border-radius:7px;font-size:.75rem;padding:.2rem .6rem;">
                            <i class="bi bi-cash-coin me-1"></i>Bayar
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-40"></i>
                        Tidak ada invoice.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile card list --}}
    <div class="d-sm-none">
        @forelse($invoices as $inv)
        @php
            $paid  = $inv->totalPaid();
            $sisa  = max(0, $inv->grand_total - $paid);
            $statusCls = match($inv->payment_status) { 'PAID'=>'badge-paid','PARTIAL'=>'badge-partial','OVERDUE'=>'badge-overdue',default=>'badge-unpaid' };
            $statusLbl = match($inv->payment_status) { 'PAID'=>'Lunas','PARTIAL'=>'Partial','OVERDUE'=>'Jatuh Tempo',default=>'Belum Bayar' };
        @endphp
        <div class="pay-list-item">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div>
                    <a href="{{ route('invoices.show', $inv) }}" class="fw-bold text-decoration-none" style="color:#1e40af;font-size:.87rem;">{{ $inv->invoice_no }}</a>
                    <div style="font-size:.76rem;color:#64748b;">{{ $inv->partner?->nama_partner ?? '-' }}{{ $inv->guest_name ? ' · '.$inv->guest_name : '' }}</div>
                </div>
                <span class="badge {{ $statusCls }}">{{ $statusLbl }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <div style="font-size:.76rem;color:#64748b;">
                    @if($inv->due_date)
                        Jatuh tempo: <span class="{{ $inv->isOverdue() && $inv->payment_status !== 'PAID' ? 'text-danger fw-semibold' : '' }}">{{ $inv->due_date->format('d/m/Y') }}</span>
                    @endif
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if($sisa > 0)
                    <span class="text-danger fw-bold" style="font-size:.82rem;">Rp {{ number_format($sisa, 0, ',', '.') }}</span>
                    @endif
                    <a href="{{ route('invoices.show', $inv) }}" class="btn btn-sm btn-outline-primary" style="border-radius:7px;font-size:.72rem;padding:.18rem .5rem;">
                        <i class="bi bi-cash-coin"></i>
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-2 d-block mb-2 opacity-40"></i>
            Tidak ada invoice.
        </div>
        @endforelse
    </div>

    @if($invoices->hasPages())
    <div class="px-4 py-3" style="border-top:1px solid #f1f5f9;">
        {{ $invoices->links() }}
    </div>
    @endif
</div>

@endsection
