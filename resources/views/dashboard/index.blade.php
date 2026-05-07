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

@endsection
