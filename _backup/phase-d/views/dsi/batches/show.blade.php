@extends('layouts.app')
@section('title', 'Detail Batch — '.$batch->batch_ref)
@section('page-title', 'DSI Batch Detail')

@push('styles')
<style>
    .stat-mini { background:#f8fafc; border-radius:10px; padding:.75rem 1rem; border:1px solid #e2e8f0; }
    .stat-mini-label { font-size:.65rem; font-weight:700; text-transform:uppercase; color:#94a3b8; margin-bottom:2px; }
    .stat-mini-value { font-size:1.1rem; font-weight:800; color:#1e293b; }
    .badge-duplicate { background:#fff7ed; color:#c2410c; border:1px solid #ffedd5; font-size:.65rem; }
</style>
@endpush

@section('content')

<div class="d-flex align-items-center gap-2 mb-3 page-hdr">
    <a href="{{ route('dsi.batches.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;"><i class="bi bi-arrow-left"></i></a>
    <div>
        <div class="page-title">{{ $batch->batch_ref }}</div>
        <div class="page-sub">Detail Batch Import DSI</div>
    </div>
    <div class="ms-auto">
        @php $sCls = match($batch->status){'COMPLETED'=>'bg-success','PARTIAL'=>'bg-warning','FAILED'=>'bg-danger','PROCESSING'=>'bg-primary',default=>'bg-secondary'}; @endphp
        <span class="badge {{ $sCls }} fs-6 px-3 py-2">{{ $batch->status }}</span>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-mini">
            <div class="stat-mini-label">Total Rows</div>
            <div class="stat-mini-value">{{ $batch->processed_rows }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-mini">
            <div class="stat-mini-label">Failed Rows</div>
            <div class="stat-mini-value text-danger">{{ $batch->failed_rows }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-mini">
            <div class="stat-mini-label">File Hash</div>
            <div class="stat-mini-value" style="font-size:.7rem; font-family:monospace; word-break:break-all;">{{ $batch->file_hash }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-mini">
            <div class="stat-mini-label">Imported At</div>
            <div class="stat-mini-value">{{ $batch->created_at->format('d M Y, H:i') }}</div>
        </div>
    </div>
</div>

<div class="card card-clean">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-list-check" style="color:#3b82f6"></i>
        Transaksi dalam Batch ini
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0" style="font-size:.83rem;">
                <thead>
                    <tr>
                        <th class="ps-4">Ref No</th>
                        <th>Guest Name</th>
                        <th>Check-in</th>
                        <th class="text-end">Amount</th>
                        <th class="text-center">Status</th>
                        <th class="text-center pe-3">Flags</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($batch->transactions as $tx)
                    <tr>
                        <td class="ps-4 fw-bold">{{ $tx->ref_no }}</td>
                        <td>{{ $tx->guest_name }}</td>
                        <td>{{ $tx->check_in_date?->format('d/m/Y') ?? '-' }}</td>
                        <td class="text-end fw-bold">Rp {{ number_format($tx->total_amount, 0, ',', '.') }}</td>
                        <td class="text-center">
                            @if($tx->is_locked)
                                <span class="badge bg-danger"><i class="bi bi-lock-fill"></i> Locked</span>
                            @else
                                <span class="badge bg-success"><i class="bi bi-unlock-fill"></i> Open</span>
                            @endif
                        </td>
                        <td class="text-center pe-3">
                            @if($tx->duplicateFlags->isNotEmpty())
                                @php $pendingFlags = $tx->duplicateFlags->where('status', 'PENDING')->count(); @endphp
                                @if($pendingFlags > 0)
                                    <span class="badge badge-duplicate"><i class="bi bi-flag-fill"></i> {{ $pendingFlags }} Pending Review</span>
                                @else
                                    <span class="badge bg-light text-muted"><i class="bi bi-flag"></i> Checked</span>
                                @endif
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
