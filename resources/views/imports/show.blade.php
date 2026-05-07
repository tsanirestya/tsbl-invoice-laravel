@php use Illuminate\Support\Str; @endphp
@extends('layouts.app')
@section('title', 'Review Import')
@section('page-title', 'Review Import')

@push('styles')
<style>
/* ── Stats ── */
.show-stat {
    background:#fff; border-radius:14px; padding:.9rem 1rem;
    box-shadow:0 1px 4px rgba(15,23,41,.06); text-align:center;
    border:1px solid rgba(0,0,0,.04);
}
.show-stat .ss-val { font-size:1.5rem; font-weight:800; line-height:1; }
.show-stat .ss-lbl { font-size:.68rem; font-weight:600; text-transform:uppercase;
    letter-spacing:.5px; color:#94a3b8; margin-top:3px; }

/* ── Tabs ── */
.review-tabs-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch;
    scrollbar-width:none; border-bottom:2px solid #f1f5f9; margin-bottom:1.25rem; }
.review-tabs-wrap::-webkit-scrollbar { display:none; }
.review-tabs { border-bottom:none; flex-wrap:nowrap; white-space:nowrap; }
.review-tabs .nav-link {
    border:none; border-bottom:2px solid transparent; border-radius:0;
    color:#64748b; font-size:.84rem; font-weight:600; padding:.6rem 1rem;
    margin-bottom:-2px; transition:color .15s,border-color .15s;
}
.review-tabs .nav-link.active { color:#3b82f6; border-bottom-color:#3b82f6; background:none; }
.review-tabs .nav-link:hover:not(.active) { color:#334155; }

/* ── Table shared ── */
.rev-table { border-radius:12px; overflow:hidden; box-shadow:0 1px 4px rgba(15,23,41,.06); }
.rev-table table thead th {
    background:#f8fafc; font-size:.67rem; font-weight:700;
    letter-spacing:.5px; text-transform:uppercase; color:#64748b;
    border-bottom:1px solid #f1f5f9; padding:.6rem 1rem;
}
.rev-table table tbody td { padding:.65rem 1rem; font-size:.83rem;
    border-bottom:1px solid #f8fafc; vertical-align:middle; }
.rev-table table tbody tr:last-child td { border-bottom:none; }

/* ── Anomaly group header ── */
.anomaly-group-hdr {
    display:flex; align-items:center; gap:.5rem;
    padding:.6rem .85rem; flex-wrap:nowrap;
}
.anomaly-group-hdr .hdr-name {
    flex:1 1 0; min-width:0;
    font-family:monospace; font-weight:600; font-size:.82rem;
    overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
}
.anomaly-group-hdr .hdr-badges { display:flex; gap:.3rem; flex-shrink:0; }
.anomaly-group-hdr .hdr-actions { display:flex; gap:.3rem; flex-shrink:0; }

/* Mobile: stack actions below name row */
@media (max-width:575.98px) {
    .anomaly-group-hdr { flex-wrap:wrap; }
    .anomaly-group-hdr .hdr-name { width:100%; flex:0 0 100%; }
    .anomaly-group-hdr .hdr-badges { flex:1 1 auto; }
    .anomaly-group-hdr .hdr-actions { width:100%; flex:0 0 100%;
        display:flex; flex-wrap:wrap; gap:.35rem; }
    .hdr-actions .btn { font-size:.75rem; padding:.25rem .5rem; flex:1 1 auto; text-align:center; }
    /* Hide non-critical table columns on mobile */
    .mob-hide { display:none !important; }
    /* Card style for valid rows on mobile */
    .valid-row-card { background:#fff; border-radius:10px; padding:.7rem .9rem;
        border:1px solid #f1f5f9; margin-bottom:.5rem; box-shadow:0 1px 3px rgba(15,23,41,.05); }
    .valid-row-card.approved { border-left:3px solid #22c55e; }
    .valid-row-card .vrc-name { font-size:.85rem; font-weight:600; color:#1e293b;
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .valid-row-card .vrc-meta { font-size:.74rem; color:#94a3b8; margin-top:2px; }
    .valid-row-card .vrc-nums { display:flex; gap:.75rem; margin-top:.4rem; }
    .valid-row-card .vrc-num-item { font-size:.75rem; color:#64748b; }
    .valid-row-card .vrc-num-item strong { display:block; font-size:.85rem; color:#1e293b; }
    /* Hide desktop tables, show mobile cards */
    .mob-table { display:none !important; }
    .mob-cards { display:block !important; }
    /* Rejected card */
    .rej-card { background:#fff; border-radius:10px; padding:.65rem .85rem;
        border:1px solid #f1f5f9; margin-bottom:.4rem; box-shadow:0 1px 3px rgba(15,23,41,.05); }
}
@media (min-width:576px) {
    .mob-cards { display:none !important; }
    .mob-table { display:block !important; }
}

/* ── Detail sub-table (anomaly expand) ── */
.detail-sub-table { font-size:.78rem; }
.detail-sub-table thead th { font-size:.65rem; }
/* On mobile, hide extra detail cols */
@media (max-width:575.98px) {
    .detail-sub-table .mob-hide { display:none !important; }
}
</style>
@endpush

@section('content')

{{-- ── Header ── --}}
<div class="d-flex align-items-start gap-2 mb-4 flex-wrap">
    <a href="{{ route('imports.index') }}"
       class="btn btn-sm btn-outline-secondary flex-shrink-0"
       style="border-radius:9px;margin-top:2px;">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div class="flex-fill min-w-0">
        <h5 class="mb-0 fw-bold" style="letter-spacing:-.2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:clamp(.95rem,4vw,1.1rem)">
            <i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>{{ $import->original_filename }}
        </h5>
        <div style="font-size:.75rem;color:#94a3b8;margin-top:3px;display:flex;flex-wrap:wrap;gap:.3rem .5rem;align-items:center;">
            <span>{{ $import->uploaded_at?->format('d/m/Y H:i') }}</span>
            <span style="color:#cbd5e1">·</span>
            <span>{{ $import->uploader->full_name ?? '-' }}</span>
            <span style="color:#cbd5e1">·</span>
            @php
                $sInfo = match($import->status) {
                    'done'       => ['Selesai',  '#f0fdf4','#166534'],
                    'reviewed'   => ['Review',   '#eff6ff','#1d4ed8'],
                    'processing' => ['Diproses', '#fff7ed','#9a3412'],
                    default      => ['Pending',  '#f8fafc','#475569'],
                };
            @endphp
            <span style="display:inline-flex;align-items:center;gap:.25rem;background:{{ $sInfo[1] }};color:{{ $sInfo[2] }};border-radius:20px;padding:.1rem .55rem;font-size:.7rem;font-weight:700;">
                {{ $sInfo[0] }}
            </span>
        </div>
    </div>
    {{-- Action buttons row --}}
    <div class="d-flex gap-2 flex-shrink-0 flex-wrap justify-content-end" style="margin-top:2px;">
        @if($anomalyRows->isNotEmpty())
        <a href="{{ route('imports.export-anomaly', $import) }}"
           class="btn btn-sm btn-outline-warning" style="border-radius:9px;">
            <i class="bi bi-file-earmark-excel me-1 d-none d-sm-inline"></i>
            <span class="d-none d-sm-inline">Export Anomaly</span>
            <i class="bi bi-download d-sm-none"></i>
        </a>
        @endif
        @if($import->status !== 'done')
        <form method="POST" action="{{ route('import-review.finalize', $import) }}">
            @csrf
            <button class="btn btn-success btn-sm" style="border-radius:9px;"
                    {{ $import->pendingAnomalies() > 0 ? 'disabled' : '' }}>
                <i class="bi bi-check-circle me-1 d-none d-sm-inline"></i>
                Finalize
            </button>
        </form>
        @endif
    </div>
</div>

{{-- Validation errors --}}
@if($errors->any())
<div class="d-flex gap-2 align-items-start mb-3 p-3"
     style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;color:#991b1b;font-size:.84rem;">
    <i class="bi bi-exclamation-circle-fill flex-shrink-0" style="color:#ef4444;margin-top:2px;"></i>
    <div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
</div>
@endif

{{-- ── Summary Stats ── --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-sm-3">
        <div class="show-stat">
            <div class="ss-val" style="color:#1e293b;">{{ number_format($import->total_rows) }}</div>
            <div class="ss-lbl">Total Baris</div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="show-stat" style="border-color:#bbf7d0;">
            <div class="ss-val" style="color:#16a34a;">{{ number_format($import->valid_rows) }}</div>
            <div class="ss-lbl" style="color:#4ade80;">Valid</div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="show-stat" style="border-color:{{ $import->anomaly_rows > 0 ? '#fca5a5' : '#f1f5f9' }};">
            <div class="ss-val" style="color:#dc2626;">{{ number_format($import->anomaly_rows) }}</div>
            <div class="ss-lbl" style="color:#f87171;">Anomaly{{ $import->anomaly_rows > 0 ? ' ('.$import->anomalyRate().'%)' : '' }}</div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="show-stat">
            <div class="ss-val" style="color:#64748b;">{{ number_format($import->rejected_rows) }}</div>
            <div class="ss-lbl">Rejected</div>
        </div>
    </div>
</div>

{{-- Pending anomaly warning --}}
@if($import->pendingAnomalies() > 0)
<div class="d-flex gap-2 align-items-center mb-3 p-3"
     style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;color:#92400e;font-size:.83rem;">
    <i class="bi bi-exclamation-triangle-fill flex-shrink-0" style="color:#f59e0b;"></i>
    <span><strong>{{ $import->pendingAnomalies() }}</strong> baris anomaly belum di-handle. Selesaikan semua sebelum Finalize.</span>
</div>
@endif

{{-- Anomaly type pills --}}
@if($anomalyTypes->isNotEmpty())
<div class="mb-3 d-flex flex-wrap gap-2">
    @foreach($anomalyTypes as $type => $items)
    <span style="display:inline-flex;align-items:center;gap:.3rem;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:20px;padding:.22rem .65rem;font-size:.72rem;font-weight:600;">
        <i class="bi bi-exclamation-octagon-fill" style="font-size:.6rem;"></i>
        {{ $type }}: {{ $items->count() }}
    </span>
    @endforeach
</div>
@endif

{{-- ── Tabs ── --}}
<div class="review-tabs-wrap">
    <ul class="nav review-tabs" id="reviewTabs">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-valid">
                <i class="bi bi-check-circle-fill text-success me-1"></i>Valid
                <span class="badge ms-1" style="background:#dcfce7;color:#166534;">{{ $validRows->count() }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-anomaly">
                <i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>Anomaly
                <span class="badge ms-1" style="background:#fee2e2;color:#991b1b;">{{ $anomalyRows->count() }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-rejected">
                <i class="bi bi-x-circle text-secondary me-1"></i>Rejected
                <span class="badge ms-1" style="background:#f1f5f9;color:#475569;">{{ $rejections->count() }}</span>
            </button>
        </li>
    </ul>
</div>

<div class="tab-content">

    {{-- ══ TAB VALID ══ --}}
    <div class="tab-pane fade show active" id="tab-valid">
        @if($validRows->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>Tidak ada baris valid.
            </div>
        @else
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
            <div class="text-muted small">
                Total Komisi: <strong class="text-success">Rp {{ number_format($totalKomisi, 0, ',', '.') }}</strong>
            </div>
        </div>
        <form method="POST" action="{{ route('import-review.approve', $import) }}" id="approveForm">
            @csrf
            <div class="mb-2 d-flex gap-2 align-items-center flex-wrap">
                <button type="submit" class="btn btn-sm btn-success" id="approveAllBtn" disabled style="border-radius:8px;">
                    <i class="bi bi-check-all me-1"></i>Approve Dipilih
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        style="border-radius:8px;"
                        onclick="toggleAll('valid-check','approveAllBtn')">
                    Pilih Semua
                </button>
            </div>

            {{-- Desktop: table --}}
            <div class="mob-table">
                <div class="rev-table">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:30px"></th>
                                <th>#</th>
                                <th>Trx No</th>
                                <th class="mob-hide">Tanggal</th>
                                <th class="mob-hide">Ticket Type</th>
                                <th>Ticket Name</th>
                                <th class="mob-hide">Cashier</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-center mob-hide">Qty</th>
                                <th class="text-end">Komisi</th>
                                <th class="mob-hide">Match</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($validRows as $row)
                            <tr class="{{ $row->is_approved ? 'table-success' : '' }}">
                                <td>
                                    @if(!$row->is_approved && $import->status !== 'done')
                                    <input type="checkbox" name="row_ids[]" value="{{ $row->id }}"
                                           class="form-check-input valid-check"
                                           onchange="syncBtn('valid-check','approveAllBtn')">
                                    @else
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $row->row_index }}</td>
                                <td class="small">{{ $row->transaction_no ?? '-' }}</td>
                                <td class="small mob-hide">{{ $row->date?->format('d/m/Y') ?? '-' }}</td>
                                <td class="mob-hide"><span class="badge bg-primary">{{ $row->ticket_type }}</span></td>
                                <td class="small">{{ $row->ticket_name }}</td>
                                <td class="small mob-hide">{{ $row->cashier ?? '-' }}</td>
                                <td class="text-end small">{{ number_format($row->unit_price,0,',','.') }}</td>
                                <td class="text-center small mob-hide">{{ $row->qty }}</td>
                                <td class="text-end small text-success">
                                    {{ $row->komisi_amount !== null ? number_format($row->komisi_amount,0,',','.') : '-' }}
                                </td>
                                <td class="mob-hide">
                                    <span class="badge bg-{{ match($row->match_method){'exact'=>'success','alias'=>'info','fuzzy'=>'warning',default=>'secondary'} }} text-dark">
                                        {{ $row->match_method ?? 'none' }}
                                    </span>
                                </td>
                                <td>
                                    @if($row->is_approved)
                                        <span class="badge bg-success">Approved</span>
                                    @else
                                        <span class="badge bg-light text-dark border">Pending</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Mobile: cards --}}
            <div class="mob-cards">
                @foreach($validRows as $row)
                <div class="valid-row-card {{ $row->is_approved ? 'approved' : '' }}">
                    <div class="d-flex align-items-start gap-2">
                        <div class="flex-shrink-0" style="padding-top:2px;">
                            @if(!$row->is_approved && $import->status !== 'done')
                            <input type="checkbox" name="row_ids[]" value="{{ $row->id }}"
                                   class="form-check-input valid-check"
                                   onchange="syncBtn('valid-check','approveAllBtn')">
                            @else
                            <i class="bi bi-check-circle-fill text-success"></i>
                            @endif
                        </div>
                        <div class="flex-fill min-w-0">
                            <div class="vrc-name">{{ $row->ticket_name }}</div>
                            <div class="vrc-meta">
                                <span class="badge bg-primary me-1" style="font-size:.65rem;">{{ $row->ticket_type }}</span>
                                {{ $row->transaction_no ?? '-' }} · {{ $row->date?->format('d/m/Y') ?? '-' }}
                            </div>
                            <div class="vrc-nums">
                                <div class="vrc-num-item">
                                    <strong>{{ number_format($row->unit_price,0,',','.') }}</strong>
                                    Unit Price
                                </div>
                                <div class="vrc-num-item">
                                    <strong class="text-success">{{ $row->komisi_amount !== null ? number_format($row->komisi_amount,0,',','.') : '-' }}</strong>
                                    Komisi
                                </div>
                                <div class="vrc-num-item">
                                    <strong>{{ $row->qty }}</strong>
                                    Qty
                                </div>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            @if($row->is_approved)
                                <span class="badge bg-success" style="font-size:.7rem;">Approved</span>
                            @else
                                <span class="badge bg-light text-dark border" style="font-size:.7rem;">Pending</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

        </form>
        @endif
    </div>

    {{-- ══ TAB ANOMALY ══ --}}
    <div class="tab-pane fade" id="tab-anomaly">
        @if($anomalyRows->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-check-circle text-success fs-2 d-block mb-2"></i>Tidak ada anomaly!
            </div>
        @else

        @php
            $anomalyLabels = [
                'SUSPICIOUS_PRICING' => ['label'=>'Harga Di Bawah Nett',   'color'=>'danger',  'icon'=>'bi-exclamation-octagon-fill'],
                'PRICE_MISMATCH'     => ['label'=>'Harga Tidak Sesuai',    'color'=>'danger',  'icon'=>'bi-currency-dollar'],
                'PRODUCT_NOT_FOUND'  => ['label'=>'Produk Tidak Ditemukan','color'=>'warning', 'icon'=>'bi-search'],
                'CATEGORY_MISMATCH'  => ['label'=>'Kategori Tidak Sesuai', 'color'=>'warning', 'icon'=>'bi-tags'],
                'REVERSE_MISMATCH'   => ['label'=>'Prefix Terbalik',       'color'=>'warning', 'icon'=>'bi-arrow-left-right'],
                'FUZZY_CANDIDATE'    => ['label'=>'Match Tidak Pasti',     'color'=>'info',    'icon'=>'bi-question-circle'],
            ];
        @endphp

        @foreach($anomalyGroups as $anomalyType => $ticketGroups)
        @php
            $meta       = $anomalyLabels[$anomalyType] ?? ['label'=>$anomalyType,'color'=>'secondary','icon'=>'bi-bug'];
            $typeTotal  = collect($ticketGroups)->sum('total');
            $typePending= collect($ticketGroups)->sum('pending');
            $typeId     = 'atype-' . Str::slug($anomalyType);
        @endphp

        <div class="card border-0 shadow-sm mb-3">
            {{-- Type section header --}}
            <div class="card-header bg-{{ $meta['color'] }}-subtle border-{{ $meta['color'] }}-subtle
                        d-flex align-items-center gap-2 py-2 px-3"
                 style="cursor:pointer" data-bs-toggle="collapse" data-bs-target="#{{ $typeId }}">
                <i class="bi {{ $meta['icon'] }} text-{{ $meta['color'] }}"></i>
                <span class="fw-semibold text-{{ $meta['color'] }} flex-fill" style="font-size:.85rem;">{{ $meta['label'] }}</span>
                <span class="badge bg-{{ $meta['color'] }} me-1">{{ $typeTotal }}</span>
                @if($typePending > 0)
                    <span class="badge bg-warning text-dark me-1">{{ $typePending }} pending</span>
                @endif
                <span class="badge bg-light text-dark border" style="font-size:.68rem;">{{ count($ticketGroups) }} tiket</span>
                <i class="bi bi-chevron-down ms-1 small"></i>
            </div>

            <div class="collapse show" id="{{ $typeId }}">
                <div class="card-body p-0">
                    @foreach($ticketGroups as $ticketName => $group)
                    @php
                        $groupId    = 'grp-' . md5($anomalyType . $ticketName);
                        $hasPending = $group['pending'] > 0 && $import->status !== 'done';
                        $sampleRow  = $group['sample_row'];
                        $confirmMsg = "Tolak semua {$group['pending']} baris dengan nama tiket ini?";
                    @endphp

                    <div class="border-bottom {{ $loop->last ? 'border-bottom-0' : '' }}">

                        {{-- Group header --}}
                        <div class="anomaly-group-hdr {{ $hasPending ? 'bg-white' : 'bg-light' }}">

                            {{-- Expand button --}}
                            <button class="btn btn-sm btn-link text-muted p-0 flex-shrink-0"
                                    data-bs-toggle="collapse" data-bs-target="#{{ $groupId }}"
                                    style="min-width:20px;line-height:1">
                                <i class="bi bi-chevron-right small expand-icon"></i>
                            </button>

                            {{-- Ticket name --}}
                            <div class="hdr-name" title="{{ $ticketName }}">{{ $ticketName }}</div>

                            {{-- Badges --}}
                            <div class="hdr-badges">
                                <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2">
                                    {{ $group['total'] }}
                                </span>
                                @if($group['pending'] > 0)
                                <span class="badge bg-warning text-dark rounded-pill px-2">
                                    {{ $group['pending'] }} pending
                                </span>
                                @else
                                <span class="badge bg-success-subtle text-success border rounded-pill px-2">
                                    <i class="bi bi-check2"></i>
                                </span>
                                @endif
                            </div>

                            {{-- Actions (visible on all sizes, stacked on mobile via CSS) --}}
                            @if($hasPending)
                            <div class="hdr-actions">
                                <button type="button"
                                        class="btn btn-sm btn-outline-success py-0 px-2"
                                        data-bs-toggle="modal" data-bs-target="#overrideGroupModal"
                                        data-ticket-name="{{ $ticketName }}"
                                        data-pending-count="{{ $group['pending'] }}"
                                        data-anomaly-type="{{ $meta['label'] }}">
                                    <i class="bi bi-pencil-check me-1 d-none d-sm-inline"></i>Override
                                </button>

                                @if(in_array($anomalyType, ['SUSPICIOUS_PRICING','PRICE_MISMATCH']))
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary py-0 px-2"
                                        data-bs-toggle="modal" data-bs-target="#adjustPricingModal"
                                        data-ticket-name="{{ $ticketName }}"
                                        data-pending-count="{{ $group['pending'] }}"
                                        data-publish-rate="{{ $sampleRow->publish_rate }}"
                                        data-nett-price="{{ $sampleRow->nett_price }}"
                                        data-unit-price="{{ $sampleRow->unit_price }}">
                                    <i class="bi bi-sliders me-1 d-none d-sm-inline"></i>Harga
                                </button>
                                @elseif($anomalyType === 'FUZZY_CANDIDATE')
                                <button type="button"
                                        class="btn btn-sm btn-outline-info py-0 px-2"
                                        data-bs-toggle="modal" data-bs-target="#reassignProductModal"
                                        data-ticket-name="{{ $ticketName }}"
                                        data-pending-count="{{ $group['pending'] }}"
                                        data-current-product="{{ $sampleRow->product?->product_name ?? '-' }}"
                                        data-current-product-id="{{ $sampleRow->matched_product_id }}">
                                    <i class="bi bi-arrow-repeat me-1 d-none d-sm-inline"></i>Produk
                                </button>
                                @endif

                                <form method="POST" action="{{ route('import-review.reject-group', $import) }}"
                                      onsubmit="return confirm('{{ $confirmMsg }}')">
                                    @csrf
                                    <input type="hidden" name="ticket_name" value="{{ $ticketName }}">
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">
                                        <i class="bi bi-x-circle me-1 d-none d-sm-inline"></i>Tolak
                                    </button>
                                </form>
                            </div>
                            @endif

                        </div>{{-- /anomaly-group-hdr --}}

                        {{-- Collapsible detail rows --}}
                        <div class="collapse" id="{{ $groupId }}">
                            <div class="border-top bg-light" style="overflow-x:hidden;">
                                {{-- Mobile cards for detail --}}
                                <div class="d-sm-none p-2">
                                    @foreach($group['rows'] as $row)
                                    <div style="background:#fff;border-radius:8px;padding:.6rem .8rem;margin-bottom:.4rem;border:1px solid {{ $row->is_approved ? '#bbf7d0' : '#fecaca' }};font-size:.78rem;">
                                        <div class="d-flex justify-content-between align-items-start gap-1">
                                            <span class="text-muted">#{{ $row->row_index }}</span>
                                            <div>
                                                @if($row->is_approved)
                                                    <span class="badge bg-success" style="font-size:.65rem;">Approved</span>
                                                    @if($row->override_reason)
                                                    <div class="text-muted" style="font-size:.68rem;margin-top:2px;text-align:right;">{{ $row->override_reason }}</div>
                                                    @endif
                                                @else
                                                    <span class="badge bg-warning text-dark" style="font-size:.65rem;">Pending</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div style="color:#475569;margin-top:.3rem;">
                                            {{ $row->transaction_no ?? '-' }} · {{ $row->date?->format('d/m/Y') ?? '-' }}
                                        </div>
                                        <div class="d-flex gap-3 mt-1">
                                            <span>Unit: <strong>{{ number_format($row->unit_price,0,',','.') }}</strong></span>
                                            @if(in_array($anomalyType,['SUSPICIOUS_PRICING','PRICE_MISMATCH','FUZZY_CANDIDATE']))
                                            <span>Nett: <strong>{{ $row->nett_price !== null ? number_format($row->nett_price,0,',','.') : '-' }}</strong></span>
                                            @endif
                                            <span>Qty: <strong>{{ $row->qty }}</strong></span>
                                        </div>
                                        @foreach($row->anomalies as $a)
                                        <span class="badge {{ $a->severity==='error'?'bg-danger':'bg-warning text-dark' }} me-1 mt-1" style="font-size:.62rem;">
                                            {{ $a->anomaly_type }}
                                        </span>
                                        @endforeach
                                    </div>
                                    @endforeach
                                </div>

                                {{-- Desktop table for detail --}}
                                <div class="d-none d-sm-block">
                                    <table class="table table-sm table-hover mb-0 align-middle detail-sub-table">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th class="ps-3">#</th>
                                                <th>Trx No</th>
                                                <th>Tanggal</th>
                                                <th>Cashier</th>
                                                <th class="text-end">Unit Price</th>
                                                @if(in_array($anomalyType,['SUSPICIOUS_PRICING','PRICE_MISMATCH']))
                                                <th class="text-end">Publish Rate</th>
                                                <th class="text-end">Nett Price</th>
                                                @elseif($anomalyType === 'FUZZY_CANDIDATE')
                                                <th>Produk (Fuzzy)</th>
                                                <th class="text-end">Publish Rate</th>
                                                <th class="text-end">Nett Price</th>
                                                @endif
                                                <th class="text-center">Qty</th>
                                                <th>Anomaly</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($group['rows'] as $row)
                                            <tr class="{{ $row->is_approved ? 'table-success' : 'table-danger' }}">
                                                <td class="ps-3 text-muted">{{ $row->row_index }}</td>
                                                <td>{{ $row->transaction_no ?? '-' }}</td>
                                                <td>{{ $row->date?->format('d/m/Y') ?? '-' }}</td>
                                                <td>{{ $row->cashier ?? '-' }}</td>
                                                <td class="text-end fw-semibold">{{ number_format($row->unit_price,0,',','.') }}</td>
                                                @if(in_array($anomalyType,['SUSPICIOUS_PRICING','PRICE_MISMATCH']))
                                                <td class="text-end text-muted">{{ $row->publish_rate !== null ? number_format($row->publish_rate,0,',','.') : '-' }}</td>
                                                <td class="text-end text-muted">{{ $row->nett_price !== null ? number_format($row->nett_price,0,',','.') : '-' }}</td>
                                                @elseif($anomalyType === 'FUZZY_CANDIDATE')
                                                <td class="small">{{ $row->product?->product_name ?? '-' }}</td>
                                                <td class="text-end text-muted">{{ $row->publish_rate !== null ? number_format($row->publish_rate,0,',','.') : '-' }}</td>
                                                <td class="text-end text-muted">{{ $row->nett_price !== null ? number_format($row->nett_price,0,',','.') : '-' }}</td>
                                                @endif
                                                <td class="text-center">{{ $row->qty }}</td>
                                                <td>
                                                    @foreach($row->anomalies as $a)
                                                    <span class="badge {{ $a->severity==='error'?'bg-danger':'bg-warning text-dark' }} me-1">
                                                        {{ $a->anomaly_type }}
                                                    </span>
                                                    @endforeach
                                                </td>
                                                <td>
                                                    @if($row->is_approved)
                                                        <span class="badge bg-success">Approved</span>
                                                        @if($row->override_reason)
                                                        <div class="text-muted" style="font-size:.72rem">{{ $row->override_reason }}</div>
                                                        @endif
                                                    @else
                                                        <span class="badge bg-warning text-dark">Pending</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>{{-- end group --}}
                    @endforeach
                </div>
            </div>
        </div>{{-- end anomaly type card --}}
        @endforeach

        @endif
    </div>

    {{-- ══ TAB REJECTED ══ --}}
    <div class="tab-pane fade" id="tab-rejected">
        @if($rejections->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>Tidak ada baris rejected.
            </div>
        @else

        {{-- Mobile cards --}}
        <div class="d-sm-none">
            @foreach($rejections as $rej)
            <div class="rej-card">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <span class="text-muted small">#{{ $rej->row_index }}</span>
                    <span class="badge bg-secondary" style="font-size:.68rem;">{{ $rej->rejection_reason }}</span>
                </div>
                <div style="font-size:.82rem;margin-top:.3rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    {{ $rej->raw_data['ticket_name'] ?? '-' }}
                </div>
                <div style="font-size:.73rem;color:#94a3b8;margin-top:2px;">
                    {{ $rej->raw_data['ticket_type'] ?? '-' }}
                </div>
            </div>
            @endforeach
        </div>

        {{-- Desktop table --}}
        <div class="d-none d-sm-block rev-table">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Reason</th>
                        <th>Ticket Type</th>
                        <th>Ticket Name</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rejections as $rej)
                    <tr class="text-muted">
                        <td>{{ $rej->row_index }}</td>
                        <td><span class="badge bg-secondary">{{ $rej->rejection_reason }}</span></td>
                        <td>{{ $rej->raw_data['ticket_type'] ?? '-' }}</td>
                        <td>{{ $rej->raw_data['ticket_name'] ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @endif
    </div>

</div>{{-- /tab-content --}}

{{-- ══ Override Group Modal ══ --}}
<div class="modal fade" id="overrideGroupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <form method="POST" action="{{ route('import-review.override-group', $import) }}">
                @csrf
                <div class="modal-header bg-success-subtle py-3">
                    <h6 class="modal-title mb-0"><i class="bi bi-pencil-check me-2"></i>Override Semua Baris Serupa</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="ticket_name" id="overrideGroupTicketName">

                    <div class="p-2 rounded mb-3" style="background:#f0fdf4;border:1px solid #bbf7d0;font-size:.82rem;color:#166534;">
                        <i class="bi bi-info-circle me-1"></i>
                        Override akan diterapkan ke <strong id="overrideGroupCount"></strong>
                    </div>

                    <div class="font-monospace fw-semibold bg-light border rounded p-2 mb-3 small"
                         id="overrideGroupTicketDisplay" style="word-break:break-all;font-size:.8rem;"></div>

                    <div class="text-muted small mb-3" id="overrideGroupAnomalyType"
                         style="font-size:.77rem;"></div>

                    <label class="form-label fw-semibold small">Alasan Override <span class="text-danger">*</span></label>
                    <textarea name="override_reason" id="overrideGroupReason" class="form-control" rows="3"
                              placeholder="Jelaskan mengapa semua baris dengan nama tiket ini disetujui..."
                              required style="font-size:.84rem;"></textarea>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-check-all me-1"></i>Override Semua
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══ Adjust Pricing Modal ══ --}}
<div class="modal fade" id="adjustPricingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <form method="POST" action="{{ route('import-review.adjust-pricing', $import) }}">
                @csrf
                <div class="modal-header bg-primary-subtle py-3">
                    <h6 class="modal-title mb-0"><i class="bi bi-sliders me-2"></i>Adjust Harga</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="ticket_name" id="adjustTicketName">

                    <div class="font-monospace fw-semibold bg-light border rounded p-2 mb-3"
                         id="adjustTicketDisplay" style="word-break:break-all;font-size:.8rem;"></div>

                    <div class="p-2 rounded mb-3" style="background:#eff6ff;border:1px solid #bfdbfe;font-size:.81rem;color:#1e40af;">
                        <i class="bi bi-info-circle me-1"></i>
                        Diterapkan ke <strong id="adjustPendingCount"></strong> baris pending.
                        Unit price aktual: <strong id="adjustUnitPrice" class="font-monospace"></strong>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Gross / Publish Rate <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="text" inputmode="numeric" name="publish_rate" id="adjustPublishRate"
                                       class="form-control currency-input" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Nett Price <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="text" inputmode="numeric" name="nett_price" id="adjustNettPrice"
                                       class="form-control currency-input" required>
                            </div>
                        </div>
                    </div>

                    <label class="form-label fw-semibold small">Alasan Adjustment <span class="text-danger">*</span></label>
                    <textarea name="override_reason" id="adjustReason" class="form-control" rows="2"
                              placeholder="Jelaskan alasan penyesuaian harga..."
                              required style="font-size:.84rem;"></textarea>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-check-lg me-1"></i>Simpan Adjustment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══ Reassign Product Modal (FUZZY_CANDIDATE) ══ --}}
<div class="modal fade" id="reassignProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <form method="POST" action="{{ route('import-review.reassign-product', $import) }}" id="reassignForm">
                @csrf
                <div class="modal-header bg-info-subtle py-3">
                    <h6 class="modal-title mb-0"><i class="bi bi-arrow-repeat me-2"></i>Ganti Produk</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="ticket_name" id="reassignTicketName">
                    <input type="hidden" name="product_id" id="reassignProductId">

                    <div class="font-monospace fw-semibold bg-light border rounded p-2 mb-2"
                         id="reassignTicketDisplay" style="word-break:break-all;font-size:.8rem;"></div>

                    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                        <span class="text-muted small">Produk saat ini:</span>
                        <span class="badge bg-warning text-dark" id="reassignCurrentProduct"></span>
                        <span class="badge bg-info text-dark" id="reassignPendingCount"></span>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                            <span class="fw-semibold small">Produk serupa:</span>
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary py-0 px-2"
                                    id="loadSimilarBtn" onclick="loadSimilarProducts()">
                                <i class="bi bi-search me-1"></i>Cari Serupa
                            </button>
                            <span class="spinner-border spinner-border-sm text-secondary d-none" id="similarSpinner"></span>
                        </div>

                        <div id="similarProductsContainer" class="d-none">
                            {{-- Mobile: card list --}}
                            <div class="d-sm-none" id="similarProductsMobile" style="max-height:260px;overflow-y:auto;"></div>
                            {{-- Desktop: table --}}
                            <div class="d-none d-sm-block" style="max-height:280px;overflow-y:auto;">
                                <table class="table table-sm table-hover mb-0 align-middle" style="font-size:.8rem;">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th style="width:30px"></th>
                                            <th>DSI Code</th>
                                            <th>Nama Produk</th>
                                            <th class="text-end">Publish Rate</th>
                                            <th class="text-end">Nett Price</th>
                                            <th class="text-end">Komisi</th>
                                            <th class="text-center">Match</th>
                                        </tr>
                                    </thead>
                                    <tbody id="similarProductsBody"></tbody>
                                </table>
                            </div>
                        </div>
                        <div id="similarProductsEmpty" class="text-muted small d-none p-2">
                            Tidak ada produk ditemukan.
                        </div>
                    </div>

                    <label class="form-label fw-semibold small">Alasan Penggantian <span class="text-danger">*</span></label>
                    <textarea name="override_reason" id="reassignReason" class="form-control" rows="2"
                              placeholder="Jelaskan alasan penggantian produk..."
                              required style="font-size:.84rem;"></textarea>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info btn-sm" id="reassignSubmitBtn" disabled>
                        <i class="bi bi-check-lg me-1"></i>Ganti Produk
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
/* ── Checkbox helpers ── */
function syncBtn(cls, btnId) {
    document.getElementById(btnId).disabled =
        document.querySelectorAll('.' + cls + ':checked').length === 0;
}
function toggleAll(cls, btnId) {
    const checks = document.querySelectorAll('.' + cls);
    const all    = [...checks].every(c => c.checked);
    checks.forEach(c => c.checked = !all);
    syncBtn(cls, btnId);
}

/* ── Collapse chevron ── */
document.querySelectorAll('.collapse').forEach(el => {
    el.addEventListener('show.bs.collapse', () => {
        document.querySelector(`[data-bs-target="#${el.id}"]`)
            ?.querySelector('.expand-icon')
            ?.classList.replace('bi-chevron-right','bi-chevron-down');
    });
    el.addEventListener('hide.bs.collapse', () => {
        document.querySelector(`[data-bs-target="#${el.id}"]`)
            ?.querySelector('.expand-icon')
            ?.classList.replace('bi-chevron-down','bi-chevron-right');
    });
});

/* ── Override Group Modal ── */
document.getElementById('overrideGroupModal').addEventListener('show.bs.modal', e => {
    const b = e.relatedTarget;
    document.getElementById('overrideGroupTicketName').value    = b.dataset.ticketName;
    document.getElementById('overrideGroupTicketDisplay').textContent = b.dataset.ticketName;
    document.getElementById('overrideGroupCount').textContent   = b.dataset.pendingCount + ' baris pending';
    document.getElementById('overrideGroupAnomalyType').textContent = 'Tipe anomaly: ' + b.dataset.anomalyType;
    document.getElementById('overrideGroupReason').value        = '';
});

/* ── Adjust Pricing Modal ── */
document.getElementById('adjustPricingModal').addEventListener('show.bs.modal', e => {
    const b = e.relatedTarget;
    document.getElementById('adjustTicketName').value    = b.dataset.ticketName;
    document.getElementById('adjustTicketDisplay').textContent = b.dataset.ticketName;
    document.getElementById('adjustPendingCount').textContent  = b.dataset.pendingCount + ' baris';
    document.getElementById('adjustUnitPrice').textContent     = 'Rp ' + Number(b.dataset.unitPrice).toLocaleString('id-ID');
    document.getElementById('adjustPublishRate').value  = b.dataset.publishRate ? fmtCurrency(parseFloat(b.dataset.publishRate)) : '';
    document.getElementById('adjustNettPrice').value    = b.dataset.nettPrice   ? fmtCurrency(parseFloat(b.dataset.nettPrice))   : '';
    document.getElementById('adjustReason').value       = '';
});

/* ── Reassign Product Modal ── */
const similarUrl = "{{ route('import-review.similar-products', $import) }}";
let currentTicketName = '';

document.getElementById('reassignProductModal').addEventListener('show.bs.modal', e => {
    const b = e.relatedTarget;
    currentTicketName = b.dataset.ticketName;
    document.getElementById('reassignTicketName').value    = currentTicketName;
    document.getElementById('reassignTicketDisplay').textContent = currentTicketName;
    document.getElementById('reassignCurrentProduct').textContent = b.dataset.currentProduct;
    document.getElementById('reassignPendingCount').textContent   = b.dataset.pendingCount + ' baris pending';
    document.getElementById('reassignProductId').value    = '';
    document.getElementById('reassignReason').value       = '';
    document.getElementById('reassignSubmitBtn').disabled = true;
    document.getElementById('similarProductsContainer').classList.add('d-none');
    document.getElementById('similarProductsEmpty').classList.add('d-none');
    document.getElementById('similarProductsBody').innerHTML     = '';
    document.getElementById('similarProductsMobile').innerHTML   = '';
});

function loadSimilarProducts() {
    const spinner = document.getElementById('similarSpinner');
    const btn     = document.getElementById('loadSimilarBtn');
    spinner.classList.remove('d-none'); btn.disabled = true;

    fetch(similarUrl + '?ticket_name=' + encodeURIComponent(currentTicketName))
        .then(r => r.json())
        .then(products => {
            spinner.classList.add('d-none'); btn.disabled = false;

            const body    = document.getElementById('similarProductsBody');
            const mobile  = document.getElementById('similarProductsMobile');
            const cont    = document.getElementById('similarProductsContainer');
            const empty   = document.getElementById('similarProductsEmpty');
            body.innerHTML = ''; mobile.innerHTML = '';

            if (!products.length) {
                empty.classList.remove('d-none'); cont.classList.add('d-none');
                return;
            }

            products.forEach(p => {
                const scoreColor = p.score >= 80 ? 'success' : p.score >= 50 ? 'warning' : 'secondary';

                /* Desktop row */
                const row = document.createElement('tr');
                row.style.cursor = 'pointer';
                row.innerHTML = `
                    <td class="ps-2">
                        <input type="radio" name="_similar_product" class="form-check-input similar-radio"
                               value="${p.id}" data-name="${p.product_name}">
                    </td>
                    <td class="font-monospace">${p.dsi_code}</td>
                    <td>${p.product_name}</td>
                    <td class="text-end">${p.publish_rate.toLocaleString('id-ID')}</td>
                    <td class="text-end">${p.nett_price.toLocaleString('id-ID')}</td>
                    <td class="text-end">${p.komisi.toLocaleString('id-ID')}</td>
                    <td class="text-center">
                        <span class="badge bg-${scoreColor}-subtle text-${scoreColor} border">${p.score}%</span>
                    </td>`;
                row.addEventListener('click', () => {
                    row.querySelector('input[type=radio]').checked = true;
                    selectSimilarProduct(p.id);
                    /* deselect other mobile cards */
                    mobile.querySelectorAll('.sim-card').forEach(c => c.classList.remove('border-primary'));
                });
                body.appendChild(row);

                /* Mobile card */
                const card = document.createElement('div');
                card.className = 'sim-card';
                card.style.cssText = 'background:#fff;border-radius:8px;padding:.6rem .8rem;margin-bottom:.4rem;border:1px solid #e2e8f0;cursor:pointer;font-size:.78rem;transition:border-color .15s';
                card.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start gap-1">
                        <div>
                            <div style="font-weight:600;font-size:.82rem;">${p.product_name}</div>
                            <div style="color:#64748b;font-size:.72rem;font-family:monospace;">${p.dsi_code}</div>
                        </div>
                        <span class="badge bg-${scoreColor}-subtle text-${scoreColor} border flex-shrink-0">${p.score}%</span>
                    </div>
                    <div class="d-flex gap-3 mt-1" style="color:#475569;">
                        <span>Publish: <strong>${p.publish_rate.toLocaleString('id-ID')}</strong></span>
                        <span>Nett: <strong>${p.nett_price.toLocaleString('id-ID')}</strong></span>
                    </div>`;
                card.addEventListener('click', () => {
                    mobile.querySelectorAll('.sim-card').forEach(c => c.classList.remove('border-primary'));
                    card.classList.add('border-primary');
                    body.querySelectorAll('input[type=radio]').forEach(r => r.checked = r.value == p.id);
                    selectSimilarProduct(p.id);
                });
                mobile.appendChild(card);
            });

            cont.classList.remove('d-none'); empty.classList.add('d-none');

            body.querySelectorAll('.similar-radio').forEach(r => {
                r.addEventListener('change', () => selectSimilarProduct(r.value));
            });
        })
        .catch(() => { spinner.classList.add('d-none'); btn.disabled = false; });
}

function selectSimilarProduct(productId) {
    document.getElementById('reassignProductId').value    = productId;
    document.getElementById('reassignSubmitBtn').disabled = false;
}
</script>
@endpush

@endsection
