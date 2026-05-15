@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
<style>
    /* ── Greeting ── */
    .dash-greeting { font-size: 1.05rem; font-weight: 800; color: #0f1729; }
    .dash-greeting-sub { font-size: .78rem; color: #94a3b8; }

    /* ── KPI Cards ── */
    .kpi-row .stat-card .card-body {
        padding: .95rem 1.1rem; position: relative; overflow: hidden;
    }
    .kpi-row .stat-card .stat-label {
        font-size: .64rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .6px; color: rgba(255,255,255,.72); margin-bottom: 4px;
    }
    .kpi-row .stat-card .stat-value { font-size: 1.45rem; font-weight: 800; color: #fff; line-height: 1; }
    .kpi-row .stat-card .stat-icon-wrap {
        width: 38px; height: 38px; border-radius: 10px;
        background: rgba(255,255,255,.17); color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0;
    }
    .kpi-row .stat-card .stat-bg-icon {
        position: absolute; right: -8px; bottom: -10px;
        font-size: 4.5rem; opacity: .09; color: #fff; pointer-events: none;
    }

    /* ── Revenue banner ── */
    .rev-card .card-body { padding: 1rem 1.1rem; position: relative; overflow: hidden; }
    .rev-label { font-size: .66rem; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; margin-bottom: 3px; }
    .rev-value { font-size: 1.15rem; font-weight: 800; line-height: 1.1; }
    .rev-icon { position: absolute; right: -4px; bottom: -6px; font-size: 3.5rem; opacity: .09; pointer-events: none; }

    /* ── Deposit list (mobile-friendly) ── */
    .deposit-item {
        display: flex; align-items: center; gap: .75rem;
        padding: .75rem 1.1rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .deposit-item:last-child { border-bottom: none; }
    .deposit-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .deposit-name { font-weight: 600; font-size: .84rem; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .deposit-amount { font-weight: 700; font-size: .84rem; white-space: nowrap; }
    .deposit-bar-wrap { flex: 1; min-width: 60px; max-width: 100px; }
    .deposit-progress { height: 5px; border-radius: 10px; background: #e8edf5; overflow: hidden; }
    .deposit-progress .bar { height: 100%; border-radius: 10px; }

    /* ── Import mini ── */
    .import-mini .card-body { padding: .85rem 1rem; }
    .import-mini .im-label { font-size: .67rem; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: #94a3b8; margin-bottom: 2px; }
    .import-mini .im-value { font-size: 1.3rem; font-weight: 800; }

    /* ── Queue & Due-soon lists ── */
    .queue-item, .due-item {
        padding: .75rem 1.1rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; gap: .75rem;
        text-decoration: none; color: inherit;
        transition: background .12s;
        cursor: pointer;
    }
    .queue-item:last-child, .due-item:last-child { border-bottom: none; }
    .queue-item:hover { background: #eff6ff; }
    .due-item:hover { background: #fafbff; }
    .q-no   { font-weight: 700; font-size: .83rem; color: #1e40af; }
    .q-sub  { font-size: .73rem; color: #94a3b8; }
    .q-amt  { font-weight: 700; font-size: .82rem; white-space: nowrap; color: #1e293b; }
    .due-badge {
        font-size: .68rem; font-weight: 700; border-radius: 6px;
        padding: .18rem .45rem; white-space: nowrap; flex-shrink: 0;
    }
    .due-normal  { background: #f1f5f9; color: #475569; }
    .due-warn3   { background: #fef3c7; color: #92400e; }  /* H-3 */
    .due-warn1   { background: #ffedd5; color: #9a3412; }  /* H-1 */
    .due-today   { background: #fee2e2; color: #991b1b; }  /* H-0 */
    .due-overdue { background: #991b1b; color: #fff; }     /* sudah lewat */

    @media (max-width: 767.98px) {
        .rev-value { font-size: 1rem; }
        .kpi-row .stat-card .stat-value { font-size: 1.25rem; }
    }
</style>
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="d-flex align-items-center justify-content-between mb-3 page-hdr">
    <div>
        <div class="dash-greeting">Halo, {{ auth()->user()->full_name }} 👋</div>
        <div class="dash-greeting-sub">Ringkasan aktivitas invoice hari ini</div>
    </div>
    <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1"
       style="background:linear-gradient(135deg,#3b82f6,#2563eb);border:none;box-shadow:0 3px 12px rgba(59,130,246,.3);border-radius:9px;">
        <i class="bi bi-plus-lg"></i>
        <span class="d-none d-sm-inline">Buat Invoice</span>
    </a>
</div>

{{-- ── KPI Cards ── --}}
<div class="row g-2 kpi-row mb-3">
    @php
        $kpis = [
            ['label'=>'Total Invoice', 'value'=>$stats['total'],   'icon'=>'bi-file-earmark-text-fill', 'gc'=>'gc-blue'],
            ['label'=>'Belum Bayar',   'value'=>$stats['unpaid'],  'icon'=>'bi-clock-fill',             'gc'=>'gc-slate'],
            ['label'=>'Partial',       'value'=>$stats['partial'], 'icon'=>'bi-hourglass-split',        'gc'=>'gc-amber'],
            ['label'=>'Lunas',         'value'=>$stats['paid'],    'icon'=>'bi-check-circle-fill',      'gc'=>'gc-green'],
            ['label'=>'Jatuh Tempo',   'value'=>$stats['overdue'], 'icon'=>'bi-exclamation-triangle-fill','gc'=>'gc-red'],
            ['label'=>'Partner',       'value'=>$totalPartners,    'icon'=>'bi-people-fill',            'gc'=>'gc-cyan'],
        ];
    @endphp
    @foreach($kpis as $kpi)
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card stat-card {{ $kpi['gc'] }} h-100">
            <div class="card-body d-flex align-items-center gap-2">
                <div class="stat-icon-wrap"><i class="bi {{ $kpi['icon'] }}"></i></div>
                <div>
                    <div class="stat-label">{{ $kpi['label'] }}</div>
                    <div class="stat-value">{{ number_format($kpi['value']) }}</div>
                </div>
                <i class="bi {{ $kpi['icon'] }} stat-bg-icon"></i>
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
                <div class="rev-value text-success">Rp {{ number_format($stats['revenue'], 0, ',', '.') }}</div>
                <i class="bi bi-graph-up-arrow rev-icon text-success"></i>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="card rev-card" style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border-left:4px solid #f59e0b;">
            <div class="card-body">
                <div class="rev-label text-warning">Outstanding Piutang</div>
                <div class="rev-value text-warning">Rp {{ number_format($stats['outstanding'], 0, ',', '.') }}</div>
                <i class="bi bi-wallet2 rev-icon text-warning"></i>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="card rev-card" style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border-left:4px solid #3b82f6;">
            <div class="card-body">
                <div class="rev-label text-primary">Sisa Deposit</div>
                <div class="rev-value text-primary">Rp {{ number_format($depositMetrics['saldo_total'], 0, ',', '.') }}</div>
                <i class="bi bi-wallet-fill rev-icon text-primary"></i>
            </div>
        </div>
    </div>
</div>

{{-- ── Alert Deposit Rendah ── --}}
@if($lowDepositAlert->count() > 0)
<div class="alert alert-dismissible fade show mb-3" role="alert"
     style="border:none;border-radius:12px;background:linear-gradient(135deg,#fffbeb,#fef3c7);border-left:4px solid #f59e0b;padding:.9rem 1.1rem;">
    <div class="d-flex align-items-start gap-2">
        <i class="bi bi-exclamation-triangle-fill text-warning flex-shrink-0 mt-1" style="font-size:1rem"></i>
        <div class="flex-grow-1">
            <div style="font-weight:700;font-size:.85rem;color:#92400e">
                {{ $lowDepositAlert->count() }} partner saldo deposit rendah
            </div>
            <div class="d-flex flex-wrap gap-2 mt-2">
                @foreach($lowDepositAlert as $p)
                <a href="{{ $p['topup_url'] }}"
                   class="text-decoration-none d-inline-flex align-items-center gap-1 px-2 py-1"
                   style="background:#fef3c7;border:1px solid #f59e0b;border-radius:7px;font-size:.75rem;font-weight:600;color:#92400e;">
                    {{ $p['name'] }}: Rp {{ number_format($p['balance'], 0, ',', '.') }}
                    <i class="bi bi-arrow-up-right" style="font-size:.65rem"></i>
                </a>
                @endforeach
            </div>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ── Anomaly Alert ── --}}
@if($highAnomalyAlert && $latestImport)
<div class="alert alert-dismissible fade show mb-3" role="alert"
     style="border:none;border-radius:12px;background:linear-gradient(135deg,#fef2f2,#fee2e2);border-left:4px solid #ef4444;padding:.9rem 1.1rem;">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill text-danger flex-shrink-0" style="font-size:1rem"></i>
        <div style="font-size:.85rem">
            <span class="fw-bold" style="color:#991b1b">Anomaly Rate Tinggi!</span>
            Import <em>{{ $latestImport->original_filename }}</em> — <strong>{{ $latestImport->anomalyRate() }}%</strong>.
            <a href="{{ route('imports.show', $latestImport) }}" class="fw-semibold ms-1" style="color:#dc2626">Review →</a>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ── Deposit + Import Row ── --}}
<div class="row g-2 mb-3">
    {{-- Deposit per Partner ── mobile-friendly list --}}
    <div class="col-lg-8">
        <div class="card table-card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-bold d-flex align-items-center gap-2" style="font-size:.88rem">
                    <i class="bi bi-wallet2 text-primary"></i> Saldo Deposit Partner
                </span>
                <span class="badge" style="background:#eff6ff;color:#1d4ed8;font-size:.68rem;border-radius:7px;">
                    {{ $partnerDeposits->count() }} aktif
                </span>
            </div>
            @if($partnerDeposits->isEmpty())
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                Tidak ada partner aktif
            </div>
            @else
            @php
                $maxBalance = $partnerDeposits->max('balance') ?: 1;
                $LOW_VW     = 5_000_000;
            @endphp
            <div class="p-0">
                @foreach($partnerDeposits as $p)
                @php
                    $bal = $p['balance'];
                    $low = $bal < $LOW_VW;
                    $pct = $maxBalance > 0 ? min(100, ($bal / $maxBalance) * 100) : 0;
                @endphp
                <div class="deposit-item">
                    <span class="deposit-dot" style="background:{{ $low ? '#f59e0b' : '#22c55e' }}"></span>
                    <div class="deposit-name">
                        {{ $p['name'] }}
                        @if($low)
                            <span class="badge ms-1" style="background:#fef3c7;color:#92400e;font-size:.58rem;border-radius:4px;">RENDAH</span>
                        @endif
                    </div>
                    <div class="deposit-bar-wrap d-none d-sm-block">
                        <div class="deposit-progress">
                            <div class="bar" style="width:{{ $pct }}%;background:{{ $low ? '#f59e0b' : '#22c55e' }}"></div>
                        </div>
                    </div>
                    <div class="deposit-amount" style="color:{{ $low ? '#d97706' : '#059669' }}">
                        Rp {{ number_format($bal, 0, ',', '.') }}
                    </div>
                    <a href="{{ $p['topup_url'] }}"
                       class="btn btn-sm"
                       style="font-size:.7rem;border-radius:6px;padding:.2rem .55rem;white-space:nowrap;{{ $low ? 'background:#fef3c7;color:#92400e;border:1px solid #f59e0b;' : 'background:#f0fdf4;color:#166534;border:1px solid #22c55e;' }}">
                        <i class="bi bi-plus-lg"></i>
                        <span class="d-none d-sm-inline">Top-up</span>
                    </a>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Import Mini + Quick Actions ── --}}
    <div class="col-lg-4 d-flex flex-column gap-2">
        <div class="card import-mini card-clean">
            <div class="card-body">
                <div class="sect-label mb-2">Status Import</div>
                <div class="row g-2">
                    <div class="col-6">
                        <div style="background:#fffbeb;border-radius:10px;padding:.75rem .9rem;border:1px solid #fde68a;">
                            <div class="im-label" style="color:#92400e">Pending</div>
                            <div class="im-value {{ $pendingImports > 0 ? 'text-warning' : 'text-muted' }}">{{ number_format($pendingImports) }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div style="background:#fef2f2;border-radius:10px;padding:.75rem .9rem;border:1px solid #fecaca;">
                            <div class="im-label" style="color:#991b1b">Anomaly</div>
                            <div class="im-value {{ $pendingAnomalies > 0 ? 'text-danger' : 'text-muted' }}">{{ number_format($pendingAnomalies) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-clean flex-grow-1">
            <div class="card-body">
                <div class="sect-label mb-2">Aksi Cepat</div>
                <div class="d-grid gap-2">
                    <a href="{{ route('imports.create') }}" class="btn btn-primary btn-sm"
                       style="background:linear-gradient(135deg,#3b82f6,#2563eb);border:none;box-shadow:0 2px 8px rgba(59,130,246,.28);border-radius:9px;">
                        <i class="bi bi-upload me-1"></i>Upload Import
                    </a>
                    <a href="{{ route('pending-invoices.index') }}" class="btn btn-outline-secondary btn-sm" style="border-color:#e2e8f0;color:#475569;border-radius:9px;">
                        <i class="bi bi-hourglass-split me-1"></i>Antrian Invoice
                        @if(($pendingCount ?? 0) > 0)
                            <span class="badge ms-1" style="background:#ef4444;font-size:.6rem;border-radius:5px;">{{ $pendingCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('imports.index') }}" class="btn btn-outline-secondary btn-sm" style="border-color:#e2e8f0;color:#475569;border-radius:9px;">
                        <i class="bi bi-list-ul me-1"></i>Semua Import
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Credit Widgets ── --}}
@if($creditOutstanding > 0 || $overLimitPartners->count() > 0 || $top5Outstanding->count() > 0)
<div class="sect-label mb-2 mt-1" style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#94a3b8;">
    <i class="bi bi-shield-lock-fill me-1" style="color:#6366f1"></i>Kredit
</div>

{{-- Row 1: Total Outstanding + Over Limit --}}
<div class="row g-2 mb-2">
    {{-- Widget 1: Total Credit Outstanding --}}
    <div class="col-12 col-sm-6 col-lg-4">
        <div class="card rev-card h-100" style="background:linear-gradient(135deg,#f5f3ff,#ede9fe);border-left:4px solid #8b5cf6;">
            <div class="card-body">
                <div class="rev-label" style="color:#7c3aed">Total Kredit Outstanding</div>
                <div class="rev-value" style="color:#6d28d9">Rp {{ number_format($creditOutstanding, 0, ',', '.') }}</div>
                <div style="font-size:.67rem;color:#8b5cf6;margin-top:3px;">
                    {{ $top5Outstanding->count() }} credit partner aktif
                </div>
                <i class="bi bi-credit-card-fill rev-icon" style="color:#8b5cf6"></i>
            </div>
        </div>
    </div>

    {{-- Widget 2: Partner Over Limit --}}
    <div class="col-12 col-sm-6 col-lg-4">
        @php $overCount = $overLimitPartners->count(); @endphp
        <div class="card rev-card h-100"
             style="background:{{ $overCount > 0 ? 'linear-gradient(135deg,#fef2f2,#fee2e2)' : 'linear-gradient(135deg,#f0fdf4,#dcfce7)' }};
                    border-left:4px solid {{ $overCount > 0 ? '#ef4444' : '#22c55e' }};">
            <div class="card-body">
                <div class="rev-label" style="color:{{ $overCount > 0 ? '#991b1b' : '#166534' }}">Partner Over Limit</div>
                <div class="rev-value" style="color:{{ $overCount > 0 ? '#dc2626' : '#16a34a' }}">{{ $overCount }}</div>
                @if($overCount > 0)
                <div class="mt-2">
                    <a class="text-decoration-none d-flex align-items-center gap-1" data-bs-toggle="collapse"
                       href="#overLimitList" style="font-size:.72rem;font-weight:600;color:#dc2626;">
                        <i class="bi bi-chevron-down" style="font-size:.6rem"></i> Lihat daftar
                    </a>
                    <div class="collapse mt-1" id="overLimitList">
                        @foreach($overLimitPartners as $op)
                        <a href="{{ route('partners.show', $op->id) }}"
                           class="d-block text-decoration-none py-1"
                           style="font-size:.73rem;color:#dc2626;border-bottom:1px solid #fecaca;">
                            {{ $op->nama_partner }}
                            <span class="text-muted" style="font-size:.66rem">
                                — Rp {{ number_format($op->credit_used_computed, 0, ',', '.') }}
                                / {{ number_format((float)$op->limit_credit, 0, ',', '.') }}
                            </span>
                        </a>
                        @endforeach
                    </div>
                </div>
                @else
                <div style="font-size:.67rem;color:#16a34a;margin-top:3px;">Semua dalam batas normal</div>
                @endif
                <i class="bi bi-exclamation-triangle-fill rev-icon" style="color:{{ $overCount > 0 ? '#ef4444' : '#22c55e' }}"></i>
            </div>
        </div>
    </div>

    {{-- Widget 3: Credit Breakdown per Class --}}
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center gap-2" style="padding:.75rem 1rem;">
                <i class="bi bi-award-fill" style="color:#8b5cf6;font-size:.9rem"></i>
                <span class="fw-bold" style="font-size:.82rem">Kredit per Class</span>
            </div>
            <div class="card-body p-0">
                @php $maxClassOut = $creditByClass->max('outstanding') ?: 1; @endphp
                @forelse($creditByClass as $cls)
                @php
                    $clsPct = $maxClassOut > 0 ? min(100, ($cls['outstanding'] / $maxClassOut) * 100) : 0;
                    $clsColorMap = ['primary'=>'#3b82f6','success'=>'#22c55e','warning'=>'#f59e0b','danger'=>'#ef4444','secondary'=>'#94a3b8','info'=>'#06b6d4','dark'=>'#1e293b'];
                    $clsHex = $clsColorMap[$cls['color']] ?? '#8b5cf6';
                @endphp
                <div style="padding:.6rem 1rem;border-bottom:1px solid #f1f5f9;">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge bg-{{ $cls['color'] }}" style="font-size:.63rem;border-radius:5px;">{{ $cls['name'] }}</span>
                            <span style="font-size:.67rem;color:#94a3b8">{{ $cls['count'] }} partner</span>
                        </div>
                        <span style="font-size:.73rem;font-weight:700;color:#1e293b">
                            Rp {{ number_format($cls['outstanding'], 0, ',', '.') }}
                        </span>
                    </div>
                    <div style="height:4px;background:#f1f5f9;border-radius:10px;overflow:hidden;">
                        <div style="height:100%;width:{{ $clsPct }}%;background:{{ $clsHex }};border-radius:10px;"></div>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-4" style="font-size:.8rem">
                    <i class="bi bi-inbox d-block fs-3 mb-1 opacity-40"></i>Tidak ada data kredit
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Widget 4: Top 5 Highest Outstanding --}}
@if($top5Outstanding->count() > 0)
<div class="card mb-3">
    <div class="card-header d-flex align-items-center gap-2" style="padding:.75rem 1rem;">
        <i class="bi bi-bar-chart-fill" style="color:#8b5cf6;font-size:.9rem"></i>
        <span class="fw-bold" style="font-size:.82rem">Top 5 Kredit Tertinggi</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0" style="font-size:.78rem;">
            <thead style="background:#f8fafc;">
                <tr>
                    <th style="padding:.5rem .9rem;font-weight:600;color:#64748b;border-bottom:1px solid #e2e8f0;">Partner</th>
                    <th style="padding:.5rem .6rem;font-weight:600;color:#64748b;border-bottom:1px solid #e2e8f0;" class="d-none d-sm-table-cell">Class</th>
                    <th style="padding:.5rem .6rem;font-weight:600;color:#64748b;border-bottom:1px solid #e2e8f0;text-align:right;">Outstanding</th>
                    <th style="padding:.5rem .6rem;font-weight:600;color:#64748b;border-bottom:1px solid #e2e8f0;text-align:right;" class="d-none d-md-table-cell">Limit</th>
                    <th style="padding:.5rem .9rem;font-weight:600;color:#64748b;border-bottom:1px solid #e2e8f0;min-width:100px;">Utilisasi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($top5Outstanding as $tp)
                @php
                    $utilColor = $tp['util_pct'] > 100 ? '#ef4444' : ($tp['util_pct'] >= 80 ? '#f59e0b' : '#22c55e');
                    $utilBg    = $tp['util_pct'] > 100 ? '#fee2e2' : ($tp['util_pct'] >= 80 ? '#fef3c7' : '#f0fdf4');
                    $barWidth  = min(100, $tp['util_pct']);
                @endphp
                <tr>
                    <td style="padding:.55rem .9rem;">
                        <a href="{{ route('partners.show', $tp['id']) }}" class="text-decoration-none fw-semibold" style="color:#1e293b;">
                            {{ $tp['name'] }}
                        </a>
                    </td>
                    <td style="padding:.55rem .6rem;" class="d-none d-sm-table-cell">
                        @if($tp['class_name'])
                        <span class="badge bg-{{ $tp['class_color'] ?? 'secondary' }}" style="font-size:.62rem;border-radius:5px;">
                            {{ $tp['class_name'] }}
                        </span>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td style="padding:.55rem .6rem;text-align:right;font-weight:700;color:#1e293b;">
                        Rp {{ number_format($tp['used'], 0, ',', '.') }}
                    </td>
                    <td style="padding:.55rem .6rem;text-align:right;color:#64748b;" class="d-none d-md-table-cell">
                        Rp {{ number_format($tp['limit'], 0, ',', '.') }}
                    </td>
                    <td style="padding:.55rem .9rem;">
                        <div class="d-flex align-items-center gap-2">
                            <div style="flex:1;height:6px;background:#e2e8f0;border-radius:10px;overflow:hidden;min-width:50px;">
                                <div style="height:100%;width:{{ $barWidth }}%;background:{{ $utilColor }};border-radius:10px;"></div>
                            </div>
                            <span style="font-size:.7rem;font-weight:700;color:{{ $utilColor }};white-space:nowrap;
                                         background:{{ $utilBg }};padding:.1rem .35rem;border-radius:5px;">
                                {{ $tp['util_pct'] }}%
                            </span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endif

{{-- ── Widget: Partner Perlu Ditagih ── --}}
@if($needCollectionPartners->count() > 0)
<div class="card mb-3" style="border:none;border-radius:12px;box-shadow:0 1px 3px rgba(15,23,41,.07);overflow:hidden;">
    <div class="card-header d-flex align-items-center justify-content-between" style="background:#fff;border-bottom:1px solid #f1f5f9;padding:.75rem 1rem;">
        <span class="fw-bold d-flex align-items-center gap-2" style="font-size:.88rem">
            <i class="bi bi-bell-fill" style="color:#f59e0b"></i> Partner dengan Outstanding Kredit
        </span>
        <a href="{{ route('payment-memos.index') }}" class="btn btn-xs btn-sm btn-outline-secondary" style="font-size:.72rem;padding:.2rem .55rem">
            Lihat semua →
        </a>
    </div>
    <div class="card-body p-0">
        @foreach($needCollectionPartners as $np)
        <div class="d-flex align-items-center gap-3 px-3 py-2" style="border-bottom:1px solid #f8fafc;">
            <div style="flex:1;min-width:0;">
                <a href="{{ route('partners.show', $np['id']) }}" class="fw-semibold text-decoration-none text-dark d-block" style="font-size:.84rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    {{ $np['name'] }}
                </a>
                <span style="font-size:.72rem;color:#64748b;">
                    @if($np['overdue_count'] > 0)
                        <span class="text-danger fw-semibold">{{ $np['overdue_count'] }} invoice OVERDUE</span>
                    @elseif($np['days_to_due'] !== null && $np['days_to_due'] <= 7)
                        <span class="text-warning fw-semibold">Jatuh tempo {{ $np['days_to_due'] }} hari lagi</span>
                    @else
                        Ada invoice outstanding
                    @endif
                </span>
            </div>
            <div class="text-end" style="white-space:nowrap;">
                <div class="fw-bold text-danger" style="font-size:.84rem;">Rp {{ number_format($np['outstanding'], 0, ',', '.') }}</div>
            </div>
            <a href="{{ $np['memo_url'] }}" class="btn btn-sm btn-outline-warning" style="font-size:.72rem;padding:.25rem .6rem;white-space:nowrap;">
                <i class="bi bi-file-earmark-plus me-1"></i> Buat Memo
            </a>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ── Antrian + Due Soon ── --}}
<div class="row g-2">

    {{-- Antrian Invoice Belum Dibuat --}}
    <div class="col-12 col-lg-6">
        <div class="card table-card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-bold d-flex align-items-center gap-2" style="font-size:.88rem">
                    <i class="bi bi-hourglass-split text-warning"></i> Antrian Invoice Belum Dibuat
                </span>
                <div class="d-flex align-items-center gap-2">
                    @if($pendingCount > 0)
                    <span class="badge" style="background:#ef4444;font-size:.68rem;border-radius:6px;">
                        {{ number_format($pendingCount) }}
                    </span>
                    @endif
                    <a href="{{ route('pending-invoices.index') }}" class="btn btn-sm btn-outline-warning"
                       style="border-radius:7px;font-size:.74rem;padding:.2rem .6rem;">
                        Semua <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            @forelse($pendingQueue as $q)
            @php $qDate = \Carbon\Carbon::parse($q->date); @endphp
            <a href="{{ route('invoices.create', ['transaction_no' => $q->transaction_no, 'visit_date' => $qDate->format('Y-m-d')]) }}"
               class="queue-item">
                <div class="flex-grow-1 min-width-0">
                    <div class="q-no">{{ $q->transaction_no }}</div>
                    <div class="q-sub">
                        {{ $qDate->format('d/m/Y') }}
                        @if($q->ticket_names)
                            · {{ \Illuminate\Support\Str::limit($q->ticket_names, 40) }}
                        @endif
                    </div>
                </div>
                <div class="text-end flex-shrink-0">
                    <div class="q-amt">Rp {{ number_format($q->total_amount, 0, ',', '.') }}</div>
                    <div class="q-sub mt-1">{{ $q->item_count }} item · <span style="color:#2563eb">Buat →</span></div>
                </div>
            </a>
            @empty
            <div class="text-center py-5 text-muted">
                <i class="bi bi-check-circle fs-2 d-block mb-2 opacity-50 text-success"></i>
                <span style="font-size:.83rem">Tidak ada antrian invoice</span>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Invoice Mendekati Due Date --}}
    <div class="col-12 col-lg-6">
        <div class="card table-card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-bold d-flex align-items-center gap-2" style="font-size:.88rem">
                    <i class="bi bi-alarm text-danger"></i> Invoice Mendekati Due Date
                </span>
                <a href="{{ route('invoices.index', ['payment_status' => 'UNPAID']) }}" class="btn btn-sm btn-outline-danger"
                   style="border-radius:7px;font-size:.74rem;padding:.2rem .6rem;">
                    Semua <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            @forelse($dueSoonInvoices as $inv)
            @php
                $daysLeft  = (int) \Carbon\Carbon::today()->diffInDays($inv->due_date, false);
                $isOverdue = $daysLeft < 0;

                if ($isOverdue) {
                    $dueCls  = 'due-overdue';
                    $dueText = 'LEWAT ' . abs($daysLeft) . 'h';
                } elseif ($daysLeft === 0) {
                    $dueCls  = 'due-today';
                    $dueText = 'HARI INI';
                } elseif ($daysLeft === 1) {
                    $dueCls  = 'due-warn1';
                    $dueText = 'BESOK';
                } elseif ($daysLeft <= 3) {
                    $dueCls  = 'due-warn3';
                    $dueText = 'H-' . $daysLeft;
                } else {
                    $dueCls  = 'due-normal';
                    $dueText = 'H-' . $daysLeft;
                }

                $statusBadge = match($inv->payment_status) {
                    'OVERDUE' => 'badge-overdue',
                    'PARTIAL' => 'badge-partial',
                    default   => 'badge-unpaid',
                };
                $statusLabel = match($inv->payment_status) {
                    'OVERDUE' => 'Jatuh Tempo',
                    'PARTIAL' => 'Partial',
                    default   => 'Belum Bayar',
                };
            @endphp
            <a href="{{ route('invoices.show', $inv) }}" class="due-item">
                <div class="flex-grow-1 min-width-0">
                    <div class="q-no">{{ $inv->invoice_no }}</div>
                    <div class="q-sub">
                        {{ $inv->partner->nama_partner ?? '-' }}
                        · Due: {{ $inv->due_date?->format('d/m/Y') }}
                    </div>
                </div>
                <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                    <span class="due-badge {{ $dueCls }}">{{ $dueText }}</span>
                    <span class="q-amt">Rp {{ number_format($inv->grand_total, 0, ',', '.') }}</span>
                    <span class="badge {{ $statusBadge }}" style="font-size:.6rem">{{ $statusLabel }}</span>
                </div>
            </a>
            @empty
            <div class="text-center py-5 text-muted">
                <i class="bi bi-check-circle fs-2 d-block mb-2 opacity-50 text-success"></i>
                <span style="font-size:.83rem">Tidak ada invoice mendekati jatuh tempo</span>
            </div>
            @endforelse
        </div>
    </div>

</div>

{{-- ── Phase 10: Reservation Stats Widget ── --}}
@php
    $todayRes   = \App\Models\Reservation::whereDate('visit_date', today())->count();
    $pendingAno = \App\Models\ReservationAnomaly::where('is_resolved', false)->count();
    $heldComm   = \App\Models\ReservationPayment::where('is_commission_held', true)->whereNull('commission_released_at')->count();
    $suspPartners = \App\Models\Partner::where('reservation_suspended', true)->count();
@endphp
@if($todayRes > 0 || $pendingAno > 0 || $heldComm > 0)
<div class="card mb-3" style="border:none;border-radius:12px;box-shadow:0 1px 3px rgba(15,23,41,.07);overflow:hidden;">
    <div class="card-header d-flex align-items-center justify-content-between" style="background:#fff;border-bottom:1px solid #f1f5f9;padding:.75rem 1rem;">
        <span style="font-weight:700;font-size:.88rem;color:#1e293b;"><i class="bi bi-calendar-check me-2 text-primary"></i>Reservasi Hari Ini</span>
        <a href="{{ route('reservations.index') }}" style="font-size:.76rem;color:#3b82f6;text-decoration:none;">Lihat Semua →</a>
    </div>
    <div class="card-body p-0">
        <div class="row g-0 text-center">
            <div class="col-3 py-3" style="border-right:1px solid #f1f5f9;">
                <div style="font-size:1.4rem;font-weight:800;color:#3b82f6;">{{ $todayRes }}</div>
                <div style="font-size:.72rem;color:#94a3b8;">Tamu Hari Ini</div>
            </div>
            <div class="col-3 py-3" style="border-right:1px solid #f1f5f9;">
                <div style="font-size:1.4rem;font-weight:800;color:{{ $pendingAno > 0 ? '#ef4444' : '#22c55e' }};">{{ $pendingAno }}</div>
                <div style="font-size:.72rem;color:#94a3b8;">Anomali Pending</div>
            </div>
            <div class="col-3 py-3" style="border-right:1px solid #f1f5f9;">
                <div style="font-size:1.4rem;font-weight:800;color:{{ $heldComm > 0 ? '#f59e0b' : '#22c55e' }};">{{ $heldComm }}</div>
                <div style="font-size:.72rem;color:#94a3b8;">Komisi Di-hold</div>
            </div>
            <div class="col-3 py-3">
                <div style="font-size:1.4rem;font-weight:800;color:{{ $suspPartners > 0 ? '#ef4444' : '#22c55e' }};">{{ $suspPartners }}</div>
                <div style="font-size:.72rem;color:#94a3b8;">Partner Suspended</div>
            </div>
        </div>
        @if($pendingAno > 0)
        <div class="px-3 pb-3">
            <a href="{{ route('anomalies.index', ['unresolved_only' => 1]) }}" class="btn btn-sm btn-outline-danger w-100">
                <i class="bi bi-shield-exclamation me-1"></i> {{ $pendingAno }} Anomali Perlu Ditindak
            </a>
        </div>
        @endif
        @if($heldComm > 0)
        <div class="px-3 pb-3">
            <a href="{{ route('commission-review.index') }}" class="btn btn-sm btn-outline-warning w-100">
                <i class="bi bi-lock me-1"></i> {{ $heldComm }} Komisi Menunggu Review
            </a>
        </div>
        @endif
    </div>
</div>
@endif

@endsection
