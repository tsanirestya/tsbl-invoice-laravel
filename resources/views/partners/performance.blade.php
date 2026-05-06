@extends('layouts.app')

@section('title', 'Scorecard Partner')
@section('page-title', 'Scorecard Partner')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Scorecard Pembayaran Partner</h5>
    <a href="{{ route('partners.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

@php
    $gradeA          = $scorecards->where('risk_grade', 'A')->count();
    $gradeB          = $scorecards->where('risk_grade', 'B')->count();
    $gradeC          = $scorecards->where('risk_grade', 'C')->count();
    $gradeD          = $scorecards->where('risk_grade', 'D')->count();
    $totalOutstanding = $scorecards->sum('outstanding');
    $avgOnTime       = $scorecards->whereNotNull('on_time_rate')->avg('on_time_rate');
@endphp

{{-- Grade summary cards --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card border-success h-100">
            <div class="card-body text-center py-3">
                <div class="display-6 fw-bold text-success">{{ $gradeA }}</div>
                <div class="fw-semibold">Grade A</div>
                <div class="small text-muted">Sangat Baik (≥90% tepat)</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-primary h-100">
            <div class="card-body text-center py-3">
                <div class="display-6 fw-bold text-primary">{{ $gradeB }}</div>
                <div class="fw-semibold">Grade B</div>
                <div class="small text-muted">Baik (≥70% tepat)</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-warning h-100">
            <div class="card-body text-center py-3">
                <div class="display-6 fw-bold text-warning">{{ $gradeC }}</div>
                <div class="fw-semibold">Grade C</div>
                <div class="small text-muted">Perlu Perhatian (≥50%)</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-danger h-100">
            <div class="card-body text-center py-3">
                <div class="display-6 fw-bold text-danger">{{ $gradeD }}</div>
                <div class="fw-semibold">Grade D</div>
                <div class="small text-muted">Risiko Tinggi (&lt;50%)</div>
            </div>
        </div>
    </div>
</div>

{{-- Overall stats bar --}}
<div class="alert alert-light border small mb-3 d-flex flex-wrap gap-3 align-items-center">
    <span><strong>Total Partner:</strong> {{ $scorecards->count() }}</span>
    <span class="text-muted">|</span>
    <span><strong>Avg On-Time Rate:</strong>
        {{ $avgOnTime !== null ? number_format($avgOnTime, 1).'%' : '—' }}
    </span>
    <span class="text-muted">|</span>
    <span><strong>Total Outstanding:</strong>
        <span class="{{ $totalOutstanding > 0 ? 'text-danger' : '' }}">
            Rp {{ number_format($totalOutstanding, 0, ',', '.') }}
        </span>
    </span>
</div>

{{-- Filter --}}
<form method="GET" class="card card-body mb-3 py-2">
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
        <div class="col-auto">
            <button class="btn btn-sm btn-outline-secondary" type="submit">
                <i class="bi bi-funnel me-1"></i>Filter
            </button>
            <a href="{{ route('partners.performance') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-x"></i>
            </a>
        </div>
    </div>
</form>

{{-- Scorecard table --}}
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle small">
            <thead class="table-light">
                <tr>
                    <th>Partner</th>
                    <th>Tipe</th>
                    <th class="text-center">Grade</th>
                    <th class="text-center">Invoice</th>
                    <th class="text-center text-success">Tepat Waktu</th>
                    <th class="text-center text-warning">Terlambat</th>
                    <th class="text-center text-danger">Belum Bayar</th>
                    <th style="min-width:130px">On-Time %</th>
                    <th class="text-center">Avg Terlambat</th>
                    <th class="text-end">Outstanding</th>
                    <th class="text-center">Credit Util</th>
                    <th>Terakhir Bayar</th>
                    <th>Kontrak Berakhir</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($scorecards as $row)
                @php $p = $row['partner']; @endphp
                <tr>
                    <td>
                        <a href="{{ route('partners.show', $p) }}" class="fw-semibold text-decoration-none">
                            {{ $p->nama_partner }}
                        </a>
                        @if(!$p->is_active)
                            <span class="badge bg-secondary ms-1">Nonaktif</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ match($p->partner_type) {
                            'HOTEL'=>'info','TRAVEL'=>'warning','TOURDESK'=>'success',default=>'secondary'
                        } }} text-dark">{{ $p->partner_type }}</span>
                    </td>
                    <td class="text-center">
                        @if($row['risk_grade'] !== 'N/A')
                            <span class="badge bg-{{ $row['risk_color'] }} px-2 fs-6">{{ $row['risk_grade'] }}</span>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $row['total_invoices'] }}</td>
                    <td class="text-center fw-semibold text-success">{{ $row['paid_on_time'] }}</td>
                    <td class="text-center fw-semibold text-warning">{{ $row['paid_late'] }}</td>
                    <td class="text-center">
                        @php $belumBayar = $row['overdue_count'] + $row['unpaid_count']; @endphp
                        <span class="{{ $belumBayar > 0 ? 'fw-semibold text-danger' : 'text-muted' }}">
                            {{ $belumBayar }}
                        </span>
                        @if($row['partial_count'] > 0)
                            <span class="text-info ms-1" title="{{ $row['partial_count'] }} partial">
                                <i class="bi bi-hourglass-split"></i>
                            </span>
                        @endif
                    </td>
                    <td>
                        @if($row['on_time_rate'] !== null)
                            <div class="d-flex align-items-center gap-1">
                                <div class="progress flex-grow-1" style="height:6px;min-width:60px">
                                    <div class="progress-bar bg-{{ $row['on_time_rate'] >= 80 ? 'success' : ($row['on_time_rate'] >= 50 ? 'warning' : 'danger') }}"
                                         style="width:{{ $row['on_time_rate'] }}%"></div>
                                </div>
                                <small class="text-nowrap">{{ $row['on_time_rate'] }}%</small>
                            </div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        {{ $row['avg_days_late'] > 0 ? '+'.$row['avg_days_late'].' hr' : '—' }}
                    </td>
                    <td class="text-end">
                        <span class="{{ $row['outstanding'] > 0 ? 'text-danger' : 'text-muted' }}">
                            Rp {{ number_format($row['outstanding'], 0, ',', '.') }}
                        </span>
                    </td>
                    <td class="text-center">
                        @if($row['credit_utilization'] !== null)
                            @php
                                $cu = $row['credit_utilization'];
                                $cuClass = $cu > 100 ? 'text-danger fw-semibold' : ($cu > 50 ? 'text-warning' : 'text-success');
                            @endphp
                            <span class="{{ $cuClass }}">{{ $cu }}%</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        {{ $row['last_payment_date']
                            ? \Carbon\Carbon::parse($row['last_payment_date'])->format('d/m/Y')
                            : '—' }}
                    </td>
                    <td class="text-nowrap">
                        @if($p->contract_end)
                            <span class="{{ $p->isContractExpiringSoon() ? 'text-warning fw-semibold' : '' }}">
                                {{ $p->contract_end->format('d/m/Y') }}
                            </span>
                            @if($p->isContractExpiringSoon())
                                <i class="bi bi-exclamation-triangle-fill text-warning ms-1"></i>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('partners.show', $p) }}" class="btn btn-sm btn-outline-secondary py-0 px-2">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="14" class="text-center text-muted py-4">Tidak ada data partner.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Grade legend --}}
