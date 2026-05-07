@extends('layouts.app')

@section('title', 'Scorecard Partner')
@section('page-title', 'Scorecard Partner')

@push('styles')
<style>
    /* ── Grade summary cards ── */
    .grade-card {
        border-radius: 14px;
        padding: 1.1rem 1rem;
        display: flex; align-items: center; gap: .9rem;
        border: 1px solid transparent;
    }
    .grade-card .grade-icon {
        width: 44px; height: 44px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.15rem; font-weight: 800; flex-shrink: 0;
    }
    .grade-card .grade-count { font-size: 1.7rem; font-weight: 800; line-height: 1; }
    .grade-card .grade-label { font-size: .75rem; color: #64748b; margin-top: 1px; }

    .grade-a { background: #f0fdf4; border-color: #bbf7d0; }
    .grade-a .grade-icon { background: #dcfce7; color: #16a34a; }
    .grade-a .grade-count { color: #16a34a; }

    .grade-b { background: #eff6ff; border-color: #bfdbfe; }
    .grade-b .grade-icon { background: #dbeafe; color: #2563eb; }
    .grade-b .grade-count { color: #2563eb; }

    .grade-c { background: #fffbeb; border-color: #fde68a; }
    .grade-c .grade-icon { background: #fef3c7; color: #d97706; }
    .grade-c .grade-count { color: #d97706; }

    .grade-d { background: #fff1f2; border-color: #fecdd3; }
    .grade-d .grade-icon { background: #fee2e2; color: #dc2626; }
    .grade-d .grade-count { color: #dc2626; }

    /* ── Stats strip ── */
    .stats-strip {
        background: #fff;
        border: 1px solid #e8edf5;
        border-radius: 12px;
        padding: .75rem 1.1rem;
        display: flex; flex-wrap: wrap; gap: .5rem 2rem;
        align-items: center;
        font-size: .82rem;
        box-shadow: 0 1px 4px rgba(15,23,41,.04);
    }
    .stats-strip .stat-item { display: flex; align-items: center; gap: .4rem; }
    .stats-strip .stat-label { color: #94a3b8; }
    .stats-strip .stat-value { font-weight: 700; color: #1e293b; }

    /* ── Filter bar ── */
    .filter-bar {
        background: #fff;
        border: 1px solid #e8edf5;
        border-radius: 12px;
        padding: .75rem 1rem;
        box-shadow: 0 1px 4px rgba(15,23,41,.04);
    }
    .filter-bar .form-select {
        border-radius: 8px; border-color: #e2e8f0;
        font-size: .82rem; padding: .38rem .7rem;
        background: #f8fafc;
    }
    .filter-bar .form-select:focus {
        background: #fff; border-color: #818cf8; box-shadow: 0 0 0 3px rgba(129,140,248,.15);
    }

    /* ── Main card ── */
    .perf-card {
        border-radius: 14px;
        border: 1px solid #e8edf5;
        box-shadow: 0 2px 8px rgba(15,23,41,.06);
        overflow: hidden; background: #fff;
    }
    .perf-table thead th {
        background: #f1f5fd;
        font-size: .64rem; font-weight: 700;
        letter-spacing: .55px; text-transform: uppercase;
        color: #6b7a99; padding: .7rem .8rem;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }
    .perf-table tbody tr { transition: background .1s; }
    .perf-table tbody tr:hover { background: #f7f8ff; }
    .perf-table tbody td {
        padding: .65rem .8rem;
        font-size: .82rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .perf-table tbody tr:last-child td { border-bottom: none; }

    /* Row accent by grade */
    .perf-table tbody tr td:first-child { border-left: 3px solid transparent; }
    .perf-table tbody tr.row-grade-A td:first-child { border-left-color: #16a34a; }
    .perf-table tbody tr.row-grade-B td:first-child { border-left-color: #2563eb; }
    .perf-table tbody tr.row-grade-C td:first-child { border-left-color: #d97706; }
    .perf-table tbody tr.row-grade-D td:first-child { border-left-color: #dc2626; }

    /* ── Grade badge ── */
    .grade-badge {
        display: inline-flex; align-items: center; justify-content: center;
        width: 26px; height: 26px; border-radius: 8px;
        font-size: .78rem; font-weight: 800;
    }
    .grade-badge-A { background: #dcfce7; color: #16a34a; }
    .grade-badge-B { background: #dbeafe; color: #2563eb; }
    .grade-badge-C { background: #fef3c7; color: #d97706; }
    .grade-badge-D { background: #fee2e2; color: #dc2626; }
    .grade-badge-NA{ background: #f1f5f9; color: #94a3b8; }

    /* ── Type badges ── */
    .badge-type { font-size: .66rem; font-weight: 600; padding: .22em .6em; border-radius: 6px; }
    .badge-hotel    { background: #e0f9ff; color: #0891b2; }
    .badge-travel   { background: #fef3c7; color: #b45309; }
    .badge-tourdesk { background: #d1fae5; color: #065f46; }
    .badge-other    { background: #f1f5f9; color: #64748b; }

    /* ── Progress bar ── */
    .ontime-bar { height: 6px; border-radius: 99px; background: #e2e8f0; overflow: hidden; min-width: 60px; }
    .ontime-bar-fill { height: 100%; border-radius: 99px; transition: width .3s; }

    /* ── Credit util ── */
    .cu-safe { color: #16a34a; font-weight: 600; }
    .cu-warn { color: #d97706; font-weight: 600; }
    .cu-high { color: #ea580c; font-weight: 600; }
    .cu-over { color: #dc2626; font-weight: 700; }

    /* ── View button ── */
    .btn-view {
        width: 28px; height: 28px; border-radius: 7px;
        border: 1px solid #e2e8f0; background: #f8fafc; color: #64748b;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .78rem; text-decoration: none;
        transition: background .12s, color .12s, border-color .12s;
    }
    .btn-view:hover { background: #eff6ff; color: #4f46e5; border-color: #c7d2fe; }

    /* ── Legend ── */
    .legend-card {
        border-radius: 12px; border: 1px solid #e8edf5;
        background: #fff; overflow: hidden;
        box-shadow: 0 1px 4px rgba(15,23,41,.04);
    }
    .legend-header {
        padding: .6rem 1rem; background: #f8fafc;
        font-size: .75rem; font-weight: 700; color: #6b7a99;
        letter-spacing: .4px; text-transform: uppercase;
        border-bottom: 1px solid #e8edf5;
    }
    .legend-item { padding: .75rem 1rem; border-bottom: 1px solid #f1f5f9; }
    .legend-item:last-child { border-bottom: none; }

    /* ── Empty ── */
    .empty-state { padding: 3rem 1rem; text-align: center; color: #94a3b8; }
    .empty-state .bi { font-size: 2rem; opacity: .4; }
</style>
@endpush

@section('content')

@php
    $gradeA           = $scorecards->where('risk_grade', 'A')->count();
    $gradeB           = $scorecards->where('risk_grade', 'B')->count();
    $gradeC           = $scorecards->where('risk_grade', 'C')->count();
    $gradeD           = $scorecards->where('risk_grade', 'D')->count();
    $totalOutstanding = $scorecards->sum('outstanding');
    $avgOnTime        = $scorecards->whereNotNull('on_time_rate')->avg('on_time_rate');
@endphp

{{-- ── Header ── --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <div style="font-size:1.1rem;font-weight:700;color:#1e293b;">Scorecard Pembayaran Partner</div>
        <div style="font-size:.78rem;color:#94a3b8;margin-top:1px;">{{ $scorecards->count() }} partner dianalisis</div>
    </div>
    <a href="{{ route('partners.index') }}" class="btn btn-sm d-flex align-items-center gap-1"
       style="border-radius:8px;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;font-size:.8rem;">
        <i class="bi bi-arrow-left"></i>
        <span class="d-none d-sm-inline">Kembali</span>
    </a>
</div>

{{-- ── Grade summary cards ── --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="grade-card grade-a h-100">
            <div class="grade-icon">A</div>
            <div>
                <div class="grade-count">{{ $gradeA }}</div>
                <div class="fw-semibold" style="font-size:.82rem;color:#16a34a;">Grade A</div>
                <div class="grade-label">Sangat Baik</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="grade-card grade-b h-100">
            <div class="grade-icon">B</div>
            <div>
                <div class="grade-count">{{ $gradeB }}</div>
                <div class="fw-semibold" style="font-size:.82rem;color:#2563eb;">Grade B</div>
                <div class="grade-label">Baik</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="grade-card grade-c h-100">
            <div class="grade-icon">C</div>
            <div>
                <div class="grade-count">{{ $gradeC }}</div>
                <div class="fw-semibold" style="font-size:.82rem;color:#d97706;">Grade C</div>
                <div class="grade-label">Perlu Perhatian</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="grade-card grade-d h-100">
            <div class="grade-icon">D</div>
            <div>
                <div class="grade-count">{{ $gradeD }}</div>
                <div class="fw-semibold" style="font-size:.82rem;color:#dc2626;">Grade D</div>
                <div class="grade-label">Risiko Tinggi</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Stats strip ── --}}
<div class="stats-strip mb-3">
    <div class="stat-item">
        <span class="stat-label">Total partner</span>
        <span class="stat-value">{{ $scorecards->count() }}</span>
    </div>
    <div style="width:1px;height:18px;background:#e2e8f0;"></div>
    <div class="stat-item">
        <span class="stat-label">Avg on-time</span>
        <span class="stat-value" style="color:{{ $avgOnTime !== null && $avgOnTime >= 70 ? '#16a34a' : '#d97706' }}">
            {{ $avgOnTime !== null ? number_format($avgOnTime, 1).'%' : '—' }}
        </span>
    </div>
    <div style="width:1px;height:18px;background:#e2e8f0;"></div>
    <div class="stat-item">
        <span class="stat-label">Total outstanding</span>
        <span class="stat-value" style="color:{{ $totalOutstanding > 0 ? '#dc2626' : '#16a34a' }}">
            Rp {{ number_format($totalOutstanding, 0, ',', '.') }}
        </span>
    </div>
</div>

{{-- ── Filter ── --}}
<form method="GET" class="filter-bar mb-3">
    <div class="row g-2 align-items-center">
        <div class="col-sm-4 col-md-3">
            <select name="type" class="form-select form-select-sm">
                <option value="">Semua Tipe</option>
                @foreach(['HOTEL','TRAVEL','TOURDESK'] as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-4 col-md-3">
            <select name="risk" class="form-select form-select-sm">
                <option value="">Semua Grade</option>
                @foreach(['A','B','C','D'] as $g)
                    <option value="{{ $g }}" @selected(request('risk') === $g)>Grade {{ $g }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto d-flex gap-2">
            <button class="btn btn-sm" type="submit"
                    style="border-radius:8px;padding:.35rem .8rem;background:#4f46e5;color:#fff;border:none;font-size:.8rem;">
                <i class="bi bi-funnel me-1"></i>Filter
            </button>
            @if(request()->hasAny(['type','risk']))
            <a href="{{ route('partners.performance') }}" class="btn btn-sm btn-outline-secondary"
               style="border-radius:8px;padding:.35rem .7rem;font-size:.8rem;">
                <i class="bi bi-x-lg"></i>
            </a>
            @endif
        </div>
    </div>
</form>

{{-- ── Scorecard table ── --}}
<div class="perf-card mb-3">
    <div class="table-responsive">
        <table class="table perf-table mb-0">
            <thead>
                <tr>
                    <th>Partner</th>
                    <th>Tipe</th>
                    <th class="text-center">Grade</th>
                    <th class="text-center">Invoice</th>
                    <th class="text-center">Tepat</th>
                    <th class="text-center">Lambat</th>
                    <th class="text-center">Belum Bayar</th>
                    <th style="min-width:110px;">On-Time %</th>
                    <th class="text-center">Avg Terlambat</th>
                    <th class="text-end">Outstanding</th>
                    <th class="text-center">Credit Util</th>
                    <th>Terakhir Bayar</th>
                    <th>Kontrak</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($scorecards as $row)
                @php
                    $p = $row['partner'];
                    $gradeClass = 'row-grade-' . $row['risk_grade'];
                    $cu = $row['credit_utilization'];
                    $cuClass = $cu === null ? '' : ($cu > 100 ? 'cu-over' : ($cu > 80 ? 'cu-high' : ($cu > 50 ? 'cu-warn' : 'cu-safe')));
                    $badgeGradeClass = match($row['risk_grade']) {
                        'A' => 'grade-badge-A',
                        'B' => 'grade-badge-B',
                        'C' => 'grade-badge-C',
                        'D' => 'grade-badge-D',
                        default => 'grade-badge-NA',
                    };
                    $typeBadgeClass = match($p->partner_type) {
                        'HOTEL'    => 'badge-hotel',
                        'TRAVEL'   => 'badge-travel',
                        'TOURDESK' => 'badge-tourdesk',
                        default    => 'badge-other',
                    };
                    $barColor = $row['on_time_rate'] !== null
                        ? ($row['on_time_rate'] >= 80 ? '#16a34a' : ($row['on_time_rate'] >= 50 ? '#d97706' : '#dc2626'))
                        : '#e2e8f0';
                    $belumBayar = $row['overdue_count'] + $row['unpaid_count'];
                @endphp
                <tr class="{{ $gradeClass }}">
                    <td>
                        <a href="{{ route('partners.show', $p) }}"
                           class="fw-semibold text-decoration-none" style="color:#1e293b;">
                            {{ $p->nama_partner }}
                        </a>
                        @if(!$p->is_active)
                            <span style="font-size:.64rem;background:#f1f5f9;color:#94a3b8;border-radius:5px;padding:.15em .5em;margin-left:4px;font-weight:600;">Nonaktif</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge-type {{ $typeBadgeClass }}">{{ $p->partner_type }}</span>
                    </td>
                    <td class="text-center">
                        @if($row['risk_grade'] !== 'N/A')
                            <span class="grade-badge {{ $badgeGradeClass }}">{{ $row['risk_grade'] }}</span>
                        @else
                            <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    <td class="text-center" style="color:#64748b;">{{ $row['total_invoices'] }}</td>
                    <td class="text-center" style="color:#16a34a;font-weight:600;">{{ $row['paid_on_time'] }}</td>
                    <td class="text-center" style="color:#d97706;font-weight:600;">{{ $row['paid_late'] }}</td>
                    <td class="text-center">
                        <span style="color:{{ $belumBayar > 0 ? '#dc2626' : '#94a3b8' }};font-weight:{{ $belumBayar > 0 ? '600' : '400' }};">
                            {{ $belumBayar }}
                        </span>
                        @if($row['partial_count'] > 0)
                            <span style="color:#0891b2;margin-left:3px;" title="{{ $row['partial_count'] }} partial">
                                <i class="bi bi-hourglass-split" style="font-size:.7rem;"></i>
                            </span>
                        @endif
                    </td>
                    <td>
                        @if($row['on_time_rate'] !== null)
                            <div class="d-flex align-items-center gap-2">
                                <div class="ontime-bar flex-grow-1">
                                    <div class="ontime-bar-fill" style="width:{{ $row['on_time_rate'] }}%;background:{{ $barColor }};"></div>
                                </div>
                                <span style="font-size:.76rem;font-weight:600;color:{{ $barColor }};white-space:nowrap;">{{ $row['on_time_rate'] }}%</span>
                            </div>
                        @else
                            <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    <td class="text-center" style="color:{{ $row['avg_days_late'] > 0 ? '#d97706' : '#94a3b8' }};">
                        {{ $row['avg_days_late'] > 0 ? '+'.$row['avg_days_late'].' hr' : '—' }}
                    </td>
                    <td class="text-end" style="font-weight:{{ $row['outstanding'] > 0 ? '600' : '400' }};color:{{ $row['outstanding'] > 0 ? '#dc2626' : '#94a3b8' }};">
                        {{ $row['outstanding'] > 0 ? 'Rp '.number_format($row['outstanding'],0,',','.') : '—' }}
                    </td>
                    <td class="text-center">
                        @if($cu !== null)
                            <span class="{{ $cuClass }}">{{ $cu }}%</span>
                        @else
                            <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    <td style="color:#64748b;white-space:nowrap;">
                        {{ $row['last_payment_date']
                            ? \Carbon\Carbon::parse($row['last_payment_date'])->format('d/m/Y')
                            : '—' }}
                    </td>
                    <td style="white-space:nowrap;">
                        @if($p->contract_end)
                            <span style="color:{{ $p->isContractExpiringSoon() ? '#f59e0b' : '#64748b' }};font-weight:{{ $p->isContractExpiringSoon() ? '600' : '400' }};">
                                {{ $p->contract_end->format('d/m/Y') }}
                                @if($p->isContractExpiringSoon())
                                    <i class="bi bi-exclamation-triangle-fill ms-1" style="color:#f59e0b;"></i>
                                @endif
                            </span>
                        @else
                            <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('partners.show', $p) }}" class="btn-view" title="Lihat detail">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="14" class="empty-state">
                        <i class="bi bi-inbox d-block mb-2"></i>
                        Tidak ada data partner.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── Grade legend ── --}}
<div class="legend-card">
    <div class="legend-header">Keterangan Grade & Rekomendasi Renewal</div>
    <div class="row g-0">
        <div class="col-sm-6 col-md-3 legend-item">
            <div class="d-flex align-items-start gap-2">
                <span class="grade-badge grade-badge-A flex-shrink-0">A</span>
                <div>
                    <div class="fw-semibold" style="font-size:.82rem;color:#16a34a;">Sangat Baik</div>
                    <div style="font-size:.76rem;color:#64748b;margin-top:2px;">On-time ≥90%, 0 overdue</div>
                    <div style="font-size:.74rem;color:#94a3b8;font-style:italic;">Perpanjang kontrak</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3 legend-item">
            <div class="d-flex align-items-start gap-2">
                <span class="grade-badge grade-badge-B flex-shrink-0">B</span>
                <div>
                    <div class="fw-semibold" style="font-size:.82rem;color:#2563eb;">Baik</div>
                    <div style="font-size:.76rem;color:#64748b;margin-top:2px;">On-time ≥70%</div>
                    <div style="font-size:.74rem;color:#94a3b8;font-style:italic;">Perpanjang dengan review ringan</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3 legend-item">
            <div class="d-flex align-items-start gap-2">
                <span class="grade-badge grade-badge-C flex-shrink-0">C</span>
                <div>
                    <div class="fw-semibold" style="font-size:.82rem;color:#d97706;">Perlu Perhatian</div>
                    <div style="font-size:.76rem;color:#64748b;margin-top:2px;">On-time ≥50% atau credit util &gt;100%</div>
                    <div style="font-size:.74rem;color:#94a3b8;font-style:italic;">Syarat perpanjangan ketat</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3 legend-item">
            <div class="d-flex align-items-start gap-2">
                <span class="grade-badge grade-badge-D flex-shrink-0">D</span>
                <div>
                    <div class="fw-semibold" style="font-size:.82rem;color:#dc2626;">Risiko Tinggi</div>
                    <div style="font-size:.76rem;color:#64748b;margin-top:2px;">On-time &lt;50% atau ada overdue aktif</div>
                    <div style="font-size:.74rem;color:#94a3b8;font-style:italic;">Evaluasi sebelum perpanjang</div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
