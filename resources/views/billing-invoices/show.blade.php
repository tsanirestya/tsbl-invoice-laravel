@extends('layouts.app')
@section('title', 'Invoice — '.$billingInvoice->invoice_no)
@section('page-title', 'Billing Invoice')

@push('styles')
<style>
.detail-label{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8;margin-bottom:2px}
.detail-value{font-size:.88rem;color:#1e293b;font-weight:500}
.type-proforma{background:#eff6ff;color:#1d4ed8}
.type-final{background:#f0fdf4;color:#15803d}
.type-credit_note{background:#fff7ed;color:#c2410c}
.type-debit_note{background:#fef2f2;color:#991b1b}
.type-cancellation{background:#f1f5f9;color:#475569}
.badge-paid{background:#dcfce7!important;color:#166534!important}
.badge-partial{background:#fef3c7!important;color:#92400e!important}
.badge-overdue{background:#fee2e2!important;color:#991b1b!important}
.badge-unpaid{background:#f1f5f9!important;color:#475569!important}
.badge-void{background:#f1f5f9!important;color:#94a3b8!important}
.alloc-bar{height:6px;border-radius:10px;background:#e8edf5;overflow:hidden;margin-top:4px}
.alloc-bar .bar{height:100%;border-radius:10px;transition:width .4s ease}
</style>
@endpush

@section('content')
@php
    $inv=$billingInvoice;
    $typeCls='type-'.strtolower($inv->invoice_type);
    $typeLabel=match($inv->invoice_type){'PROFORMA'=>'Proforma','FINAL'=>'Final','CREDIT_NOTE'=>'Credit Note','DEBIT_NOTE'=>'Debit Note','CANCELLATION'=>'Cancellation',default=>$inv->invoice_type};
    $ps=$inv->payment_status;
    $psCls=match($ps){'PAID'=>'badge-paid','PARTIAL'=>'badge-partial','OVERDUE'=>'badge-overdue','VOID'=>'badge-void',default=>'badge-unpaid'};
    $paid=$inv->allocations->sum('amount_allocated');
    $remaining=max(0,$inv->grand_total-$paid);
    $pct=$inv->grand_total>0?min(100,round($paid/$inv->grand_total*100)):0;
@endphp

<div class="d-flex align-items-center gap-2 mb-3 page-hdr">
    <a href="{{ route('billing-invoices.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;"><i class="bi bi-arrow-left"></i></a>
    <div>
        <div class="page-title">{{ $inv->invoice_no }}</div>
        <div class="page-sub">Detail Billing Invoice</div>
    </div>
    <div class="ms-auto d-flex gap-2 align-items-center">
        <span class="badge {{ $typeCls }} px-3 py-2" style="font-size:.78rem;">{{ $typeLabel }}</span>
        <span class="badge {{ $psCls }} px-3 py-2" style="font-size:.78rem;">{{ $ps }}</span>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">

        {{-- Header Info --}}
        <div class="card card-clean mb-3">
            <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-info-circle" style="color:#3b82f6"></i>Informasi Invoice</div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-6 col-md-4"><div class="detail-label">Partner</div><div class="detail-value fw-bold">{{ $inv->partner?->name ?? '-' }}</div></div>
                    <div class="col-6 col-md-4"><div class="detail-label">Tanggal Invoice</div><div class="detail-value">{{ $inv->invoice_date?->format('d M Y') }}</div></div>
                    <div class="col-6 col-md-4"><div class="detail-label">Jatuh Tempo</div><div class="detail-value {{ $ps==='OVERDUE'?'text-danger fw-bold':'' }}">{{ $inv->due_date?->format('d M Y') ?? '-' }}</div></div>
                    @if($inv->parentInvoice)
                    <div class="col-6 col-md-4">
                        <div class="detail-label">Parent Invoice</div>
                        <div class="detail-value"><a href="{{ route('billing-invoices.show',$inv->parentInvoice) }}" style="color:#1e40af;text-decoration:none;">{{ $inv->parentInvoice->invoice_no }}</a></div>
                    </div>
                    @endif
                    @if($inv->replacesInvoice)
                    <div class="col-6 col-md-4">
                        <div class="detail-label">Mengganti Invoice</div>
                        <div class="detail-value"><a href="{{ route('billing-invoices.show',$inv->replacesInvoice) }}" style="color:#1e40af;text-decoration:none;">{{ $inv->replacesInvoice->invoice_no }}</a></div>
                    </div>
                    @endif
                    @if($inv->sent_at)
                    <div class="col-6 col-md-4"><div class="detail-label">Dikirim</div><div class="detail-value">{{ $inv->sent_at->format('d M Y, H:i') }}</div></div>
                    @endif
                    @if($inv->notes)
                    <div class="col-12"><div class="detail-label">Catatan</div><div class="detail-value" style="white-space:pre-wrap;">{{ $inv->notes }}</div></div>
                    @endif
                    @if($inv->lock_reason)
                    <div class="col-12"><div class="detail-label text-warning">Lock Reason</div><div class="detail-value text-warning">{{ $inv->lock_reason }}</div></div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Line Items --}}
        <div class="card card-clean mb-3">
            <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-list-ul" style="color:#8b5cf6"></i>Item Invoice</div>
            <div class="card-body p-0">
                <table class="table mb-0" style="font-size:.83rem;">
                    <thead><tr>
                        <th class="ps-4" style="background:#f8fafc;font-size:.65rem;text-transform:uppercase;color:#64748b;">Deskripsi</th>
                        <th class="text-center" style="background:#f8fafc;font-size:.65rem;text-transform:uppercase;color:#64748b;">Qty</th>
                        <th class="text-end" style="background:#f8fafc;font-size:.65rem;text-transform:uppercase;color:#64748b;">Harga Satuan</th>
                        <th class="text-end pe-4" style="background:#f8fafc;font-size:.65rem;text-transform:uppercase;color:#64748b;">Jumlah</th>
                    </tr></thead>
                    <tbody>
                        @foreach($inv->items as $item)
                        <tr>
                            <td class="ps-4">{{ $item->description }}</td>
                            <td class="text-center text-muted">{{ $item->quantity }}</td>
                            <td class="text-end text-muted">Rp {{ number_format($item->unit_price,0,',','.') }}</td>
                            <td class="text-end pe-4 fw-bold">Rp {{ number_format($item->amount,0,',','.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="border-top:2px solid #e2e8f0;">
                            <td colspan="3" class="text-end fw-bold ps-4" style="font-size:.88rem;padding-top:.8rem;padding-bottom:.8rem;">Grand Total</td>
                            <td class="text-end pe-4 fw-bold" style="font-size:1.05rem;color:#1e40af;padding-top:.8rem;padding-bottom:.8rem;">Rp {{ number_format($inv->grand_total,0,',','.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Payment Allocations --}}
        @if($inv->allocations->isNotEmpty())
        <div class="card card-clean">
            <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-cash-stack" style="color:#10b981"></i>Alokasi Pembayaran</div>
            <div class="card-body p-0">
                <table class="table mb-0" style="font-size:.83rem;">
                    <thead><tr>
                        <th class="ps-4" style="background:#f8fafc;font-size:.65rem;text-transform:uppercase;color:#64748b;">Ref Pembayaran</th>
                        <th style="background:#f8fafc;font-size:.65rem;text-transform:uppercase;color:#64748b;">Tanggal</th>
                        <th class="text-end pe-4" style="background:#f8fafc;font-size:.65rem;text-transform:uppercase;color:#64748b;">Jumlah Dialokasi</th>
                    </tr></thead>
                    <tbody>
                        @foreach($inv->allocations as $alloc)
                        <tr>
                            <td class="ps-4">{{ $alloc->payment?->reference_no ?? '#'.$alloc->payment_id }}</td>
                            <td style="color:#64748b;">{{ $alloc->created_at?->format('d/m/Y') }}</td>
                            <td class="text-end pe-4 fw-bold text-success">Rp {{ number_format($alloc->amount_allocated,0,',','.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        {{-- Payment Progress --}}
        <div class="card card-clean mb-3">
            <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-pie-chart-fill" style="color:#10b981"></i>Status Pembayaran</div>
            <div class="card-body p-4">
                <div class="d-flex justify-content-between mb-1" style="font-size:.78rem;font-weight:600;">
                    <span>Terbayar</span><span>{{ $pct }}%</span>
                </div>
                <div class="alloc-bar"><div class="bar {{ $pct>=100?'bg-success':($pct>0?'bg-warning':'') }}" style="width:{{ $pct }}%"></div></div>
                <div class="row g-2 mt-3">
                    <div class="col-6"><div class="detail-label">Total Tagihan</div><div class="detail-value fw-bold">Rp {{ number_format($inv->grand_total,0,',','.') }}</div></div>
                    <div class="col-6"><div class="detail-label">Sudah Dibayar</div><div class="detail-value fw-bold text-success">Rp {{ number_format($paid,0,',','.') }}</div></div>
                    <div class="col-12"><div class="detail-label">Sisa</div><div class="detail-value fw-bold {{ $remaining>0?'text-danger':'' }}">Rp {{ number_format($remaining,0,',','.') }}</div></div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="card card-clean mb-3">
            <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-lightning-charge-fill" style="color:#f59e0b"></i>Tindakan</div>
            <div class="card-body d-grid gap-2 p-3">
                <a href="{{ route('billing-invoices.download',$inv) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Download PDF</a>

                @if(auth()->user()->isAdmin() || auth()->user()->user_status==='FINANCE')
                    @if($ps !== 'VOID' && !$inv->sent_at)
                    <form method="POST" action="{{ route('billing-invoices.send',$inv) }}">@csrf
                        <button type="submit" class="btn btn-info btn-sm text-white w-100"><i class="bi bi-send me-1"></i>Kirim Invoice</button>
                    </form>
                    @endif

                    @if($ps !== 'VOID' && !$inv->void_proposed_at)
                    <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalVoid"><i class="bi bi-slash-circle me-1"></i>Propose Void</button>
                    @endif

                    @if($inv->void_proposed_at && !($ps==='VOID'))
                    <form method="POST" action="{{ route('billing-invoices.cancel-void',$inv) }}">@csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm w-100"><i class="bi bi-x me-1"></i>Cancel Void Proposal</button>
                    </form>
                    @endif
                @endif

                @if(auth()->user()->isAdmin() && $inv->void_proposed_at && $ps!=='VOID')
                <form method="POST" action="{{ route('billing-invoices.approve-void',$inv) }}" onsubmit="return confirm('Approve void untuk invoice ini?')">@csrf
                    <button type="submit" class="btn btn-danger btn-sm w-100"><i class="bi bi-check-circle me-1"></i>Approve Void</button>
                </form>
                @endif
            </div>
        </div>

        {{-- Void Status --}}
        @if($inv->void_proposed_at)
        <div class="card card-clean" style="border:1px solid #fde68a;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-2"><i class="bi bi-exclamation-triangle-fill text-warning"></i><span class="fw-bold" style="font-size:.82rem;">Void Proposed</span></div>
                <div class="detail-label">Diajukan</div>
                <div class="detail-value mb-2">{{ $inv->void_proposed_at?->format('d M Y, H:i') }}</div>
                @if($inv->void_reason)
                <div class="detail-label">Alasan</div>
                <div class="detail-value" style="white-space:pre-wrap;">{{ $inv->void_reason }}</div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Modal Void --}}
<div class="modal fade" id="modalVoid" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:12px;border:none;">
            <div class="modal-header" style="border-bottom:1px solid #f1f5f9;">
                <h6 class="modal-title fw-bold text-warning"><i class="bi bi-slash-circle me-2"></i>Propose Void Invoice</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('billing-invoices.void',$inv) }}">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-modern mb-3" style="background:#fffbeb;border-left:4px solid #f59e0b;color:#92400e;"><i class="bi bi-info-circle me-2"></i>Void proposal memerlukan approval dari Admin/Senior Finance.</div>
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Alasan Void <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" required placeholder="Tuliskan alasan..."></textarea>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-slash-circle me-1"></i>Submit Proposal</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
