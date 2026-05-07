@extends('layouts.app')
@section('title', 'Invoice Deposit')
@section('page-title', 'Invoice Deposit')

@push('styles')
<style>
    .di-list-item { padding: .85rem 1rem; border-bottom: 1px solid #f1f5f9; }
    .di-list-item:last-child { border-bottom: none; }
    .filter-panel .form-control,
    .filter-panel .form-select {
        border-radius: 8px; border-color: #e2e8f0; font-size: .82rem; padding: .38rem .7rem;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 page-hdr">
    <div>
        <div class="page-title">Invoice Deposit</div>
        <div class="page-sub">Daftar invoice permintaan deposit</div>
    </div>
    <a href="{{ route('deposit-invoices.create') }}" class="btn btn-primary btn-sm" style="border-radius:9px;">
        <i class="bi bi-plus-circle me-1"></i>
        <span class="d-none d-sm-inline">Buat Invoice Deposit</span>
    </a>
</div>

{{-- Filter --}}
<div class="filter-panel mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-12 col-sm-4">
            <input type="text" name="search" class="form-control form-control-sm"
                   placeholder="Cari no. invoice / partner…" value="{{ request('search') }}">
        </div>
        <div class="col-6 col-sm-3">
            <select name="status" class="form-select form-select-sm">
                <option value="">Semua Status</option>
                @foreach(['DRAFT','SENT','PAID','CANCELLED'] as $s)
                    <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-sm-3">
            <select name="partner_id" class="form-select form-select-sm ts-partner-filter">
                <option value="">Semua Partner</option>
                @foreach($partners as $p)
                    <option value="{{ $p->id }}" {{ request('partner_id') == $p->id ? 'selected' : '' }}>
                        {{ $p->nama_partner }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-sm-2">
            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
        </div>
        <div class="col-6 col-sm-2">
            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
        </div>
        <div class="col-auto d-flex gap-1">
            <button type="submit" class="btn btn-primary btn-sm" style="border-radius:8px;padding:.38rem .8rem;"><i class="bi bi-search"></i></button>
            <a href="{{ route('deposit-invoices.index') }}" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;padding:.38rem .8rem;"><i class="bi bi-x-lg"></i></a>
        </div>
    </form>
</div>

{{-- List --}}
<div class="card card-clean overflow-hidden">
    @if($depositInvoices->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-wallet2 fs-1 d-block mb-2 opacity-40"></i>
            Belum ada invoice deposit.
            <div class="mt-2">
                <a href="{{ route('deposit-invoices.create') }}" class="btn btn-primary btn-sm" style="border-radius:8px;">Buat Sekarang</a>
            </div>
        </div>
    @else

    {{-- Desktop table --}}
    <div class="d-none d-md-block">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th class="ps-3" style="background:#f8fafc;font-size:.65rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#64748b;padding:.65rem 1rem;">No. Invoice</th>
                    <th style="background:#f8fafc;font-size:.65rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#64748b;padding:.65rem 1rem;">Partner</th>
                    <th style="background:#f8fafc;font-size:.65rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#64748b;padding:.65rem 1rem;">Tgl Invoice</th>
                    <th style="background:#f8fafc;font-size:.65rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#64748b;padding:.65rem 1rem;">Jatuh Tempo</th>
                    <th class="text-end" style="background:#f8fafc;font-size:.65rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#64748b;padding:.65rem 1rem;">Jumlah</th>
                    <th class="text-center" style="background:#f8fafc;font-size:.65rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#64748b;padding:.65rem 1rem;">Status</th>
                    <th class="text-end pe-3" style="background:#f8fafc;padding:.65rem 1rem;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($depositInvoices as $di)
                @php
                    $statusClass = match($di->status) { 'PAID'=>'badge-paid','SENT'=>'bg-info text-dark','CANCELLED'=>'bg-secondary',default=>'bg-warning text-dark' };
                @endphp
                <tr>
                    <td class="ps-3" style="padding:.65rem 1rem;font-size:.83rem;border-bottom:1px solid #f8fafc;vertical-align:middle;font-weight:600;">
                        <a href="{{ route('deposit-invoices.show', $di) }}" class="text-decoration-none" style="color:#1e40af;">{{ $di->invoice_no }}</a>
                        @if(!$di->is_finalized)
                            <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem;">Draft</span>
                        @endif
                    </td>
                    <td style="padding:.65rem 1rem;font-size:.83rem;border-bottom:1px solid #f8fafc;vertical-align:middle;color:#64748b;">{{ $di->partner?->nama_partner ?? '-' }}</td>
                    <td style="padding:.65rem 1rem;font-size:.83rem;border-bottom:1px solid #f8fafc;vertical-align:middle;color:#94a3b8;">{{ $di->invoice_date?->format('d/m/Y') }}</td>
                    <td style="padding:.65rem 1rem;font-size:.83rem;border-bottom:1px solid #f8fafc;vertical-align:middle;color:#94a3b8;">{{ $di->due_date?->format('d/m/Y') ?? '-' }}</td>
                    <td class="text-end" style="padding:.65rem 1rem;font-size:.83rem;border-bottom:1px solid #f8fafc;vertical-align:middle;font-weight:700;">Rp {{ number_format($di->amount, 0, ',', '.') }}</td>
                    <td class="text-center" style="padding:.65rem 1rem;border-bottom:1px solid #f8fafc;vertical-align:middle;">
                        <span class="badge {{ $statusClass }}" style="font-size:.68rem;">{{ $di->status }}</span>
                    </td>
                    <td class="text-end pe-3" style="padding:.65rem 1rem;border-bottom:1px solid #f8fafc;vertical-align:middle;">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('deposit-invoices.show', $di) }}" class="btn btn-sm btn-outline-primary" style="border-radius:7px;padding:.2rem .55rem;font-size:.76rem;" title="Lihat">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('deposit-invoices.pdf', $di) }}" target="_blank" class="btn btn-sm btn-outline-danger" style="border-radius:7px;padding:.2rem .55rem;font-size:.76rem;" title="PDF">
                                <i class="bi bi-file-pdf"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile card list --}}
    <div class="d-md-none">
        @foreach($depositInvoices as $di)
        @php
            $statusClass = match($di->status) { 'PAID'=>'badge-paid','SENT'=>'bg-info text-dark','CANCELLED'=>'bg-secondary',default=>'bg-warning text-dark' };
        @endphp
        <div class="di-list-item">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                <div>
                    <a href="{{ route('deposit-invoices.show', $di) }}" class="fw-bold text-decoration-none" style="color:#1e40af;font-size:.87rem;">{{ $di->invoice_no }}</a>
                    @if(!$di->is_finalized)
                        <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem;">Draft</span>
                    @endif
                    <div style="font-size:.76rem;color:#64748b;">{{ $di->partner?->nama_partner ?? '-' }}</div>
                </div>
                <span class="badge {{ $statusClass }}" style="font-size:.65rem;flex-shrink:0;">{{ $di->status }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <div style="font-size:.75rem;color:#94a3b8;">
                    {{ $di->invoice_date?->format('d/m/Y') }}
                    @if($di->due_date) · JT: {{ $di->due_date->format('d/m/Y') }} @endif
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold" style="font-size:.84rem;">Rp {{ number_format($di->amount, 0, ',', '.') }}</span>
                    <div class="d-flex gap-1">
                        <a href="{{ route('deposit-invoices.show', $di) }}" class="btn btn-sm btn-outline-primary" style="border-radius:7px;padding:.2rem .5rem;font-size:.76rem;">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('deposit-invoices.pdf', $di) }}" target="_blank" class="btn btn-sm btn-outline-danger" style="border-radius:7px;padding:.2rem .5rem;font-size:.76rem;">
                            <i class="bi bi-file-pdf"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="px-3 py-2" style="border-top:1px solid #f1f5f9;">
        {{ $depositInvoices->links() }}
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
    document.querySelectorAll('.ts-partner-filter').forEach(el => {
        new TomSelect(el, { allowEmptyOption: true, maxOptions: 200 });
    });
</script>
@endpush
