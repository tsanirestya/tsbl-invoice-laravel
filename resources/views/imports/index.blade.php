@extends('layouts.app')
@section('title', 'Import Transaksi')
@section('page-title', 'Import Transaksi')

@push('styles')
<style>
    /* ── Import Stats ── */
    .imp-stat {
        background: #fff;
        border-radius: 14px;
        padding: 1rem 1.25rem;
        display: flex; align-items: center; gap: .9rem;
        box-shadow: 0 1px 4px rgba(15,23,41,.06), 0 4px 14px rgba(15,23,41,.04);
        transition: transform .18s, box-shadow .18s;
        border: 1px solid rgba(0,0,0,.04);
    }
    .imp-stat:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(15,23,41,.1); }
    .imp-stat-icon {
        width: 44px; height: 44px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; flex-shrink: 0;
    }
    .imp-stat-label { font-size: .67rem; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #94a3b8; margin-bottom: 1px; }
    .imp-stat-value { font-size: 1.45rem; font-weight: 800; color: #1e293b; line-height: 1; }

    /* ── Table wrap ── */
    .imp-table-wrap {
        background: #fff; border-radius: 14px;
        box-shadow: 0 1px 4px rgba(15,23,41,.06);
        overflow: hidden;
    }
    .imp-table-hdr {
        padding: .85rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .5rem;
    }
    .imp-table-wrap table thead th {
        background: #f8fafc;
        font-size: .67rem; font-weight: 700; letter-spacing: .55px; text-transform: uppercase;
        color: #64748b; border-bottom: 1px solid #f1f5f9; padding: .65rem 1rem; white-space: nowrap;
    }
    .imp-table-wrap table tbody td {
        padding: .72rem 1rem; font-size: .84rem;
        border-bottom: 1px solid #f8fafc; vertical-align: middle;
    }
    .imp-table-wrap table tbody tr:last-child td { border-bottom: none; }
    .imp-table-wrap table tbody tr:hover { background: #fafbff; }

    /* filename */
    .imp-filename {
        font-weight: 600; color: #1e293b;
        max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        display: block;
    }
    .imp-filename:hover { color: #3b82f6; }

    /* stat chips */
    .imp-chip {
        display: inline-flex; align-items: center; gap: .28rem;
        padding: .15rem .5rem; border-radius: 20px;
        font-size: .72rem; font-weight: 600;
    }
    .imp-chip-green  { background: #f0fdf4; color: #166534; }
    .imp-chip-red    { background: #fef2f2; color: #991b1b; }
    .imp-chip-slate  { background: #f1f5f9; color: #475569; }
    .imp-chip-orange { background: #fff7ed; color: #9a3412; }

    /* Action buttons */
    .imp-btn {
        width: 30px; height: 30px; border-radius: 8px;
        border: 1px solid #e2e8f0; background: #fff; color: #64748b;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .8rem; text-decoration: none;
        transition: background .15s, color .15s, border-color .15s, transform .15s;
        cursor: pointer;
    }
    .imp-btn:hover { transform: translateY(-1px); }
    .imp-btn.view:hover { background: #eff6ff; color: #3b82f6; border-color: #bfdbfe; }
    .imp-btn.del:hover  { background: #fef2f2; color: #dc2626; border-color: #fca5a5; }

    /* Empty */
    .imp-empty { padding: 3.5rem 1rem; text-align: center; color: #94a3b8; }
    .imp-empty i { font-size: 3.5rem; opacity: .3; display: block; margin-bottom: .75rem; }

    /* Mobile card */
    .imp-mobile-card {
        padding: .9rem 1rem; border-bottom: 1px solid #f1f5f9;
    }
    .imp-mobile-card:last-child { border-bottom: none; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-bold" style="letter-spacing:-.2px;">Import Transaksi</h5>
        <p class="text-muted mb-0" style="font-size:.8rem;">Riwayat upload dan validasi file transaksi</p>
    </div>
    <a href="{{ route('imports.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-2" style="border-radius:10px;padding:.45rem 1rem;">
        <i class="bi bi-upload"></i> <span class="d-none d-sm-inline">Upload Baru</span>
    </a>
</div>

{{-- Stats --}}
@php
    $totalImports  = $imports->total();
    $doneCount     = $imports->getCollection()->where('status','done')->count();
    $pendingCount  = $imports->getCollection()->whereIn('status',['pending','processing','reviewed'])->count();
    $totalRows     = $imports->getCollection()->sum('total_rows');
    $totalAnomaly  = $imports->getCollection()->sum('anomaly_rows');
@endphp
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="imp-stat">
            <div class="imp-stat-icon" style="background:#eff6ff;color:#3b82f6;"><i class="bi bi-file-earmark-spreadsheet-fill"></i></div>
            <div>
                <div class="imp-stat-label">Total Import</div>
                <div class="imp-stat-value">{{ $imports->total() }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="imp-stat">
            <div class="imp-stat-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="imp-stat-label">Selesai</div>
                <div class="imp-stat-value">{{ $doneCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="imp-stat">
            <div class="imp-stat-icon" style="background:#fff7ed;color:#ea580c;"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <div class="imp-stat-label">Pending Review</div>
                <div class="imp-stat-value">{{ $pendingCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="imp-stat">
            <div class="imp-stat-icon" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div>
                <div class="imp-stat-label">Anomaly (halaman ini)</div>
                <div class="imp-stat-value">{{ number_format($totalAnomaly) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Table --}}
<div class="imp-table-wrap">
    <div class="imp-table-hdr">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-file-earmark-spreadsheet" style="color:#3b82f6;"></i>
            <span class="fw-semibold" style="font-size:.88rem;">Riwayat Import</span>
        </div>
        <span style="font-size:.75rem;color:#94a3b8;">{{ $imports->total() }} data</span>
    </div>

    @if($imports->isEmpty())
        <div class="imp-empty">
            <i class="bi bi-inbox"></i>
            <p class="fw-semibold mb-1" style="color:#64748b;">Belum ada import</p>
            <p style="font-size:.85rem;">
                <a href="{{ route('imports.create') }}">Upload file transaksi pertama</a> untuk mulai memproses data.
            </p>
        </div>
    @else

    {{-- Desktop table --}}
    <div class="d-none d-md-block table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th class="ps-4">File</th>
                    <th>Upload Oleh</th>
                    <th>Tanggal</th>
                    <th class="text-center">Total</th>
                    <th class="text-center">Valid</th>
                    <th class="text-center">Anomaly</th>
                    <th class="text-center">Rejected</th>
                    <th>Status</th>
                    <th class="text-center pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($imports as $imp)
                @php
                    $statusInfo = match($imp->status) {
                        'done'       => ['label'=>'Selesai',    'bg'=>'#f0fdf4', 'color'=>'#166534', 'icon'=>'bi-check-circle-fill'],
                        'reviewed'   => ['label'=>'Review',     'bg'=>'#eff6ff', 'color'=>'#1d4ed8', 'icon'=>'bi-eye-fill'],
                        'processing' => ['label'=>'Diproses',   'bg'=>'#fff7ed', 'color'=>'#9a3412', 'icon'=>'bi-arrow-repeat'],
                        default      => ['label'=>'Pending',    'bg'=>'#f8fafc', 'color'=>'#475569', 'icon'=>'bi-clock'],
                    };
                @endphp
                <tr>
                    <td class="ps-4" style="max-width:220px;">
                        <a href="{{ route('imports.show', $imp) }}" class="imp-filename" title="{{ $imp->original_filename }}">
                            <i class="bi bi-file-earmark-spreadsheet text-success me-1"></i>{{ $imp->original_filename }}
                        </a>
                    </td>
                    <td style="color:#64748b;">{{ $imp->uploader->full_name ?? '-' }}</td>
                    <td style="color:#94a3b8;white-space:nowrap;">{{ $imp->uploaded_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td class="text-center">
                        <span class="imp-chip imp-chip-slate">{{ number_format($imp->total_rows) }}</span>
                    </td>
                    <td class="text-center">
                        <span class="imp-chip imp-chip-green">
                            <i class="bi bi-check-circle-fill" style="font-size:.65rem;"></i>
                            {{ number_format($imp->valid_rows) }}
                        </span>
                    </td>
                    <td class="text-center">
                        @if($imp->anomaly_rows > 0)
                            <span class="imp-chip imp-chip-red">
                                <i class="bi bi-exclamation-circle-fill" style="font-size:.65rem;"></i>
                                {{ number_format($imp->anomaly_rows) }}
                                <span style="opacity:.7;">({{ $imp->anomalyRate() }}%)</span>
                            </span>
                        @else
                            <span class="imp-chip imp-chip-green">
                                <i class="bi bi-check2" style="font-size:.65rem;"></i> 0
                            </span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="imp-chip imp-chip-slate">{{ number_format($imp->rejected_rows) }}</span>
                    </td>
                    <td>
                        <span class="imp-chip" style="background:{{ $statusInfo['bg'] }};color:{{ $statusInfo['color'] }};">
                            <i class="bi {{ $statusInfo['icon'] }}" style="font-size:.65rem;"></i>
                            {{ $statusInfo['label'] }}
                        </span>
                    </td>
                    <td class="text-center pe-4">
                        <div class="d-flex gap-1 justify-content-center">
                            <a href="{{ route('imports.show', $imp) }}" class="imp-btn view" title="Review">
                                <i class="bi bi-eye"></i>
                            </a>
                            @if($imp->status !== 'done')
                            <form method="POST" action="{{ route('imports.destroy', $imp) }}" class="d-inline"
                                  onsubmit="return confirm('Hapus import ini?')">
                                @csrf @method('DELETE')
                                <button class="imp-btn del" title="Hapus"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile card list --}}
    <div class="d-md-none">
        @foreach($imports as $imp)
        @php
            $statusInfo = match($imp->status) {
                'done'       => ['label'=>'Selesai',  'bg'=>'#f0fdf4','color'=>'#166534','icon'=>'bi-check-circle-fill'],
                'reviewed'   => ['label'=>'Review',   'bg'=>'#eff6ff','color'=>'#1d4ed8','icon'=>'bi-eye-fill'],
                'processing' => ['label'=>'Diproses', 'bg'=>'#fff7ed','color'=>'#9a3412','icon'=>'bi-arrow-repeat'],
                default      => ['label'=>'Pending',  'bg'=>'#f8fafc','color'=>'#475569','icon'=>'bi-clock'],
            };
        @endphp
        <div class="imp-mobile-card">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <div class="min-w-0" style="flex:1;">
                    <div class="fw-semibold" style="font-size:.85rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <i class="bi bi-file-earmark-spreadsheet text-success me-1"></i>{{ $imp->original_filename }}
                    </div>
                    <div style="font-size:.73rem;color:#94a3b8;margin-top:2px;">
                        {{ $imp->uploader->full_name ?? '-' }} &middot; {{ $imp->uploaded_at?->format('d/m/Y H:i') ?? '-' }}
                    </div>
                </div>
                <span class="imp-chip flex-shrink-0" style="background:{{ $statusInfo['bg'] }};color:{{ $statusInfo['color'] }};">
                    <i class="bi {{ $statusInfo['icon'] }}" style="font-size:.65rem;"></i>
                    {{ $statusInfo['label'] }}
                </span>
            </div>
            <div class="d-flex align-items-center justify-content-between gap-2">
                <div class="d-flex gap-1 flex-wrap">
                    <span class="imp-chip imp-chip-green"><i class="bi bi-check-circle-fill" style="font-size:.6rem;"></i>{{ number_format($imp->valid_rows) }}</span>
                    @if($imp->anomaly_rows > 0)
                    <span class="imp-chip imp-chip-red"><i class="bi bi-exclamation-circle-fill" style="font-size:.6rem;"></i>{{ number_format($imp->anomaly_rows) }}</span>
                    @endif
                    <span class="imp-chip imp-chip-slate">{{ number_format($imp->total_rows) }} total</span>
                </div>
                <div class="d-flex gap-1">
                    <a href="{{ route('imports.show', $imp) }}" class="imp-btn view" title="Review"><i class="bi bi-eye"></i></a>
                    @if($imp->status !== 'done')
                    <form method="POST" action="{{ route('imports.destroy', $imp) }}" class="d-inline" onsubmit="return confirm('Hapus import ini?')">
                        @csrf @method('DELETE')
                        <button class="imp-btn del"><i class="bi bi-trash"></i></button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($imports->hasPages())
    <div class="px-4 py-3" style="border-top:1px solid #f1f5f9;">
        {{ $imports->links() }}
    </div>
    @endif

    @endif
</div>

@endsection
