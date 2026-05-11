@extends('layouts.app')
@section('title', 'Rekonsiliasi')
@section('page-title', 'Reconciliation Queue')

@push('styles')
<style>
    .rec-wrap { background:#fff; border-radius:11px; box-shadow:0 1px 3px rgba(15,23,41,.06); overflow:hidden; }
    .rec-table-hdr { padding:.8rem 1.1rem; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; }
    .rec-wrap table thead th { background:#f8fafc; font-size:.65rem; font-weight:700; letter-spacing:.55px; text-transform:uppercase; color:#64748b; border-bottom:1px solid #f1f5f9; padding:.62rem 1rem; white-space:nowrap; }
    .rec-wrap table tbody td { padding:.65rem 1rem; font-size:.83rem; border-bottom:1px solid #f8fafc; vertical-align:middle; }
    .rec-wrap table tbody tr:last-child td { border-bottom:none; }
    .rec-wrap table tbody tr:hover { background:#fafbff; }

    .rs-pending_review { background:#eff6ff; color:#1d4ed8; }
    .rs-approved       { background:#f0fdf4; color:#166534; }
    .rs-disputed       { background:#fff7ed; color:#c2410c; }
    .rs-rejected       { background:#fef2f2; color:#991b1b; }

    .delta-pos { color:#dc2626; font-weight:700; }
    .delta-neg { color:#059669; font-weight:700; }
    .delta-zero { color:#94a3b8; }
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 page-hdr">
    <div>
        <div class="page-title">Rekonsiliasi</div>
        <div class="page-sub">Review perbandingan Proforma vs DSI</div>
    </div>
</div>

<div class="filter-panel mb-3" style="background:#fff; border-radius:11px; padding:.9rem 1.1rem; box-shadow:0 1px 3px rgba(15,23,41,.06);">
    <form method="GET" action="{{ route('reconciliations.index') }}" id="rec-filter-form">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="filter-label small fw-bold text-muted text-uppercase mb-1 d-block">Pencarian</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="No Invoice / Ref DSI" value="{{ request('search') }}" style="border-radius:8px;">
            </div>
            <div class="col-md-3">
                <label class="filter-label small fw-bold text-muted text-uppercase mb-1 d-block">Status</label>
                <select name="status" class="form-select form-select-sm" style="border-radius:8px;">
                    <option value="">Semua</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm" style="border-radius:8px;"><i class="bi bi-search"></i></button>
            </div>
        </div>
    </form>
</div>

<div class="rec-wrap">
    <div class="rec-table-hdr">
        <span class="fw-semibold" style="font-size:.86rem;">Antrian Rekonsiliasi</span>
        <span style="font-size:.73rem;color:#94a3b8;">{{ $reconciliations->total() }} data</span>
    </div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th class="ps-4">No</th>
                    <th>Partner</th>
                    <th>Proforma</th>
                    <th>DSI Transaction</th>
                    <th class="text-end">Proforma Amt</th>
                    <th class="text-end">DSI Amt</th>
                    <th class="text-end">Delta</th>
                    <th class="text-center">Status</th>
                    <th class="text-center pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reconciliations as $rec)
                @php $sCls = 'rs-' . strtolower($rec->status); @endphp
                <tr>
                    <td class="ps-4 text-muted">#{{ $rec->id }}</td>
                    <td style="max-width:140px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $rec->proformaInvoice?->partner?->name ?? '-' }}</td>
                    <td>
                        @if($rec->proformaInvoice)
                            <a href="{{ route('billing-invoices.show', $rec->proformaInvoice) }}" class="text-decoration-none fw-bold">{{ $rec->proformaInvoice->invoice_no }}</a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if($rec->dsiTransaction)
                            <span class="fw-bold">{{ $rec->dsiTransaction->ref_no }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-end fw-bold">Rp {{ number_format($rec->proforma_amount, 0, ',', '.') }}</td>
                    <td class="text-end fw-bold">Rp {{ number_format($rec->dsi_amount, 0, ',', '.') }}</td>
                    <td class="text-end">
                        @if($rec->delta_amount > 0)
                            <span class="delta-pos">+Rp {{ number_format($rec->delta_amount, 0, ',', '.') }}</span>
                        @elseif($rec->delta_amount < 0)
                            <span class="delta-neg">-Rp {{ number_format(abs($rec->delta_amount), 0, ',', '.') }}</span>
                        @else
                            <span class="delta-zero">Rp 0</span>
                        @endif
                    </td>
                    <td class="text-center"><span class="badge {{ $sCls }}">{{ $rec->status }}</span></td>
                    <td class="text-center pe-3">
                        <a href="{{ route('reconciliations.show', $rec) }}" class="btn btn-sm btn-outline-primary" style="border-radius:7px;"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($reconciliations->hasPages())
    <div class="px-4 py-3 border-top">
        {{ $reconciliations->links() }}
    </div>
    @endif
</div>

@endsection
