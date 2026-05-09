@extends('layouts.app')
@section('title', 'Review Rekonsiliasi #'.$reconciliation->id)
@section('page-title', 'Reconciliation Detail')

@push('styles')
<style>
    .comp-card { background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(15,23,41,.07); overflow:hidden; border:1px solid #f1f5f9; height:100%; }
    .comp-hdr { padding:.8rem 1.1rem; border-bottom:1px solid #f1f5f9; background:#f8fafc; font-weight:700; color:#475569; display:flex; align-items:center; gap:.5rem; }
    .comp-body { padding:0; }
    .comp-table { width:100%; font-size:.82rem; }
    .comp-table th { background:#fff; color:#94a3b8; font-size:.65rem; text-transform:uppercase; letter-spacing:.5px; padding:.6rem 1rem; border-bottom:1px solid #f1f5f9; }
    .comp-table td { padding:.6rem 1rem; border-bottom:1px solid #f8fafc; vertical-align:middle; }
    .comp-table tr:last-child td { border-bottom:none; }

    .delta-banner { border-radius:12px; padding:1rem 1.25rem; display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; }
    .delta-banner.balanced { background:#f0fdf4; border:1px solid #dcfce7; color:#166534; }
    .delta-banner.diff     { background:#fff7ed; border:1px solid #ffedd5; color:#c2410c; }
    .delta-banner.warn     { background:#fef2f2; border:1px solid #fee2e2; color:#991b1b; }

    .status-pill { display:inline-block; padding:4px 12px; border-radius:20px; font-size:.75rem; font-weight:700; }
    .sp-pending_review { background:#eff6ff; color:#1d4ed8; }
    .sp-approved       { background:#f0fdf4; color:#166534; }
    .sp-disputed       { background:#fff7ed; color:#c2410c; }
    .sp-rejected       { background:#fef2f2; color:#991b1b; }
</style>
@endpush

@section('content')

<div class="d-flex align-items-center gap-2 mb-3 page-hdr">
    <a href="{{ route('reconciliations.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;"><i class="bi bi-arrow-left"></i></a>
    <div>
        <div class="page-title">Rekonsiliasi #{{ $reconciliation->id }}</div>
        <div class="page-sub">Review perbandingan untuk Reservasi {{ $reconciliation->reservation?->reservation_no }}</div>
    </div>
    <div class="ms-auto">
        <span class="status-pill sp-{{ strtolower($reconciliation->status) }}">{{ $reconciliation->status }}</span>
    </div>
</div>

{{-- Delta Banner --}}
@php
    $delta = $reconciliation->delta_amount;
    $isBalanced = round($delta) == 0;
    $bannerCls = $isBalanced ? 'balanced' : ($delta > 0 ? 'diff' : 'warn');
    $bannerIcon = $isBalanced ? 'check-circle-fill' : 'exclamation-triangle-fill';
@endphp
<div class="delta-banner {{ $bannerCls }}">
    <div class="d-flex align-items-center gap-3">
        <i class="bi bi-{{ $bannerIcon }} fs-4"></i>
        <div>
            <div class="fw-bold fs-5">
                @if($isBalanced)
                    Data Sesuai (Balanced)
                @else
                    Terdapat Selisih: Rp {{ number_format($delta, 0, ',', '.') }}
                @endif
            </div>
            <div class="small opacity-75">Perbandingan antara tagihan awal (Proforma) dengan data transaksi aktual (DSI)</div>
        </div>
    </div>
    <div class="text-end">
        <div class="small fw-bold opacity-75 text-uppercase">Delta Terdeteksi</div>
        <div class="fs-4 fw-800">Rp {{ number_format($delta, 0, ',', '.') }}</div>
    </div>
</div>

<div class="row g-4">
    {{-- Left: Proforma side --}}
    <div class="col-lg-6">
        <div class="comp-card">
            <div class="comp-hdr">
                <i class="bi bi-file-earmark-text text-primary"></i>
                DATA PROFORMA (TAGIHAN AWAL)
                @if($reconciliation->proformaInvoice)
                    <span class="ms-auto badge bg-primary opacity-75">{{ $reconciliation->proformaInvoice->invoice_no }}</span>
                @endif
            </div>
            <div class="comp-body">
                <table class="comp-table">
                    <thead>
                        <tr>
                            <th>Deskripsi Item</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($reconciliation->proformaInvoice)
                            @foreach($reconciliation->proformaInvoice->items as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end fw-bold">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        @else
                            <tr><td colspan="3" class="text-center py-4 text-muted">Data Proforma tidak tersedia</td></tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr style="background:#f8fafc; border-top:1px solid #e2e8f0;">
                            <td colspan="2" class="ps-3 py-3 fw-bold">Total Proforma</td>
                            <td class="text-end pe-3 py-3 fw-800 text-primary fs-6">Rp {{ number_format($reconciliation->proforma_amount, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Right: DSI side --}}
    <div class="col-lg-6">
        <div class="comp-card">
            <div class="comp-hdr">
                <i class="bi bi-table text-info"></i>
                DATA DSI (TRANSAKSI AKTUAL)
                @if($reconciliation->dsiTransaction)
                    <span class="ms-auto badge bg-info text-white opacity-75">{{ $reconciliation->dsiTransaction->ref_no }}</span>
                @endif
            </div>
            <div class="comp-body">
                <table class="comp-table">
                    <thead>
                        <tr>
                            <th>Deskripsi Layanan</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($reconciliation->dsiTransaction)
                            @foreach($reconciliation->dsiTransaction->lineItems as $line)
                            <tr>
                                <td>{{ $line->service_description ?? 'Layanan' }}</td>
                                <td class="text-center">{{ $line->quantity }}</td>
                                <td class="text-end fw-bold">Rp {{ number_format($line->amount, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        @else
                            <tr><td colspan="3" class="text-center py-4 text-muted">Data DSI tidak tersedia</td></tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr style="background:#f8fafc; border-top:1px solid #e2e8f0;">
                            <td colspan="2" class="ps-3 py-3 fw-bold">Total DSI</td>
                            <td class="text-end pe-3 py-3 fw-800 text-info fs-6">Rp {{ number_format($reconciliation->dsi_amount, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Bottom Action Panel --}}
    <div class="col-12">
        <div class="card card-clean">
            <div class="card-header">Tindakan Rekonsiliasi</div>
            <div class="card-body p-4">
                @if($reconciliation->status === 'PENDING_REVIEW' || $reconciliation->status === 'DISPUTED')
                    <div class="row g-3">
                        <div class="col-md-4">
                            <form method="POST" action="{{ route('reconciliations.approve', $reconciliation) }}" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui rekonsiliasi ini? Final Invoice akan diterbitkan.')">
                                @csrf
                                <button type="submit" class="btn btn-success w-100 py-2">
                                    <i class="bi bi-check-circle me-1"></i> Setujui & Terbitkan Final Invoice
                                </button>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-warning w-100 py-2" data-bs-toggle="modal" data-bs-target="#disputeModal">
                                <i class="bi bi-exclamation-triangle me-1"></i> Tandai Dispute (Sengketa)
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-outline-danger w-100 py-2" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="bi bi-x-circle me-1"></i> Tolak Rekonsiliasi
                            </button>
                        </div>
                    </div>
                @else
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Status rekonsiliasi ini adalah <strong>{{ $reconciliation->status }}</strong>. Tindakan tidak lagi tersedia.
                        @if($reconciliation->finalInvoice)
                            <div class="mt-2">
                                <a href="{{ route('billing-invoices.show', $reconciliation->finalInvoice) }}" class="btn btn-sm btn-primary">Lihat Final Invoice: {{ $reconciliation->finalInvoice->invoice_no }}</a>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Dispute Modal --}}
<div class="modal fade" id="disputeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h6 class="modal-title">Tandai sebagai Dispute</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('reconciliations.dispute', $reconciliation) }}">
                @csrf
                <div class="modal-body">
                    <label class="form-label fw-bold">Alasan Dispute</label>
                    <textarea name="dispute_reason" class="form-control" rows="4" required placeholder="Jelaskan mengapa data ini perlu diperiksa lebih lanjut..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Kirim Dispute</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-danger"><h6 class="modal-title">Tolak Rekonsiliasi</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('reconciliations.reject', $reconciliation) }}">
                @csrf
                <div class="modal-body">
                    <label class="form-label fw-bold">Alasan Penolakan</label>
                    <textarea name="reject_reason" class="form-control" rows="4" required placeholder="Berikan alasan penolakan..."></textarea>
                    <div class="small text-muted mt-2">Menolak rekonsiliasi akan membuka kembali transaksi DSI terkait agar bisa direkonsiliasi ulang.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Ya, Tolak Rekonsiliasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