<div class="card mt-3">
    <div class="card-header small fw-semibold">Keterangan Grade & Rekomendasi Renewal</div>
    <div class="card-body py-2">
        <div class="row g-2 small">
            <div class="col-sm-6 col-md-3">
                <span class="badge bg-success me-1">A</span>
                <strong>Sangat Baik</strong>
                <div class="text-muted">On-time ≥90%, 0 overdue. <em>Perpanjang kontrak.</em></div>
            </div>
            <div class="col-sm-6 col-md-3">
                <span class="badge bg-primary me-1">B</span>
                <strong>Baik</strong>
                <div class="text-muted">On-time ≥70%. <em>Perpanjang dengan review ringan.</em></div>
            </div>
            <div class="col-sm-6 col-md-3">
                <span class="badge bg-warning text-dark me-1">C</span>
                <strong>Perlu Perhatian</strong>
                <div class="text-muted">On-time ≥50% atau credit util &gt;100%. <em>Syarat ketat.</em></div>
            </div>
            <div class="col-sm-6 col-md-3">
                <span class="badge bg-danger me-1">D</span>
                <strong>Risiko Tinggi</strong>
                <div class="text-muted">On-time &lt;50% atau overdue aktif. <em>Evaluasi sebelum perpanjang.</em></div>
            </div>
        </div>
    </div>
</div>
@endsection
