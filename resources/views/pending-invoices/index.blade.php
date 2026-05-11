@extends('layouts.app')
@section('title', 'Antrian Invoice')
@section('page-title', 'Antrian Invoice')

@push('styles')
<style>
    /* ── Stats bar ── */
    .queue-stat {
        background: #fff;
        border-radius: 12px;
        padding: .85rem 1.2rem;
        box-shadow: 0 1px 4px rgba(0,0,0,.07);
        display: flex;
        align-items: center;
        gap: .85rem;
    }
    .queue-stat .stat-icon {
        width: 42px; height: 42px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    .queue-stat .stat-label  { font-size: .72rem; color: #6c757d; line-height: 1.2; }
    .queue-stat .stat-value  { font-size: 1.15rem; font-weight: 700; line-height: 1.2; }

    /* ── Filter card ── */
    .filter-card {
        background: #fff;
        border-radius: 12px;
        padding: .85rem 1.1rem;
        box-shadow: 0 1px 4px rgba(0,0,0,.07);
    }

    /* ── Table card ── */
    .table-card {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 2px 8px rgba(0,0,0,.08);
        overflow: hidden;
    }
    .table-card table thead th {
        background: #f6f7fb;
        border-bottom: 2px solid #e9ecef;
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: #6c757d;
        padding: .65rem .85rem;
        white-space: nowrap;
    }
    .table-card table tbody tr {
        border-bottom: 1px solid #f0f2f5;
        transition: background .12s;
    }
    .table-card table tbody tr:last-child { border-bottom: none; }
    .table-card table tbody tr:hover { background: #f8f9ff; }
    .table-card table td { padding: .7rem .85rem; vertical-align: middle; }

    /* ── Urgency row highlight ── */
    tr.row-danger  { border-left: 3px solid #dc3545; }
    tr.row-warning { border-left: 3px solid #ffc107; }
    tr.row-ok      { border-left: 3px solid transparent; }

    /* ── Day badge ── */
    .day-badge {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        border-radius: 20px;
        padding: .2rem .65rem;
        font-size: .75rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .day-badge.danger  { background: #fff0f0; color: #dc3545; border: 1px solid #f5c6cb; }
    .day-badge.warning { background: #fffbec; color: #856404; border: 1px solid #ffecb5; }
    .day-badge.info    { background: #e8f4ff; color: #0369a1; border: 1px solid #b6d4fe; }
    .day-badge.future  { background: #f0fff4; color: #166534; border: 1px solid #a3cfbb; }
    .day-badge.ok      { background: #f5f5f5; color: #6c757d; border: 1px solid #dee2e6; }

    /* ── Trx number chip ── */
    .trx-chip {
        background: #f0f4ff;
        color: #3b5acd;
        border-radius: 6px;
        padding: .15rem .5rem;
        font-size: .78rem;
        font-family: monospace;
        font-weight: 600;
        letter-spacing: .3px;
        white-space: nowrap;
    }

    /* ── Btn create ── */
    .btn-create {
        background: linear-gradient(135deg, #0d6efd, #3b8bf7);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: .35rem .85rem;
        font-size: .78rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        transition: box-shadow .15s, transform .1s;
        white-space: nowrap;
        text-decoration: none;
    }
    .btn-create:hover {
        color: #fff;
        box-shadow: 0 4px 12px rgba(13,110,253,.35);
        transform: translateY(-1px);
    }

    /* ── tfoot ── */
    .table-card tfoot td {
        background: #f6f7fb;
        border-top: 2px solid #e9ecef;
        font-size: .82rem;
        padding: .65rem .85rem;
    }

    /* ── Empty state ── */
    .empty-state { padding: 4rem 2rem; text-align: center; }
    .empty-state .icon-wrap {
        width: 72px; height: 72px;
        border-radius: 50%;
        background: #e8f9ee;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 1.1rem;
        font-size: 2rem;
        color: #198754;
    }
</style>
@endpush

@section('content')

{{-- ── Header + Stats ── --}}
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Antrian Buat Invoice</h5>
        <div class="text-muted small mt-1">Transaksi disetujui yang belum dibuatkan invoice — diurutkan dari terlama</div>
    </div>
</div>

<div class="row g-3 mb-3">
    {{-- Total pending --}}
    <div class="col-6 col-md-3">
        <div class="queue-stat">
            <div class="stat-icon" style="background:#fff3cd">
                <i class="bi bi-hourglass-split text-warning"></i>
            </div>
            <div>
                <div class="stat-label">Total Pending</div>
                <div class="stat-value text-warning">{{ $transactions->total() }}</div>
            </div>
        </div>
    </div>
    {{-- Mendesak ≥7 hari --}}
    @php
        $urgentCount  = $transactions->filter(fn($t) => round(\Carbon\Carbon::parse($t->date)->floatDiffInDays(now())) >= 7)->count();
        $warningCount = $transactions->filter(fn($t) => ($d = round(\Carbon\Carbon::parse($t->date)->floatDiffInDays(now()))) >= 3 && $d < 7)->count();
        $totalAmt     = $transactions->sum('total_amount');
        $totalKomisi  = $transactions->sum('total_komisi');
    @endphp
    <div class="col-6 col-md-3">
        <div class="queue-stat">
            <div class="stat-icon" style="background:#fde8e8">
                <i class="bi bi-exclamation-triangle-fill text-danger"></i>
            </div>
            <div>
                <div class="stat-label">Mendesak (&ge;7 hari)</div>
                <div class="stat-value text-danger">{{ $urgentCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="queue-stat">
            <div class="stat-icon" style="background:#e8f0ff">
                <i class="bi bi-cash-stack text-primary"></i>
            </div>
            <div>
                <div class="stat-label">Total Amount</div>
                <div class="stat-value" style="font-size:.95rem">Rp {{ number_format($totalAmt, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="queue-stat">
            <div class="stat-icon" style="background:#e8faf0">
                <i class="bi bi-graph-up-arrow text-success"></i>
            </div>
            <div>
                <div class="stat-label">Total Komisi</div>
                <div class="stat-value text-success" style="font-size:.95rem">Rp {{ number_format($totalKomisi, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Filter ── --}}
<div class="filter-card mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-12 col-sm-5 col-md-4">
            <label class="form-label fw-semibold mb-1" style="font-size:.75rem">Cari Transaksi</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0"
                       placeholder="No. transaksi / nama tiket…" value="{{ request('search') }}">
            </div>
        </div>
        <div class="col-6 col-sm-3 col-md-2">
            <label class="form-label fw-semibold mb-1" style="font-size:.75rem">Dari Tanggal</label>
            <input type="date" name="date_from" class="form-control form-control-sm"
                   value="{{ request('date_from') }}">
        </div>
        <div class="col-6 col-sm-3 col-md-2">
            <label class="form-label fw-semibold mb-1" style="font-size:.75rem">Sampai Tanggal</label>
            <input type="date" name="date_to" class="form-control form-control-sm"
                   value="{{ request('date_to') }}">
        </div>
        <div class="col-auto d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm px-3">
                <i class="bi bi-funnel-fill me-1"></i> Filter
            </button>
            <a href="{{ route('pending-invoices.index') }}" class="btn btn-outline-secondary btn-sm px-3">
                <i class="bi bi-x-lg"></i> Reset
            </a>
        </div>
    </form>
</div>

{{-- ── Table ── --}}
<div class="table-card">
    @if($transactions->isEmpty())
        <div class="empty-state">
            <div class="icon-wrap"><i class="bi bi-check2-circle"></i></div>
            <div class="fw-bold fs-6">Semua transaksi sudah dibuatkan invoice!</div>
            <div class="text-muted small mt-1">Tidak ada transaksi pending saat ini.</div>
        </div>
    @else
    <div class="table-responsive">
        <table class="table mb-0" style="border-collapse: separate; border-spacing: 0;">
            <thead>
                <tr>
                    <th class="ps-3 d-none d-sm-table-cell" style="width:40px">#</th>
                    <th>No. Transaksi</th>
                    <th class="d-none d-md-table-cell">Tiket / Layanan</th>
                    <th class="text-center d-none d-lg-table-cell" style="width:70px">Item</th>
                    <th class="text-center d-none d-sm-table-cell">Tgl Kedatangan</th>
                    <th class="text-center">Selisih Hari</th>
                    <th class="text-end">Total</th>
                    <th class="text-end d-none d-md-table-cell">Komisi</th>
                    <th class="text-center pe-3" style="width:120px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $i => $trx)
                @php
                    $daysAgo  = (int) round(\Carbon\Carbon::parse($trx->date)->floatDiffInDays(now(), false));
                    $urgency  = $daysAgo >= 7 ? 'danger'  : ($daysAgo >= 3 ? 'warning' : ($daysAgo == 0 ? 'info' : ($daysAgo < 0 ? 'future' : 'ok')));
                    $rowClass = $daysAgo >= 7 ? 'row-danger' : ($daysAgo >= 3 ? 'row-warning' : 'row-ok');
                @endphp
                <tr class="{{ $rowClass }}">
                    <td class="ps-3 text-muted small d-none d-sm-table-cell">{{ $transactions->firstItem() + $i }}</td>
                    <td>
                        <span class="trx-chip">{{ $trx->transaction_no ?? '—' }}</span>
                        @if(isset($trx->has_unhandled) && $trx->has_unhandled)
                            <i class="bi bi-exclamation-triangle-fill text-warning ms-1" 
                               title="Transaksi ini memiliki item Anomali yang belum ditangani. Invoice mungkin tidak lengkap."
                               data-bs-toggle="tooltip"></i>
                        @endif
                    </td>
                    <td class="small d-none d-md-table-cell" style="max-width:200px">
                        <span class="d-block text-truncate" title="{{ $trx->ticket_names }}">
                            {{ $trx->ticket_names ?: '—' }}
                        </span>
                    </td>
                    <td class="text-center d-none d-lg-table-cell">
                        <span class="badge rounded-pill" style="background:#e8f0ff;color:#3b5acd;font-weight:600;font-size:.73rem">
                            {{ $trx->item_count }} item
                        </span>
                    </td>
                    <td class="text-center d-none d-sm-table-cell">
                        <span class="fw-semibold small {{ $daysAgo >= 7 ? 'text-danger' : ($daysAgo >= 3 ? 'text-warning' : 'text-dark') }}">
                            {{ \Carbon\Carbon::parse($trx->date)->isoFormat('D MMM Y') }}
                        </span>
                    </td>
                    <td class="text-center">
                        @if($daysAgo > 0)
                            <span class="day-badge {{ $urgency }}">
                                <i class="bi bi-clock{{ $daysAgo >= 7 ? '-history' : '' }}"></i>
                                {{ $daysAgo }} hari lalu
                            </span>
                        @elseif($daysAgo == 0)
                            <span class="day-badge info">
                                <i class="bi bi-dot" style="font-size:1.1em"></i> Hari ini
                            </span>
                        @else
                            <span class="day-badge future">
                                <i class="bi bi-calendar-check"></i>
                                {{ abs($daysAgo) }} hari lagi
                            </span>
                        @endif
                    </td>
                    <td class="text-end fw-bold small">
                        Rp {{ number_format($trx->total_amount ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="text-end small d-none d-md-table-cell">
                        @if($trx->total_komisi)
                            <span class="text-success fw-semibold">
                                Rp {{ number_format($trx->total_komisi, 0, ',', '.') }}
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center pe-3">
                        <a href="{{ route('invoices.create', ['transaction_no' => $trx->transaction_no, 'visit_date' => \Carbon\Carbon::parse($trx->date)->format('Y-m-d')]) }}"
                           class="btn-create">
                            <i class="bi bi-file-earmark-plus"></i> Buat
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="ps-3 fw-semibold text-muted">
                        <i class="bi bi-calculator me-1"></i> Total (halaman ini)
                    </td>
                    <td class="text-end fw-bold">Rp {{ number_format($transactions->sum('total_amount'), 0, ',', '.') }}</td>
                    <td class="text-end fw-bold text-success d-none d-md-table-cell">Rp {{ number_format($transactions->sum('total_komisi'), 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="px-3 py-2 border-top">
        {{ $transactions->links() }}
    </div>
    @endif
</div>
@endsection
