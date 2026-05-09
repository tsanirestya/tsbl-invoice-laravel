@extends('layouts.app')
@section('title', 'Billing Invoice')
@section('page-title', 'Billing Invoice')

@push('styles')
<style>
.bi-stat{border:none;border-radius:11px;padding:.9rem 1rem;display:flex;align-items:center;gap:.85rem;box-shadow:0 1px 3px rgba(15,23,41,.07),0 3px 12px rgba(15,23,41,.04);transition:transform .16s;position:relative;overflow:hidden;color:#fff}
.bi-stat:hover{transform:translateY(-2px)}
.bi-stat-icon{width:42px;height:42px;border-radius:11px;background:rgba(255,255,255,.2);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.15rem;flex-shrink:0}
.bi-stat-label{font-size:.64rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;opacity:.75;margin-bottom:1px}
.bi-stat-value{font-size:1.35rem;font-weight:800;line-height:1.1}
.bi-stat-sub{font-size:.7rem;margin-top:1px;opacity:.7}
.bg-icon-abs{position:absolute;right:-8px;bottom:-10px;font-size:4.5rem;opacity:.07;pointer-events:none}
.c-blue{background:linear-gradient(135deg,#3b82f6,#2563eb)}
.c-amber{background:linear-gradient(135deg,#f59e0b,#d97706)}
.c-red{background:linear-gradient(135deg,#ef4444,#dc2626)}
.c-green{background:linear-gradient(135deg,#10b981,#059669)}
.c-purple{background:linear-gradient(135deg,#8b5cf6,#7c3aed)}
.c-slate{background:linear-gradient(135deg,#64748b,#475569)}
.c-cyan{background:linear-gradient(135deg,#06b6d4,#0891b2)}

.filter-panel{background:#fff;border-radius:11px;padding:.9rem 1.1rem;box-shadow:0 1px 3px rgba(15,23,41,.06);margin-bottom:1rem}
.filter-label{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8;margin-bottom:.3rem;display:block}
.filter-panel .form-control,.filter-panel .form-select{border-radius:8px;border-color:#e2e8f0;font-size:.82rem;padding:.38rem .7rem}
.filter-panel .form-control:focus,.filter-panel .form-select:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1)}

.biv-wrap{background:#fff;border-radius:11px;box-shadow:0 1px 3px rgba(15,23,41,.06);overflow:hidden}
.biv-hdr{padding:.8rem 1.1rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between}
.biv-wrap table thead th{background:#f8fafc;font-size:.65rem;font-weight:700;letter-spacing:.55px;text-transform:uppercase;color:#64748b;border-bottom:1px solid #f1f5f9;padding:.62rem 1rem;white-space:nowrap}
.biv-wrap table tbody td{padding:.65rem 1rem;font-size:.83rem;border-bottom:1px solid #f8fafc;vertical-align:middle}
.biv-wrap table tbody tr:last-child td{border-bottom:none}
.biv-wrap table tbody tr:hover{background:#fafbff}
.inv-link{font-weight:700;color:#1e40af;text-decoration:none}
.inv-link:hover{color:#3b82f6;text-decoration:underline}

/* type color coding */
.type-proforma{background:#eff6ff;color:#1d4ed8}
.type-final{background:#f0fdf4;color:#15803d}
.type-credit_note{background:#fff7ed;color:#c2410c}
.type-debit_note{background:#fef2f2;color:#991b1b}
.type-cancellation{background:#f1f5f9;color:#475569}

.badge-paid{background:#dcfce7!important;color:#166534!important}
.badge-partial{background:#fef3c7!important;color:#92400e!important}
.badge-overdue{background:#fee2e2!important;color:#991b1b!important}
.badge-unpaid{background:#f1f5f9!important;color:#475569!important}
.badge-void{background:#f1f5f9!important;color:#94a3b8!important}

.act-btn{width:28px;height:28px;border-radius:7px;border:1px solid #e2e8f0;background:#fff;color:#64748b;display:inline-flex;align-items:center;justify-content:center;font-size:.78rem;text-decoration:none;transition:background .13s,color .13s,border-color .13s}
.act-btn.view:hover{background:#eff6ff;color:#3b82f6;border-color:#bfdbfe}
.act-btn.pdf:hover{background:#fef2f2;color:#ef4444;border-color:#fecaca}

.bi-empty{padding:3rem 1rem;text-align:center;color:#94a3b8}
.bi-empty i{font-size:3rem;opacity:.3;display:block;margin-bottom:.6rem}

.mobile-item{padding:.9rem 1rem;border-bottom:1px solid #f1f5f9}
.mobile-item:last-child{border-bottom:none}
</style>
@endpush

@section('content')
@php
    use App\Models\Invoice;
    $allInvoices = Invoice::query();
    $stats = [
        'total'    => (clone $allInvoices)->count(),
        'proforma' => (clone $allInvoices)->where('invoice_type','PROFORMA')->count(),
        'final'    => (clone $allInvoices)->where('invoice_type','FINAL')->count(),
        'cn'       => (clone $allInvoices)->where('invoice_type','CREDIT_NOTE')->count(),
        'dn'       => (clone $allInvoices)->where('invoice_type','DEBIT_NOTE')->count(),
        'void'     => (clone $allInvoices)->where('payment_status','VOID')->count(),
        'overdue'  => (clone $allInvoices)->where('payment_status','OVERDUE')->count(),
    ];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3 page-hdr">
    <div>
        <div class="page-title">Billing Invoice</div>
        <div class="page-sub">Invoice enterprise: Proforma, Final, CN, DN, Cancellation</div>
    </div>
    <a href="{{ route('reservations.index') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1" style="border-radius:9px;">
        <i class="bi bi-calendar2-check"></i>
        <span class="d-none d-sm-inline">Ke Reservasi</span>
    </a>
</div>

{{-- Stats --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-md-4 col-xl-2"><div class="bi-stat c-slate"><div class="bi-stat-icon"><i class="bi bi-collection-fill"></i></div><div><div class="bi-stat-label">Total</div><div class="bi-stat-value">{{ $stats['total'] }}</div></div><i class="bi bi-collection-fill bg-icon-abs"></i></div></div>
    <div class="col-6 col-md-4 col-xl-2"><div class="bi-stat c-blue"><div class="bi-stat-icon"><i class="bi bi-file-earmark-text-fill"></i></div><div><div class="bi-stat-label">Proforma</div><div class="bi-stat-value">{{ $stats['proforma'] }}</div></div><i class="bi bi-file-earmark-text-fill bg-icon-abs"></i></div></div>
    <div class="col-6 col-md-4 col-xl-2"><div class="bi-stat c-green"><div class="bi-stat-icon"><i class="bi bi-check-circle-fill"></i></div><div><div class="bi-stat-label">Final</div><div class="bi-stat-value">{{ $stats['final'] }}</div></div><i class="bi bi-check-circle-fill bg-icon-abs"></i></div></div>
    <div class="col-6 col-md-4 col-xl-2"><div class="bi-stat c-amber"><div class="bi-stat-icon"><i class="bi bi-arrow-down-circle-fill"></i></div><div><div class="bi-stat-label">Credit Note</div><div class="bi-stat-value">{{ $stats['cn'] }}</div></div><i class="bi bi-arrow-down-circle-fill bg-icon-abs"></i></div></div>
    <div class="col-6 col-md-4 col-xl-2"><div class="bi-stat c-red"><div class="bi-stat-icon"><i class="bi bi-arrow-up-circle-fill"></i></div><div><div class="bi-stat-label">Debit Note</div><div class="bi-stat-value">{{ $stats['dn'] }}</div></div><i class="bi bi-arrow-up-circle-fill bg-icon-abs"></i></div></div>
    <div class="col-6 col-md-4 col-xl-2"><div class="bi-stat c-purple"><div class="bi-stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div><div><div class="bi-stat-label">Overdue</div><div class="bi-stat-value">{{ $stats['overdue'] }}</div></div><i class="bi bi-exclamation-triangle-fill bg-icon-abs"></i></div></div>
</div>

{{-- Filter --}}
<div class="filter-panel">
    <form method="GET" action="{{ route('billing-invoices.index') }}" id="bi-filter-form">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-sm-6 col-lg-3">
                <label class="filter-label">Pencarian</label>
                <input type="text" name="search" class="form-control" placeholder="No invoice / partner" value="{{ request('search') }}">
            </div>
            <div class="col-6 col-sm-3 col-lg-2">
                <label class="filter-label">Tipe</label>
                <select name="invoice_type" class="form-select">
                    <option value="">Semua</option>
                    @foreach($types as $k=>$v)
                        <option value="{{ $k }}" @selected(request('invoice_type')===$k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-3 col-lg-2">
                <label class="filter-label">Status</label>
                <select name="payment_status" class="form-select">
                    <option value="">Semua</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s }}" @selected(request('payment_status')===$s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-3 col-lg-2">
                <label class="filter-label">Partner</label>
                <select name="partner_id" class="form-select">
                    <option value="">Semua</option>
                    @foreach($partners as $p)
                        <option value="{{ $p->id }}" @selected(request('partner_id')==$p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-3 col-lg-2">
                <label class="filter-label">Dari</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-6 col-sm-3 col-lg-1">
                <label class="filter-label">Sampai</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm" style="border-radius:8px;padding:.38rem .85rem;"><i class="bi bi-search"></i></button>
                @if(request()->hasAny(['search','invoice_type','payment_status','partner_id','date_from','date_to']))
                <a href="{{ route('billing-invoices.index') }}" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;padding:.38rem .85rem;" title="Reset"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="biv-wrap">
    <div class="biv-hdr">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-file-earmark-text" style="color:#3b82f6"></i>
            <span class="fw-semibold" style="font-size:.86rem;">Billing Invoice</span>
            @if(request()->hasAny(['search','invoice_type','payment_status','partner_id','date_from','date_to']))
                <span class="badge" style="background:#eff6ff;color:#3b82f6;font-size:.65rem;">Filter Aktif</span>
            @endif
        </div>
        <span style="font-size:.73rem;color:#94a3b8;">{{ $invoices->total() }} data</span>
    </div>

    @if($invoices->isEmpty())
        <div class="bi-empty"><i class="bi bi-inbox"></i><p class="fw-semibold mb-1" style="color:#64748b;">Tidak ada invoice</p></div>
    @else
    {{-- Desktop --}}
    <div class="d-none d-md-block">
        <table class="table table-hover mb-0">
            <thead><tr>
                <th class="ps-4">No Invoice</th>
                <th>Tipe</th>
                <th>Partner</th>
                <th>Tgl Invoice</th>
                <th>Jatuh Tempo</th>
                <th class="text-end">Total</th>
                <th class="text-center">Bayar</th>
                <th class="text-center pe-3">Aksi</th>
            </tr></thead>
            <tbody>
                @foreach($invoices as $inv)
                @php
                    $typeCls='type-'.strtolower($inv->invoice_type);
                    $typeLabel=match($inv->invoice_type){'PROFORMA'=>'Proforma','FINAL'=>'Final','CREDIT_NOTE'=>'Credit Note','DEBIT_NOTE'=>'Debit Note','CANCELLATION'=>'Cancellation',default=>$inv->invoice_type};
                    $ps=$inv->payment_status;
                    $psCls=match($ps){'PAID'=>'badge-paid','PARTIAL'=>'badge-partial','OVERDUE'=>'badge-overdue','VOID'=>'badge-void',default=>'badge-unpaid'};
                @endphp
                <tr>
                    <td class="ps-4">
                        <a href="{{ route('billing-invoices.show',$inv) }}" class="inv-link">{{ $inv->invoice_no }}</a>
                        @if($inv->is_locked)<span class="badge ms-1" style="background:#f1f5f9;color:#64748b;font-size:.6rem;"><i class="bi bi-lock-fill"></i></span>@endif
                    </td>
                    <td><span class="badge {{ $typeCls }}">{{ $typeLabel }}</span></td>
                    <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#475569;">{{ $inv->partner?->name ?? '-' }}</td>
                    <td style="color:#64748b;white-space:nowrap;">{{ $inv->invoice_date?->format('d/m/Y') }}</td>
                    <td style="white-space:nowrap;" class="{{ $ps==='OVERDUE'?'text-danger fw-bold':'' }}">{{ $inv->due_date?->format('d/m/Y') ?? '-' }}</td>
                    <td class="text-end fw-bold" style="color:#1e293b;white-space:nowrap;">Rp {{ number_format($inv->grand_total,0,',','.') }}</td>
                    <td class="text-center"><span class="badge {{ $psCls }}">{{ $ps }}</span></td>
                    <td class="text-center pe-3">
                        <div class="d-flex gap-1 justify-content-center">
                            <a href="{{ route('billing-invoices.show',$inv) }}" class="act-btn view" title="Detail"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('billing-invoices.download',$inv) }}" class="act-btn pdf" title="Download PDF"><i class="bi bi-file-pdf"></i></a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile --}}
    <div class="d-md-none">
        @foreach($invoices as $inv)
        @php
            $typeCls='type-'.strtolower($inv->invoice_type);
            $typeLabel=match($inv->invoice_type){'PROFORMA'=>'Proforma','FINAL'=>'Final','CREDIT_NOTE'=>'CN','DEBIT_NOTE'=>'DN','CANCELLATION'=>'Cancel',default=>$inv->invoice_type};
            $ps=$inv->payment_status;
            $psCls=match($ps){'PAID'=>'badge-paid','PARTIAL'=>'badge-partial','OVERDUE'=>'badge-overdue','VOID'=>'badge-void',default=>'badge-unpaid'};
        @endphp
        <div class="mobile-item">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div class="d-flex align-items-center gap-1">
                    <a href="{{ route('billing-invoices.show',$inv) }}" class="inv-link" style="font-size:.84rem;">{{ $inv->invoice_no }}</a>
                    <span class="badge {{ $typeCls }}" style="font-size:.6rem;">{{ $typeLabel }}</span>
                </div>
                <span class="badge {{ $psCls }}">{{ $ps }}</span>
            </div>
            <div style="font-size:.78rem;color:#475569;">{{ $inv->partner?->name ?? '-' }}</div>
            <div class="d-flex justify-content-between mt-1">
                <span style="font-size:.73rem;color:#94a3b8;">{{ $inv->invoice_date?->format('d/m/Y') }}</span>
                <span style="font-weight:700;font-size:.84rem;">Rp {{ number_format($inv->grand_total,0,',','.') }}</span>
            </div>
        </div>
        @endforeach
    </div>

    @if($invoices->hasPages())
    <div class="px-4 py-3" style="border-top:1px solid #f1f5f9;">{{ $invoices->links() }}</div>
    @endif
    @endif
</div>

@endsection

@push('scripts')
<script>
document.querySelectorAll('#bi-filter-form select').forEach(function(sel){
    sel.addEventListener('change',function(){document.getElementById('bi-filter-form').submit();});
});
</script>
@endpush
