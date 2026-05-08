@extends('layouts.app')

@section('title', 'Laporan')
@section('page-title', 'Laporan Keuangan')

@push('styles')
<style>
/* ── Page Header ── */
.rpt-page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 1.25rem; gap: 1rem;
}
.rpt-title      { font-size: 1.05rem; font-weight: 800; color: #0f1729; letter-spacing: -.2px; margin: 0; }
.rpt-subtitle   { font-size: .75rem; color: #94a3b8; margin: 2px 0 0; font-weight: 400; }

/* ── KPI Count Cards ── */
.rpt-kpi-card {
    background: #fff;
    border: none; border-radius: 12px;
    box-shadow: 0 1px 4px rgba(15,23,41,.06), 0 4px 16px rgba(15,23,41,.04);
    transition: transform .18s ease, box-shadow .18s ease;
    overflow: hidden; position: relative;
}
.rpt-kpi-card:hover { transform: translateY(-2px); box-shadow: 0 6px 24px rgba(15,23,41,.1); }
.rpt-kpi-card .card-body { padding: .9rem 1rem; display: flex; align-items: center; gap: .85rem; }
.rpt-kpi-icon {
    width: 38px; height: 38px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem; flex-shrink: 0;
}
.rpt-kpi-label {
    font-size: .62rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .6px; color: #94a3b8; margin-bottom: 2px;
}
.rpt-kpi-value { font-size: 1.4rem; font-weight: 800; color: #0f1729; line-height: 1; }

/* ── Rev / Summary Cards (dashboard-matched) ── */
.rev-card { border: none; border-radius: 12px; box-shadow: 0 1px 3px rgba(15,23,41,.06); transition: transform .18s; overflow: hidden; }
.rev-card:hover { transform: translateY(-2px); }
.rev-card .card-body { padding: 1rem 1.15rem; position: relative; overflow: hidden; }
.rev-label { font-size: .64rem; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; margin-bottom: 4px; }
.rev-value { font-size: 1.18rem; font-weight: 800; line-height: 1.1; }
.rev-icon  { position: absolute; right: -4px; bottom: -6px; font-size: 3.6rem; opacity: .08; pointer-events: none; }

/* ── Filter Panel ── */
.rpt-filter-card {
    background: #fff; border: none; border-radius: 12px;
    box-shadow: 0 1px 3px rgba(15,23,41,.06);
    overflow: hidden;
}
.rpt-filter-header {
    display: flex; align-items: center; gap: .5rem;
    padding: .75rem 1.1rem;
    background: linear-gradient(135deg,#f8fafc,#f1f5f9);
    border-bottom: 1px solid #e8edf5;
    font-size: .78rem; font-weight: 700; color: #334155;
}
.rpt-filter-body { padding: .85rem 1.1rem; }
.rpt-filter-body .form-label { font-size: .68rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 3px; }
.rpt-filter-body .form-control,
.rpt-filter-body .form-select {
    border-color: #e2e8f0; border-radius: 8px;
    font-size: .8rem; color: #1e293b;
    background: #fafbff;
    box-shadow: none;
    transition: border-color .15s, box-shadow .15s;
}
.rpt-filter-body .form-control:focus,
.rpt-filter-body .form-select:focus {
    border-color: #93c5fd;
    box-shadow: 0 0 0 3px rgba(59,130,246,.1);
    background: #fff;
}
.rpt-export-bar {
    display: flex; align-items: center; gap: .5rem;
    padding: .65rem 1.1rem;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
}
.rpt-export-bar .label { font-size: .72rem; color: #94a3b8; font-weight: 500; flex: 1; }

/* ── Pill Tabs ── */
.rpt-tabs {
    display: flex; gap: .35rem;
    background: #f1f5f9;
    border-radius: 10px; padding: 4px;
    margin-bottom: 1.1rem;
    flex-wrap: wrap;
}
.rpt-tab-btn {
    flex: 1; min-width: 0;
    background: transparent; border: none; border-radius: 7px;
    padding: .4rem .85rem;
    font-size: .78rem; font-weight: 600; color: #64748b;
    cursor: pointer; transition: background .15s, color .15s, box-shadow .15s;
    white-space: nowrap; display: flex; align-items: center; justify-content: center; gap: .35rem;
}
.rpt-tab-btn:hover { background: rgba(255,255,255,.7); color: #334155; }
.rpt-tab-btn.active {
    background: #fff; color: #1e40af;
    box-shadow: 0 1px 4px rgba(15,23,41,.1);
}

/* ── Table Card (override for reports) ── */
.rpt-table-wrap {
    background: #fff; border: none; border-radius: 12px;
    box-shadow: 0 1px 4px rgba(15,23,41,.06), 0 3px 12px rgba(15,23,41,.03);
    overflow: hidden;
}
.rpt-table { width: 100%; border-collapse: collapse; }
.rpt-table thead tr { background: #f8fafc; }
.rpt-table thead th {
    font-size: .66rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .55px; color: #64748b;
    padding: .7rem 1rem; border-bottom: 1px solid #f1f5f9;
    white-space: nowrap;
}
.rpt-table tbody td {
    padding: .65rem 1rem; font-size: .82rem; color: #1e293b;
    border-bottom: 1px solid #f8fafc; vertical-align: middle;
}
.rpt-table tbody tr:last-child td { border-bottom: none; }
.rpt-table tbody tr:hover { background: #fafbff; }
.rpt-table tfoot td {
    padding: .65rem 1rem; font-size: .8rem; font-weight: 700;
    background: #f8fafc; color: #334155;
    border-top: 2px solid #f1f5f9;
}

/* ── Invoice link ── */
.inv-link { font-weight: 700; color: #2563eb; text-decoration: none; font-size: .83rem; }
.inv-link:hover { color: #1d4ed8; text-decoration: underline; }

/* ── Import Mini Cards ── */
.import-stat-card {
    border-radius: 10px; padding: .85rem 1rem;
    text-align: center; position: relative; overflow: hidden;
}
.import-stat-card .ist-label { font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .55px; margin-bottom: 4px; }
.import-stat-card .ist-value { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.import-stat-card .ist-sub   { font-size: .72rem; margin-top: 3px; }

/* ── Partner rank badge ── */
.rank-badge {
    display: inline-flex; align-items: center; justify-content: center;
    width: 22px; height: 22px; border-radius: 6px;
    font-size: .67rem; font-weight: 800;
    background: #f1f5f9; color: #64748b;
}
.rank-badge.top1 { background: #fef3c7; color: #92400e; }
.rank-badge.top2 { background: #f1f5f9; color: #475569; }
.rank-badge.top3 { background: #fef9c3; color: #a16207; }

/* ── Deposit progress bar ── */
.dep-bar-outer { height: 4px; border-radius: 10px; background: #f1f5f9; overflow: hidden; min-width: 60px; }
.dep-bar-inner { height: 100%; border-radius: 10px; }

/* ── Empty state ── */
.rpt-empty { text-align: center; padding: 3rem 1rem; color: #94a3b8; }
.rpt-empty i { font-size: 2.2rem; display: block; margin-bottom: .5rem; opacity: .4; }
.rpt-empty p { font-size: .82rem; margin: 0; }

@media (max-width: 767.98px) {
    .rev-value { font-size: 1rem; }
    .rpt-kpi-value { font-size: 1.2rem; }
    .rpt-tab-btn { font-size: .73rem; padding: .35rem .6rem; }
}
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="rpt-page-header">
    <div>
        <div class="rpt-title"><i class="bi bi-bar-chart-line-fill me-2" style="color:#3b82f6;font-size:.95rem"></i>Laporan Keuangan</div>
        <div class="rpt-subtitle">Ringkasan invoice, pembayaran, dan deposit seluruh periode</div>
    </div>
</div>

{{-- ── KPI Count Row ── --}}
<div class="row g-2 mb-3">
    @php
        $kpis = [
            ['label'=>'Total Invoice', 'value'=>$summary['total_invoice'],  'icon'=>'bi-receipt',                  'ic_bg'=>'#eff6ff', 'ic_color'=>'#2563eb'],
            ['label'=>'Lunas',         'value'=>$summary['count_paid'],     'icon'=>'bi-check-circle-fill',        'ic_bg'=>'#f0fdf4', 'ic_color'=>'#059669'],
            ['label'=>'Belum Bayar',   'value'=>$summary['count_unpaid'],   'icon'=>'bi-clock',                    'ic_bg'=>'#f8fafc', 'ic_color'=>'#64748b'],
            ['label'=>'Partial',       'value'=>$summary['count_partial'],  'icon'=>'bi-hourglass-split',          'ic_bg'=>'#fffbeb', 'ic_color'=>'#d97706'],
            ['label'=>'Jatuh Tempo',   'value'=>$summary['count_overdue'],  'icon'=>'bi-exclamation-triangle-fill','ic_bg'=>'#fef2f2', 'ic_color'=>'#dc2626'],
        ];
    @endphp
    @foreach($kpis as $k)
    <div class="col-6 col-sm-4 col-md">
        <div class="rpt-kpi-card">
            <div class="card-body">
                <div class="rpt-kpi-icon" style="background:{{ $k['ic_bg'] }};color:{{ $k['ic_color'] }}">
                    <i class="bi {{ $k['icon'] }}"></i>
                </div>
                <div>
                    <div class="rpt-kpi-label">{{ $k['label'] }}</div>
                    <div class="rpt-kpi-value">{{ number_format($k['value']) }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Revenue / Outstanding / Deposit ── --}}
<div class="row g-2 mb-3">
    <div class="col-12 col-sm-4">
        <div class="card rev-card" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-left:4px solid #22c55e;">
            <div class="card-body">
                <div class="rev-label text-success">Pendapatan (Lunas)</div>
                <div class="rev-value text-success">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</div>
                <i class="bi bi-graph-up-arrow rev-icon text-success"></i>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="card rev-card" style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border-left:4px solid #f59e0b;">
            <div class="card-body">
                <div class="rev-label text-warning">Outstanding Piutang</div>
                <div class="rev-value text-warning">Rp {{ number_format($summary['total_outstanding'], 0, ',', '.') }}</div>
                <i class="bi bi-wallet2 rev-icon text-warning"></i>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="card rev-card" style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border-left:4px solid #3b82f6;">
            <div class="card-body">
                <div class="rev-label text-primary">Sisa Deposit</div>
                <div class="rev-value text-primary">Rp {{ number_format($depositReport->sum('balance'), 0, ',', '.') }}</div>
                <i class="bi bi-wallet-fill rev-icon text-primary"></i>
            </div>
        </div>
    </div>
</div>

{{-- ── Filter Panel ── --}}
<div class="rpt-filter-card mb-3">
    <div class="rpt-filter-header">
        <i class="bi bi-funnel-fill" style="color:#3b82f6;font-size:.82rem"></i>
        Filter &amp; Pencarian
    </div>
    <div class="rpt-filter-body">
        <form method="GET" action="{{ route('reports.index') }}" id="filter-form">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="date_from" class="form-control form-control-sm"
                        value="{{ request('date_from') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Sampai</label>
                    <input type="date" name="date_to" class="form-control form-control-sm"
                        value="{{ request('date_to') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        @foreach(['PAID','UNPAID','PARTIAL','OVERDUE'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Partner</label>
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
                    <label class="form-label">Cari</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                        placeholder="No, tamu, partner…" value="{{ request('search') }}">
                </div>
                <div class="col-6 col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill"
                        style="background:linear-gradient(135deg,#3b82f6,#2563eb);border:none;border-radius:8px;font-size:.78rem;">
                        <i class="bi bi-funnel me-1"></i>Filter
                    </button>
                    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm"
                        style="border-color:#e2e8f0;color:#64748b;border-radius:8px;"
                        title="Reset">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
    <div class="rpt-export-bar" id="export-bar-invoice">
        <span class="label"><i class="bi bi-download me-1"></i>Export hasil filter:</span>
        <a href="{{ route('reports.export-csv', request()->query()) }}"
           class="btn btn-sm"
           style="background:#f0fdf4;color:#059669;border:1px solid #bbf7d0;border-radius:7px;font-size:.75rem;font-weight:600;padding:.28rem .75rem;">
            <i class="bi bi-filetype-csv me-1"></i>CSV
        </a>
        <a href="{{ route('reports.export-pdf', request()->query()) }}"
           class="btn btn-sm"
           style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:7px;font-size:.75rem;font-weight:600;padding:.28rem .75rem;">
            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
        </a>
    </div>
    <div class="rpt-export-bar d-none" id="export-bar-credit">
        <span class="label"><i class="bi bi-download me-1"></i>Export laporan kredit:</span>
        <a href="{{ route('reports.export-credit-csv', request()->only(['credit_class_id','credit_status'])) }}"
           class="btn btn-sm"
           style="background:#f0fdf4;color:#059669;border:1px solid #bbf7d0;border-radius:7px;font-size:.75rem;font-weight:600;padding:.28rem .75rem;">
            <i class="bi bi-filetype-csv me-1"></i>CSV
        </a>
        <a href="{{ route('reports.export-credit-pdf', request()->only(['credit_class_id','credit_status'])) }}"
           class="btn btn-sm"
           style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:7px;font-size:.75rem;font-weight:600;padding:.28rem .75rem;">
            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
        </a>
    </div>
</div>

{{-- ── Pill Tabs ── --}}
<div class="rpt-tabs" id="reportTabs" role="tablist">
    <button class="rpt-tab-btn" data-target="tab-invoices" data-export-bar="export-bar-invoice">
        <i class="bi bi-list-ul"></i> Invoice List
    </button>
    <button class="rpt-tab-btn" data-target="tab-partners" data-export-bar="export-bar-invoice">
        <i class="bi bi-people"></i> Per Partner
    </button>
    <button class="rpt-tab-btn" data-target="tab-deposit" data-export-bar="export-bar-invoice">
        <i class="bi bi-wallet2"></i> Deposit
    </button>
    <button class="rpt-tab-btn" data-target="tab-import" data-export-bar="export-bar-invoice">
        <i class="bi bi-file-earmark-spreadsheet"></i> Import
    </button>
    <button class="rpt-tab-btn" data-target="tab-kredit" data-export-bar="export-bar-credit">
        <i class="bi bi-credit-card-2-front"></i> Kredit
    </button>
</div>

{{-- ── Tab Panes ── --}}

{{-- Invoice List --}}
<div class="tab-pane-content" id="tab-invoices">
    <div class="rpt-table-wrap">
        <div class="table-responsive">
            <table class="rpt-table">
                <thead>
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
                            <a href="{{ route('invoices.show', $inv) }}" class="inv-link">
                                {{ $inv->invoice_no }}
                            </a>
                            @if(!$inv->is_finalized)
                                <span class="ms-1" style="display:inline-block;background:#f1f5f9;color:#64748b;font-size:.6rem;font-weight:700;padding:.1em .45em;border-radius:4px;letter-spacing:.3px;">DRAFT</span>
                            @endif
                        </td>
                        <td class="text-truncate" style="max-width:140px;font-weight:500">{{ $inv->partner->nama_partner ?? '—' }}</td>
                        <td class="d-none d-md-table-cell text-truncate" style="max-width:120px;color:#64748b">{{ $inv->guest_name ?? '—' }}</td>
                        <td style="color:#475569;white-space:nowrap">{{ $inv->invoice_date?->format('d/m/y') }}</td>
                        <td class="d-none d-md-table-cell" style="white-space:nowrap;{{ $inv->isOverdue() ? 'color:#dc2626;font-weight:700' : 'color:#64748b' }}">
                            {{ $inv->due_date?->format('d/m/y') ?? '—' }}
                        </td>
                        <td class="text-end" style="font-weight:700;color:#0f1729">{{ number_format($inv->grand_total, 0, ',', '.') }}</td>
                        <td class="text-end d-none d-md-table-cell" style="color:#059669;font-weight:600">{{ number_format($inv->totalPaid(), 0, ',', '.') }}</td>
                        <td class="text-end d-none d-md-table-cell" style="font-weight:600;{{ $inv->grand_total - $inv->totalPaid() > 0 ? 'color:#dc2626' : 'color:#059669' }}">
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
                        <td colspan="9">
                            <div class="rpt-empty">
                                <i class="bi bi-inbox"></i>
                                <p>Tidak ada data dengan filter ini.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($invoices->count() > 0)
                <tfoot>
                    <tr>
                        <td colspan="5" class="d-none d-md-table-cell" style="color:#64748b;font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Total (halaman ini)</td>
                        <td colspan="3" class="d-md-none" style="color:#64748b;font-size:.72rem;font-weight:600">Total</td>
                        <td class="text-end" style="color:#0f1729">{{ number_format($invoices->sum('grand_total'), 0, ',', '.') }}</td>
                        <td class="text-end d-none d-md-table-cell" style="color:#059669">{{ number_format($invoices->sum(fn($i) => $i->totalPaid()), 0, ',', '.') }}</td>
                        <td class="text-end d-none d-md-table-cell" style="color:#dc2626">{{ number_format($invoices->sum(fn($i) => max(0, $i->grand_total - $i->totalPaid())), 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $invoices->links() }}</div>
</div>

{{-- Per Partner --}}
<div class="tab-pane-content d-none" id="tab-partners">
    <div class="rpt-table-wrap">
        <div class="table-responsive">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
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
                        <td>
                            <span class="rank-badge {{ $i===0?'top1':($i===1?'top2':($i===2?'top3':'')) }}">
                                {{ $i + 1 }}
                            </span>
                        </td>
                        <td style="font-weight:600;color:#0f1729">{{ $row->partner->nama_partner ?? 'Tanpa Partner' }}</td>
                        <td class="text-center">
                            <span style="background:#eff6ff;color:#2563eb;font-size:.72rem;font-weight:700;padding:.2em .55em;border-radius:5px;">
                                {{ $row->invoice_count }}
                            </span>
                        </td>
                        <td class="text-end" style="font-weight:600">{{ number_format($row->total_billed, 0, ',', '.') }}</td>
                        <td class="text-end" style="color:#059669;font-weight:600">{{ number_format($row->total_paid, 0, ',', '.') }}</td>
                        <td class="text-end">
                            @if($row->total_outstanding > 0)
                                <span style="color:#dc2626;font-weight:700">{{ number_format($row->total_outstanding, 0, ',', '.') }}</span>
                            @else
                                <span style="color:#94a3b8">0</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6"><div class="rpt-empty"><i class="bi bi-inbox"></i><p>Tidak ada data.</p></div></td></tr>
                    @endforelse
                </tbody>
                @if($partnerSummary->count() > 0)
                <tfoot>
                    <tr>
                        <td colspan="3" style="color:#64748b;font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Total</td>
                        <td class="text-end">{{ number_format($partnerSummary->sum('total_billed'), 0, ',', '.') }}</td>
                        <td class="text-end" style="color:#059669">{{ number_format($partnerSummary->sum('total_paid'), 0, ',', '.') }}</td>
                        <td class="text-end" style="color:#dc2626">{{ number_format($partnerSummary->sum('total_outstanding'), 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

{{-- Deposit --}}
<div class="tab-pane-content d-none" id="tab-deposit">
    <div class="rpt-table-wrap">
        <div class="table-responsive">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Partner</th>
                        <th class="text-end">Total Top-up</th>
                        <th class="text-end">Total Terpakai</th>
                        <th class="text-end">Saldo</th>
                        <th class="d-none d-md-table-cell" style="width:120px">Indikator</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($depositReport as $i => $row)
                    @php
                        $threshold = (float) \App\Models\Setting::get('deposit_low_threshold', 1000000);
                        $isEmpty   = $row->balance <= 0;
                        $isLow     = !$isEmpty && $row->balance < $threshold;
                        $maxBal    = $depositReport->max('total_topup') ?: 1;
                        $pct       = $maxBal > 0 ? min(100, ($row->balance / $maxBal) * 100) : 0;
                        $barColor  = $isEmpty ? '#ef4444' : ($isLow ? '#f59e0b' : '#22c55e');
                    @endphp
                    <tr>
                        <td><span class="rank-badge">{{ $i + 1 }}</span></td>
                        <td style="font-weight:600;color:#0f1729">{{ $row->partner->nama_partner ?? '—' }}</td>
                        <td class="text-end" style="color:#059669;font-weight:600">{{ number_format($row->total_topup, 0, ',', '.') }}</td>
                        <td class="text-end" style="color:#dc2626;font-weight:600">{{ number_format($row->total_deduction, 0, ',', '.') }}</td>
                        <td class="text-end" style="font-weight:800;color:{{ $isEmpty ? '#dc2626' : ($isLow ? '#d97706' : '#059669') }}">
                            {{ number_format($row->balance, 0, ',', '.') }}
                        </td>
                        <td class="d-none d-md-table-cell">
                            <div class="dep-bar-outer">
                                <div class="dep-bar-inner" style="width:{{ max(2, $pct) }}%;background:{{ $barColor }}"></div>
                            </div>
                        </td>
                        <td>
                            @if($isEmpty)
                                <span style="background:#fee2e2;color:#991b1b;font-size:.68rem;font-weight:700;padding:.22em .55em;border-radius:5px;">Habis</span>
                            @elseif($isLow)
                                <span style="background:#fef3c7;color:#92400e;font-size:.68rem;font-weight:700;padding:.22em .55em;border-radius:5px;">Rendah</span>
                            @else
                                <span style="background:#dcfce7;color:#166534;font-size:.68rem;font-weight:700;padding:.22em .55em;border-radius:5px;">OK</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('deposits.index', $row->partner_id) }}"
                               style="background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;border-radius:6px;font-size:.72rem;font-weight:600;padding:.2rem .6rem;text-decoration:none;display:inline-block;">
                                Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8"><div class="rpt-empty"><i class="bi bi-wallet2"></i><p>Belum ada transaksi deposit.</p></div></td></tr>
                    @endforelse
                </tbody>
                @if($depositReport->count() > 0)
                <tfoot>
                    <tr>
                        <td colspan="2" style="color:#64748b;font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Total</td>
                        <td class="text-end" style="color:#059669">{{ number_format($depositReport->sum('total_topup'), 0, ',', '.') }}</td>
                        <td class="text-end" style="color:#dc2626">{{ number_format($depositReport->sum('total_deduction'), 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($depositReport->sum('balance'), 0, ',', '.') }}</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

{{-- Import Summary --}}
<div class="tab-pane-content d-none" id="tab-import">
    {{-- Mini stat cards --}}
    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <div class="import-stat-card" style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1px solid #bfdbfe;">
                <div class="ist-label" style="color:#1d4ed8">Total Import Sessions</div>
                <div class="ist-value" style="color:#1e40af">{{ number_format($importSummary['totalImports']) }}</div>
                <div class="ist-sub" style="color:#3b82f6">{{ $importSummary['doneImports'] }} selesai</div>
                <i class="bi bi-file-earmark-spreadsheet" style="position:absolute;right:10px;bottom:6px;font-size:2.8rem;opacity:.07;color:#1d4ed8;pointer-events:none"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="import-stat-card" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1px solid #bbf7d0;">
                <div class="ist-label" style="color:#166534">Total Komisi dari Import</div>
                <div class="ist-value" style="color:#059669;font-size:1.2rem">Rp {{ number_format($importSummary['totalKomisi'], 0, ',', '.') }}</div>
                <i class="bi bi-graph-up-arrow" style="position:absolute;right:10px;bottom:6px;font-size:2.8rem;opacity:.07;color:#059669;pointer-events:none"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="import-stat-card" style="background:linear-gradient(135deg,#faf5ff,#ede9fe);border:1px solid #ddd6fe;">
                <div class="ist-label" style="color:#5b21b6">Tamu per Nasionalitas</div>
                <div class="ist-value" style="font-size:1.05rem;color:#4c1d95">
                    <span style="color:#2563eb">{{ number_format($importSummary['byNationality']['local']) }}</span>
                    <span style="color:#94a3b8;font-size:.8rem;font-weight:500"> Lokal</span>
                    &nbsp;/&nbsp;
                    <span style="color:#7c3aed">{{ number_format($importSummary['byNationality']['foreign']) }}</span>
                    <span style="color:#94a3b8;font-size:.8rem;font-weight:500"> Asing</span>
                </div>
                <i class="bi bi-globe2" style="position:absolute;right:10px;bottom:6px;font-size:2.8rem;opacity:.07;color:#7c3aed;pointer-events:none"></i>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-5">
            <div class="rpt-table-wrap">
                <div style="padding:.75rem 1rem;border-bottom:1px solid #f1f5f9;font-size:.78rem;font-weight:700;color:#334155;display:flex;align-items:center;gap:.4rem;">
                    <i class="bi bi-tag-fill" style="color:#6366f1;font-size:.78rem"></i> Revenue per Ticket Type
                </div>
                <table class="rpt-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th class="text-center">Trx</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Komisi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($importSummary['byType'] as $type => $data)
                        <tr>
                            <td>
                                <span style="background:#eef2ff;color:#4338ca;font-size:.7rem;font-weight:700;padding:.2em .55em;border-radius:5px;">{{ $type }}</span>
                            </td>
                            <td class="text-center" style="font-weight:600">{{ number_format($data['count']) }}</td>
                            <td class="text-end" style="font-weight:600">{{ number_format($data['total_amount'], 0, ',', '.') }}</td>
                            <td class="text-end" style="color:#059669;font-weight:600">{{ number_format($data['total_komisi'], 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4"><div class="rpt-empty"><i class="bi bi-inbox"></i><p>Belum ada data import.</p></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-7">
            <div class="rpt-table-wrap">
                <div style="padding:.75rem 1rem;border-bottom:1px solid #f1f5f9;font-size:.78rem;font-weight:700;color:#334155;display:flex;align-items:center;gap:.4rem;">
                    <i class="bi bi-trophy-fill" style="color:#f59e0b;font-size:.78rem"></i> Top Produk (by Trx Count)
                </div>
                <table class="rpt-table">
                    <thead>
                        <tr><th style="width:40px">#</th><th>Ticket Name</th><th class="text-center">Trx</th></tr>
                    </thead>
                    <tbody>
                        @forelse($importSummary['topProducts'] as $i => $p)
                        <tr>
                            <td>
                                <span class="rank-badge {{ $i===0?'top1':($i===1?'top2':($i===2?'top3':'')) }}">{{ $i + 1 }}</span>
                            </td>
                            <td style="color:#334155">{{ $p['name'] }}</td>
                            <td class="text-center" style="font-weight:700;color:#2563eb">{{ number_format($p['count']) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3"><div class="rpt-empty"><i class="bi bi-inbox"></i><p>Belum ada data.</p></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('imports.index') }}"
           style="background:#fff;border:1px solid #e2e8f0;color:#2563eb;border-radius:8px;font-size:.78rem;font-weight:600;padding:.35rem .9rem;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem;box-shadow:0 1px 3px rgba(15,23,41,.06);">
            <i class="bi bi-list-ul"></i> Semua Import
        </a>
    </div>
</div>

{{-- Kredit --}}
<div class="tab-pane-content d-none" id="tab-kredit">

    {{-- Credit-specific filter row --}}
    <form method="GET" action="{{ route('reports.index') }}" class="mb-3">
        {{-- preserve existing invoice filters --}}
        @foreach(request()->only(['date_from','date_to','status','partner_id','search']) as $k => $v)
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endforeach
        <input type="hidden" name="tab" value="kredit">
        <div class="rpt-filter-card">
            <div class="rpt-filter-header">
                <i class="bi bi-credit-card-2-front" style="color:#7c3aed;font-size:.82rem"></i>
                Filter Kredit
            </div>
            <div class="rpt-filter-body">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label">Credit Class</label>
                        <select name="credit_class_id" class="form-select form-select-sm">
                            <option value="">Semua Class</option>
                            @foreach($creditClasses as $cc)
                                <option value="{{ $cc->id }}" {{ request('credit_class_id') == $cc->id ? 'selected' : '' }}>
                                    {{ $cc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Status Kredit</label>
                        <select name="credit_status" class="form-select form-select-sm">
                            <option value="">Semua Status</option>
                            @foreach(['NORMAL','WARNING','OVER_LIMIT'] as $cs)
                                <option value="{{ $cs }}" {{ request('credit_status') === $cs ? 'selected' : '' }}>{{ $cs }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm"
                            style="background:linear-gradient(135deg,#7c3aed,#6d28d9);border:none;border-radius:8px;font-size:.78rem;">
                            <i class="bi bi-funnel me-1"></i>Filter
                        </button>
                        <a href="{{ route('reports.index', ['tab'=>'kredit']) }}" class="btn btn-outline-secondary btn-sm"
                            style="border-color:#e2e8f0;color:#64748b;border-radius:8px;" title="Reset">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- Credit Summary Table --}}
    <div class="rpt-table-wrap mb-3">
        <div style="padding:.75rem 1rem;border-bottom:1px solid #f1f5f9;font-size:.78rem;font-weight:700;color:#334155;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-person-check-fill" style="color:#7c3aed;font-size:.78rem"></i> Credit Summary per Partner
        </div>
        <div class="table-responsive">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>Partner</th>
                        <th>Credit Class</th>
                        <th class="text-end">Limit</th>
                        <th class="text-end">Used</th>
                        <th class="text-end">Available</th>
                        <th style="width:120px">Utilization</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($creditSummary as $row)
                    @php
                        $util  = $row->utilization_percent;
                        $color = $util > 100 ? '#dc2626' : ($util >= \App\Models\Setting::get('credit_warning_threshold', 80) ? '#d97706' : '#059669');
                    @endphp
                    <tr>
                        <td style="font-weight:600;color:#0f1729">
                            <a href="{{ route('partners.show', $row->partner) }}" style="color:#0f1729;text-decoration:none;">
                                {{ $row->partner->nama_partner }}
                            </a>
                        </td>
                        <td>
                            @if($row->credit_class_name)
                                <span class="badge bg-{{ $row->credit_class_color ?? 'secondary' }}">{{ $row->credit_class_name }}</span>
                            @else
                                <span style="color:#94a3b8;font-size:.78rem">—</span>
                            @endif
                        </td>
                        <td class="text-end" style="font-weight:600">{{ number_format($row->limit, 0, ',', '.') }}</td>
                        <td class="text-end" style="color:#dc2626;font-weight:600">{{ number_format($row->used, 0, ',', '.') }}</td>
                        <td class="text-end" style="color:{{ $row->available >= 0 ? '#059669' : '#dc2626' }};font-weight:600">
                            {{ number_format($row->available, 0, ',', '.') }}
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:6px;">
                                <div style="flex:1;height:5px;background:#f1f5f9;border-radius:10px;overflow:hidden;min-width:50px;">
                                    <div style="width:{{ min(100, $util) }}%;height:100%;background:{{ $color }};border-radius:10px;"></div>
                                </div>
                                <span style="font-size:.72rem;font-weight:700;color:{{ $color }};white-space:nowrap;">{{ number_format($util, 1) }}%</span>
                            </div>
                        </td>
                        <td>
                            @if($row->status === 'OVER_LIMIT')
                                <span style="background:#fee2e2;color:#991b1b;font-size:.68rem;font-weight:700;padding:.22em .55em;border-radius:5px;">OVER LIMIT</span>
                            @elseif($row->status === 'WARNING')
                                <span style="background:#fef3c7;color:#92400e;font-size:.68rem;font-weight:700;padding:.22em .55em;border-radius:5px;">WARNING</span>
                            @else
                                <span style="background:#dcfce7;color:#166534;font-size:.68rem;font-weight:700;padding:.22em .55em;border-radius:5px;">NORMAL</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="rpt-empty">
                                <i class="bi bi-credit-card"></i>
                                <p>Tidak ada partner dengan credit limit.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($creditSummary->count() > 0)
                <tfoot>
                    <tr>
                        <td colspan="2" style="color:#64748b;font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Total</td>
                        <td class="text-end">{{ number_format($creditSummary->sum('limit'), 0, ',', '.') }}</td>
                        <td class="text-end" style="color:#dc2626">{{ number_format($creditSummary->sum('used'), 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($creditSummary->sum('available'), 0, ',', '.') }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Credit Aging Table --}}
    @php
        $agingBuckets = $creditAging['buckets'];
        $agingRows    = $creditAging['rows'];
        $agingTotals  = $creditAging['totals'];
        $b1 = $agingBuckets['b1']; $b2 = $agingBuckets['b2'];
        $b3 = $agingBuckets['b3']; $b4 = $agingBuckets['b4'];
    @endphp
    <div class="rpt-table-wrap">
        <div style="padding:.75rem 1rem;border-bottom:1px solid #f1f5f9;font-size:.78rem;font-weight:700;color:#334155;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-calendar-week-fill" style="color:#dc2626;font-size:.78rem"></i>
            Credit Aging
            <span style="font-size:.68rem;font-weight:500;color:#94a3b8;margin-left:4px;">
                Bucket: Current | 1–{{ $b1 }} | {{ $b1+1 }}–{{ $b2 }} | {{ $b2+1 }}–{{ $b3 }} | {{ $b3+1 }}–{{ $b4 }} | >{{ $b4 }} hari
            </span>
        </div>
        <div class="table-responsive">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>Partner</th>
                        <th>Class</th>
                        <th class="text-end">Current</th>
                        <th class="text-end">1–{{ $b1 }}</th>
                        <th class="text-end">{{ $b1+1 }}–{{ $b2 }}</th>
                        <th class="text-end">{{ $b2+1 }}–{{ $b3 }}</th>
                        <th class="text-end">{{ $b3+1 }}–{{ $b4 }}</th>
                        <th class="text-end">>{{ $b4 }}</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agingRows as $row)
                    <tr>
                        <td style="font-weight:600;color:#0f1729">{{ $row->partner->nama_partner }}</td>
                        <td>
                            @if($row->partner->creditClass)
                                <span class="badge bg-{{ $row->partner->creditClass->color ?? 'secondary' }}" style="font-size:.65rem;">{{ $row->partner->creditClass->name }}</span>
                            @else
                                <span style="color:#94a3b8;font-size:.78rem">—</span>
                            @endif
                        </td>
                        @foreach(['current','b1','b2','b3','b4','b5'] as $bk)
                        <td class="text-end" style="{{ $bk !== 'current' && $row->buckets[$bk] > 0 ? 'color:#dc2626;font-weight:600' : 'color:#64748b' }}">
                            {{ $row->buckets[$bk] > 0 ? number_format($row->buckets[$bk], 0, ',', '.') : '—' }}
                        </td>
                        @endforeach
                        <td class="text-end" style="font-weight:700;color:#0f1729">{{ number_format($row->total, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9">
                            <div class="rpt-empty">
                                <i class="bi bi-calendar-check"></i>
                                <p>Tidak ada outstanding piutang kredit.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($agingRows) > 0)
                <tfoot>
                    <tr>
                        <td colspan="2" style="color:#64748b;font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Grand Total</td>
                        @foreach(['current','b1','b2','b3','b4','b5'] as $bk)
                        <td class="text-end" style="{{ $bk !== 'current' && $agingTotals[$bk] > 0 ? 'color:#dc2626' : '' }}">
                            {{ $agingTotals[$bk] > 0 ? number_format($agingTotals[$bk], 0, ',', '.') : '—' }}
                        </td>
                        @endforeach
                        <td class="text-end">{{ number_format(array_sum($agingTotals), 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const tabs       = document.querySelectorAll('.rpt-tab-btn');
    const panes      = document.querySelectorAll('.tab-pane-content');
    const exportBars = document.querySelectorAll('[id^="export-bar-"]');

    function activateTab(btn) {
        const target    = btn.dataset.target;
        const exportBar = btn.dataset.exportBar;

        tabs.forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');

        panes.forEach(function (p) {
            p.id === target ? p.classList.remove('d-none') : p.classList.add('d-none');
        });

        exportBars.forEach(function (bar) {
            bar.id === exportBar ? bar.classList.remove('d-none') : bar.classList.add('d-none');
        });
    }

    tabs.forEach(function (btn) {
        btn.addEventListener('click', function () { activateTab(btn); });
    });

    // Auto-activate tab from URL param or credit filter
    const urlTab = new URLSearchParams(window.location.search).get('tab');
    const hasCreditFilter = new URLSearchParams(window.location.search).get('credit_class_id')
                         || new URLSearchParams(window.location.search).get('credit_status');
    let defaultTarget = 'tab-invoices';
    if (urlTab === 'kredit' || hasCreditFilter) defaultTarget = 'tab-kredit';

    const defaultBtn = Array.from(tabs).find(function (b) { return b.dataset.target === defaultTarget; });
    if (defaultBtn) activateTab(defaultBtn);
    else if (tabs.length) activateTab(tabs[0]);
})();
</script>
@endpush
