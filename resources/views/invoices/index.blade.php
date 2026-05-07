@extends('layouts.app')
@section('title', 'Invoice')
@section('page-title', 'Invoice')

@push('styles')
<style>
    /* ── Summary Cards ── */
    .inv-stat-card {
        border: none; border-radius: 11px;
        padding: .9rem 1rem;
        display: flex; align-items: center; gap: .85rem;
        box-shadow: 0 1px 3px rgba(15,23,41,.07), 0 3px 12px rgba(15,23,41,.04);
        transition: transform .16s; position: relative; overflow: hidden;
    }
    .inv-stat-card:hover { transform: translateY(-2px); }
    .inv-stat-icon {
        width: 42px; height: 42px; border-radius: 11px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.15rem; flex-shrink: 0;
    }
    .inv-stat-label { font-size: .64rem; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; opacity: .75; margin-bottom: 1px; }
    .inv-stat-value { font-size: 1.35rem; font-weight: 800; line-height: 1.1; }
    .inv-stat-sub   { font-size: .7rem; margin-top: 1px; opacity: .62; }
    .inv-stat-bg-icon { position: absolute; right: -8px; bottom: -10px; font-size: 4.5rem; opacity: .07; pointer-events: none; }
    .inv-stat-card.blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); color:#fff; }
    .inv-stat-card.blue   .inv-stat-icon { background: rgba(255,255,255,.2); color:#fff; }
    .inv-stat-card.amber  { background: linear-gradient(135deg,#f59e0b,#d97706); color:#fff; }
    .inv-stat-card.amber  .inv-stat-icon { background: rgba(255,255,255,.2); color:#fff; }
    .inv-stat-card.red    { background: linear-gradient(135deg,#ef4444,#dc2626); color:#fff; }
    .inv-stat-card.red    .inv-stat-icon { background: rgba(255,255,255,.2); color:#fff; }
    .inv-stat-card.green  { background: linear-gradient(135deg,#10b981,#059669); color:#fff; }
    .inv-stat-card.green  .inv-stat-icon { background: rgba(255,255,255,.2); color:#fff; }

    /* ── Filter panel ── */
    .filter-panel {
        background: #fff; border-radius: 11px;
        padding: .9rem 1.1rem;
        box-shadow: 0 1px 3px rgba(15,23,41,.06);
        margin-bottom: 1rem;
    }
    .filter-panel .filter-label {
        font-size: .65rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .6px; color: #94a3b8; margin-bottom: .3rem; display: block;
    }
    .filter-panel .form-control,
    .filter-panel .form-select {
        border-radius: 8px; border-color: #e2e8f0;
        font-size: .82rem; padding: .38rem .7rem;
    }
    .filter-panel .form-control:focus,
    .filter-panel .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59,130,246,.1);
    }

    /* ── Invoice table wrapper ── */
    .inv-wrap {
        background: #fff; border-radius: 11px;
        box-shadow: 0 1px 3px rgba(15,23,41,.06); overflow: hidden;
    }
    .inv-table-hdr {
        padding: .8rem 1.1rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between;
    }

    /* ── Desktop table ── */
    .inv-wrap table thead th {
        background: #f8fafc; font-size: .65rem; font-weight: 700;
        letter-spacing: .55px; text-transform: uppercase; color: #64748b;
        border-bottom: 1px solid #f1f5f9; padding: .62rem 1rem; white-space: nowrap;
    }
    .inv-wrap table tbody td {
        padding: .65rem 1rem; font-size: .83rem;
        border-bottom: 1px solid #f8fafc; vertical-align: middle;
    }
    .inv-wrap table tbody tr:last-child td { border-bottom: none; }
    .inv-wrap table tbody tr:hover { background: #fafbff; }
    .inv-no-link { font-weight: 700; color: #1e40af; text-decoration: none; }
    .inv-no-link:hover { color: #3b82f6; text-decoration: underline; }

    /* ── Action buttons ── */
    .inv-actions { display: flex; gap: .3rem; justify-content: center; }
    .inv-actions .btn-action {
        width: 28px; height: 28px; border-radius: 7px;
        border: 1px solid #e2e8f0; background: #fff; color: #64748b;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .78rem; text-decoration: none;
        transition: background .13s, color .13s, border-color .13s;
    }
    .inv-actions .btn-action.view:hover  { background: #eff6ff; color: #3b82f6; border-color: #bfdbfe; }
    .inv-actions .btn-action.pdf:hover   { background: #fef2f2; color: #ef4444; border-color: #fecaca; }
    .inv-actions .btn-action.edit:hover  { background: #fefce8; color: #ca8a04; border-color: #fde68a; }
    .inv-actions .btn-action.del:hover   { background: #fef2f2; color: #dc2626; border-color: #fca5a5; }
    .inv-actions .btn-action.finalize:hover { background: #f0fdf4; color: #16a34a; border-color: #86efac; }
    .lock-badge {
        display: inline-flex; align-items: center; gap: .15rem;
        background: #f1f5f9; color: #64748b;
        font-size: .6rem; font-weight: 600;
        border-radius: 4px; padding: .1em .3em; vertical-align: middle;
    }

    /* ── Empty ── */
    .inv-empty { padding: 3rem 1rem; text-align: center; color: #94a3b8; }
    .inv-empty i { font-size: 3rem; opacity: .3; display: block; margin-bottom: .6rem; }

    /* ── Mobile card list ── */
    .inv-mobile-item {
        padding: .9rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        display: block; text-decoration: none; color: inherit;
    }
    .inv-mobile-item:last-child { border-bottom: none; }
    .inv-mobile-item:active { background: #fafbff; }

    @media (max-width: 767.98px) {
        .inv-stat-value { font-size: 1.15rem; }
        .inv-stat-sub { display: none; }
    }
</style>
@endpush

@section('content')

{{-- ── Header ── --}}
<div class="d-flex justify-content-between align-items-center mb-3 page-hdr">
    <div>
        <div class="page-title">Daftar Invoice</div>
        <div class="page-sub">Kelola semua invoice</div>
    </div>
    <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1" style="border-radius:9px;">
        <i class="bi bi-plus-lg"></i>
        <span class="d-none d-sm-inline">Buat Invoice</span>
    </a>
</div>

{{-- ── Summary Stats ── --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-lg-3">
        <div class="inv-stat-card blue">
            <div class="inv-stat-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
            <div>
                <div class="inv-stat-label">Total</div>
                <div class="inv-stat-value">{{ $stats['total'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="inv-stat-card amber">
            <div class="inv-stat-icon"><i class="bi bi-clock-fill"></i></div>
            <div>
                <div class="inv-stat-label">Belum Bayar</div>
                <div class="inv-stat-value">{{ $stats['unpaid'] }}</div>
                <div class="inv-stat-sub">Rp {{ number_format($stats['unpaid_amount'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="inv-stat-card red">
            <div class="inv-stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div>
                <div class="inv-stat-label">Overdue</div>
                <div class="inv-stat-value">{{ $stats['overdue'] }}</div>
                <div class="inv-stat-sub">Rp {{ number_format($stats['overdue_amount'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="inv-stat-card green">
            <div class="inv-stat-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="inv-stat-label">Lunas</div>
                <div class="inv-stat-value">{{ $stats['paid'] }}</div>
                <div class="inv-stat-sub">Rp {{ number_format($stats['paid_amount'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Filter Panel ── --}}
<div class="filter-panel">
    <form method="GET" action="{{ route('invoices.index') }}" id="filter-form">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-sm-6 col-lg-3">
                <label class="filter-label">Pencarian</label>
                <input type="text" name="search" class="form-control"
                       placeholder="No invoice / tamu / partner"
                       value="{{ request('search') }}">
            </div>
            <div class="col-6 col-sm-3 col-lg-2">
                <label class="filter-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    @foreach(['UNPAID','PARTIAL','PAID','OVERDUE'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-3 col-lg-3">
                <label class="filter-label">Partner</label>
                <select name="partner_id" class="form-select">
                    <option value="">Semua</option>
                    @foreach($partners as $p)
                        <option value="{{ $p->id }}" @selected(request('partner_id') == $p->id)>{{ $p->nama_partner }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-3 col-lg-2">
                <label class="filter-label">Dari</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-6 col-sm-3 col-lg-2">
                <label class="filter-label">Sampai</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm" style="border-radius:8px;padding:.38rem .85rem;">
                    <i class="bi bi-search"></i>
                </button>
                @if(request()->hasAny(['search','status','partner_id','date_from','date_to']))
                <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;padding:.38rem .85rem;" title="Reset">
                    <i class="bi bi-x-lg"></i>
                </a>
                @endif
            </div>
        </div>
    </form>
</div>

{{-- ── List ── --}}
<div class="inv-wrap">
    <div class="inv-table-hdr">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-file-earmark-text" style="color:#3b82f6"></i>
            <span class="fw-semibold" style="font-size:.86rem;">Invoice</span>
            @if(request()->hasAny(['search','status','partner_id','date_from','date_to']))
                <span class="badge" style="background:#eff6ff;color:#3b82f6;font-size:.65rem;">Filter Aktif</span>
            @endif
        </div>
        <span style="font-size:.73rem;color:#94a3b8;">{{ $invoices->total() }} data</span>
    </div>

    @if($invoices->isEmpty())
        <div class="inv-empty">
            <i class="bi bi-inbox"></i>
            <p class="fw-semibold mb-1" style="color:#64748b;">Tidak ada invoice</p>
            <p style="font-size:.85rem;">
                @if(request()->hasAny(['search','status','partner_id','date_from','date_to']))
                    Coba ubah filter pencarian.
                @else
                    <a href="{{ route('invoices.create') }}">Buat invoice pertama</a>
                @endif
            </p>
        </div>
    @else

    {{-- Desktop Table --}}
    <div class="d-none d-md-block">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th class="ps-4">No Invoice</th>
                    <th>Partner</th>
                    <th>Tamu</th>
                    <th>Tgl Invoice</th>
                    <th class="text-end">Total</th>
                    <th class="text-center">Status</th>
                    <th class="text-center pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $inv)
                @php
                    $status = $inv->payment_status;
                    $cls = match($status) {
                        'PAID'    => 'badge-paid',
                        'PARTIAL' => 'badge-partial',
                        'OVERDUE' => 'badge-overdue',
                        default   => 'badge-unpaid',
                    };
                    $label = match($status) {
                        'PAID'    => 'Lunas',
                        'PARTIAL' => 'Partial',
                        'OVERDUE' => 'Jatuh Tempo',
                        default   => 'Belum Bayar',
                    };
                    $daysUntilDue = $inv->due_date ? now()->diffInDays($inv->due_date, false) : null;
                    $dueCls = $inv->isOverdue() ? 'text-danger fw-bold' : (($daysUntilDue !== null && $daysUntilDue <= 3) ? 'text-warning fw-semibold' : '');
                @endphp
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('invoices.show', $inv) }}" class="inv-no-link">{{ $inv->invoice_no }}</a>
                            @if($inv->is_finalized)
                                <span class="lock-badge"><i class="bi bi-lock-fill"></i> Final</span>
                            @endif
                        </div>
                    </td>
                    <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#475569;">
                        {{ $inv->partner?->nama_partner ?? '-' }}
                    </td>
                    <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#64748b;">
                        {{ $inv->guest_name ?? '-' }}
                    </td>
                    <td style="color:#64748b;white-space:nowrap;">{{ $inv->invoice_date?->format('d/m/Y') }}</td>
                    <td class="text-end" style="font-weight:700;color:#1e293b;white-space:nowrap;">
                        Rp {{ number_format($inv->grand_total, 0, ',', '.') }}
                    </td>
                    <td class="text-center"><span class="badge {{ $cls }}">{{ $label }}</span></td>
                    <td class="text-center pe-3">
                        <div class="inv-actions">
                            <a href="{{ route('invoices.show', $inv) }}" class="btn-action view" title="Detail"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('invoices.pdf', $inv) }}" target="_blank" class="btn-action pdf" title="PDF"><i class="bi bi-file-pdf"></i></a>
                            @if(!$inv->is_finalized)
                            <a href="{{ route('invoices.edit', $inv) }}" class="btn-action edit" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('invoices.finalize', $inv) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Finalisasi {{ $inv->invoice_no }}? Tidak bisa diedit setelah ini.')">
                                @csrf
                                <button type="submit" class="btn-action finalize" title="Finalisasi"><i class="bi bi-check-circle"></i></button>
                            </form>
                            <form action="{{ route('invoices.destroy', $inv) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Hapus {{ $inv->invoice_no }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-action del" title="Hapus"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile Card List --}}
    <div class="d-md-none">
        @foreach($invoices as $inv)
        @php
            $status = $inv->payment_status;
            $cls = match($status) { 'PAID'=>'badge-paid','PARTIAL'=>'badge-partial','OVERDUE'=>'badge-overdue',default=>'badge-unpaid' };
            $label = match($status) { 'PAID'=>'Lunas','PARTIAL'=>'Partial','OVERDUE'=>'Jatuh Tempo',default=>'Belum Bayar' };
        @endphp
        <div class="inv-mobile-item">
            <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                <div>
                    <a href="{{ route('invoices.show', $inv) }}" class="inv-no-link">{{ $inv->invoice_no }}</a>
                    @if($inv->is_finalized)
                        <span class="lock-badge ms-1"><i class="bi bi-lock-fill"></i></span>
                    @endif
                </div>
                <span class="badge {{ $cls }}">{{ $label }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div style="font-size:.78rem;color:#475569;">{{ $inv->partner?->nama_partner ?? '-' }}</div>
                    <div style="font-size:.73rem;color:#94a3b8;">{{ $inv->invoice_date?->format('d/m/Y') }}{{ $inv->guest_name ? ' · '.$inv->guest_name : '' }}</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span style="font-weight:700;font-size:.84rem;">Rp {{ number_format($inv->grand_total, 0, ',', '.') }}</span>
                    <div class="d-flex gap-1">
                        <a href="{{ route('invoices.show', $inv) }}" class="btn-action view" title="Detail"><i class="bi bi-eye"></i></a>
                        @if(!$inv->is_finalized)
                        <a href="{{ route('invoices.edit', $inv) }}" class="btn-action edit" title="Edit"><i class="bi bi-pencil"></i></a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($invoices->hasPages())
    <div class="px-4 py-3" style="border-top:1px solid #f1f5f9;">
        {{ $invoices->links() }}
    </div>
    @endif
    @endif
</div>

@endsection

@push('scripts')
<script>
document.querySelectorAll('#filter-form select').forEach(function(sel) {
    sel.addEventListener('change', function() {
        document.getElementById('filter-form').submit();
    });
});
</script>
@endpush
