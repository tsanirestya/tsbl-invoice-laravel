@extends('layouts.app')
@section('title', 'Riwayat Batch DSI')
@section('page-title', 'DSI Import Batches')

@push('styles')
<style>
    .batch-wrap { background:#fff; border-radius:11px; box-shadow:0 1px 3px rgba(15,23,41,.06); overflow:hidden; }
    .batch-table-hdr { padding:.8rem 1.1rem; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; }
    .batch-wrap table thead th { background:#f8fafc; font-size:.65rem; font-weight:700; letter-spacing:.55px; text-transform:uppercase; color:#64748b; border-bottom:1px solid #f1f5f9; padding:.62rem 1rem; white-space:nowrap; }
    .batch-wrap table tbody td { padding:.65rem 1rem; font-size:.83rem; border-bottom:1px solid #f8fafc; vertical-align:middle; }
    .batch-wrap table tbody tr:last-child td { border-bottom:none; }
    .batch-wrap table tbody tr:hover { background:#fafbff; }
    .batch-link { font-weight:700; color:#1e40af; text-decoration:none; }
    .batch-link:hover { color:#3b82f6; text-decoration:underline; }

    .bs-completed { background:#f0fdf4; color:#166534; }
    .bs-partial   { background:#fff7ed; color:#c2410c; }
    .bs-failed    { background:#fef2f2; color:#991b1b; }
    .bs-processing { background:#eff6ff; color:#1d4ed8; }

    .filter-panel { background:#fff; border-radius:11px; padding:.9rem 1.1rem; box-shadow:0 1px 3px rgba(15,23,41,.06); margin-bottom:1rem; }
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 page-hdr">
    <div>
        <div class="page-title">Riwayat Batch DSI</div>
        <div class="page-sub">Daftar semua batch impor data DSI</div>
    </div>
    <a href="{{ route('dsi.import.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1" style="border-radius:9px;">
        <i class="bi bi-upload"></i>
        <span>Import Baru</span>
    </a>
</div>

<div class="filter-panel">
    <form method="GET" action="{{ route('dsi.batches.index') }}" id="batch-filter-form">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="filter-label" style="font-size:.65rem; font-weight:700; text-transform:uppercase; color:#94a3b8; display:block; margin-bottom:.3rem;">Pencarian</label>
                <input type="text" name="search" class="form-control" placeholder="Batch Ref / File Name" value="{{ request('search') }}" style="border-radius:8px; font-size:.82rem;">
            </div>
            <div class="col-md-3">
                <label class="filter-label" style="font-size:.65rem; font-weight:700; text-transform:uppercase; color:#94a3b8; display:block; margin-bottom:.3rem;">Status</label>
                <select name="status" class="form-select" style="border-radius:8px; font-size:.82rem;">
                    <option value="">Semua</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm" style="border-radius:8px; padding:.38rem .85rem;"><i class="bi bi-search"></i></button>
            </div>
        </div>
    </form>
</div>

<div class="batch-wrap">
    <div class="batch-table-hdr">
        <span class="fw-semibold" style="font-size:.86rem;">Semua Batch</span>
        <span style="font-size:.73rem;color:#94a3b8;">{{ $batches->total() }} data</span>
    </div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Batch Ref</th>
                    <th>File Name</th>
                    <th>Source</th>
                    <th class="text-center">Rows</th>
                    <th class="text-center">Failed</th>
                    <th class="text-center">Status</th>
                    <th>Imported By</th>
                    <th>Date</th>
                    <th class="text-center pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($batches as $batch)
                @php $sCls = 'bs-' . strtolower($batch->status); @endphp
                <tr>
                    <td class="ps-4"><a href="{{ route('dsi.batches.show', $batch) }}" class="batch-link">{{ $batch->batch_ref }}</a></td>
                    <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $batch->file_name ?? '-' }}</td>
                    <td>{{ $batch->source }}</td>
                    <td class="text-center fw-bold">{{ $batch->processed_rows }}</td>
                    <td class="text-center {{ $batch->failed_rows > 0 ? 'text-danger' : '' }}">{{ $batch->failed_rows }}</td>
                    <td class="text-center"><span class="badge {{ $sCls }}">{{ $batch->status }}</span></td>
                    <td>{{ $batch->importedBy?->full_name ?? 'System' }}</td>
                    <td>{{ $batch->created_at->format('d/m/Y H:i') }}</td>
                    <td class="text-center pe-3">
                        <a href="{{ route('dsi.batches.show', $batch) }}" class="btn btn-sm btn-outline-primary" style="border-radius:7px;"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($batches->hasPages())
    <div class="px-4 py-3" style="border-top:1px solid #f1f5f9;">
        {{ $batches->links() }}
    </div>
    @endif
</div>

@endsection
