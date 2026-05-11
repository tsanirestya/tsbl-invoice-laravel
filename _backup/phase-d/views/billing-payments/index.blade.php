@extends('layouts.app')
@section('title', 'Pembayaran Billing')
@section('page-title', 'Billing Payments')

@push('styles')
<style>
    .pay-wrap { background:#fff; border-radius:11px; box-shadow:0 1px 3px rgba(15,23,41,.06); overflow:hidden; }
    .pay-table-hdr { padding:.8rem 1.1rem; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; }
    .pay-wrap table thead th { background:#f8fafc; font-size:.65rem; font-weight:700; letter-spacing:.55px; text-transform:uppercase; color:#64748b; border-bottom:1px solid #f1f5f9; padding:.62rem 1rem; white-space:nowrap; }
    .pay-wrap table tbody td { padding:.65rem 1rem; font-size:.83rem; border-bottom:1px solid #f8fafc; vertical-align:middle; }
    .pay-wrap table tbody tr:last-child td { border-bottom:none; }
    .pay-wrap table tbody tr:hover { background:#fafbff; }

    .ps-pending  { background:#fff7ed; color:#c2410c; }
    .ps-verified { background:#f0fdf4; color:#166534; }
    .ps-rejected { background:#fef2f2; color:#991b1b; }
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 page-hdr">
    <div>
        <div class="page-title">Pembayaran Billing</div>
        <div class="page-sub">Kelola pencatatan dan verifikasi pembayaran</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('billing-payments.credit-balance') }}" class="btn btn-outline-primary btn-sm" style="border-radius:9px;">
            <i class="bi bi-wallet2 me-1"></i> Credit Balance
        </a>
        <button class="btn btn-primary btn-sm d-flex align-items-center gap-1" style="border-radius:9px;" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
            <i class="bi bi-plus-lg"></i>
            <span>Catat Pembayaran</span>
        </button>
    </div>
</div>

<div class="filter-panel mb-3" style="background:#fff; border-radius:11px; padding:.9rem 1.1rem; box-shadow:0 1px 3px rgba(15,23,41,.06);">
    <form method="GET" action="{{ route('billing-payments.index') }}" id="pay-filter-form">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="filter-label small fw-bold text-muted text-uppercase mb-1 d-block">Cari</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Ref No / No Invoice" value="{{ request('search') }}" style="border-radius:8px;">
            </div>
            <div class="col-md-3">
                <label class="filter-label small fw-bold text-muted text-uppercase mb-1 d-block">Partner</label>
                <select name="partner_id" class="form-select form-select-sm" style="border-radius:8px;">
                    <option value="">Semua</option>
                    @foreach($partners as $p)
                        <option value="{{ $p->id }}" @selected(request('partner_id') == $p->id)>{{ $p->nama_partner }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="filter-label small fw-bold text-muted text-uppercase mb-1 d-block">Dari</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" style="border-radius:8px;">
            </div>
            <div class="col-md-2">
                <label class="filter-label small fw-bold text-muted text-uppercase mb-1 d-block">Sampai</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" style="border-radius:8px;">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm" style="border-radius:8px;"><i class="bi bi-search"></i></button>
            </div>
        </div>
    </form>
</div>

<div class="pay-wrap">
    <div class="pay-table-hdr">
        <span class="fw-semibold" style="font-size:.86rem;">Semua Pembayaran</span>
        <span style="font-size:.73rem;color:#94a3b8;">{{ $payments->total() }} data</span>
    </div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Ref No</th>
                    <th>Invoice</th>
                    <th>Partner</th>
                    <th>Method</th>
                    <th class="text-end">Amount</th>
                    <th class="text-center">Status</th>
                    <th>Date</th>
                    <th class="text-center pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $pay)
                @php $sCls = 'ps-' . strtolower($pay->status); @endphp
                <tr>
                    <td class="ps-4 fw-bold">{{ $pay->reference_no ?? 'N/A' }}</td>
                    <td>
                        @if($pay->invoice)
                            <a href="{{ route('billing-invoices.show', $pay->invoice) }}" class="text-decoration-none small">{{ $pay->invoice->invoice_no }}</a>
                        @else
                            <span class="text-muted small">Bulk/Credit</span>
                        @endif
                    </td>
                    <td style="max-width:140px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $pay->invoice?->partner?->name ?? 'N/A' }}</td>
                    <td>{{ $pay->payment_method }}</td>
                    <td class="text-end fw-bold">Rp {{ number_format($pay->amount, 0, ',', '.') }}</td>
                    <td class="text-center"><span class="badge {{ $sCls }}">{{ $pay->status }}</span></td>
                    <td>{{ $pay->payment_date?->format('d/m/Y') }}</td>
                    <td class="text-center pe-3">
                        <a href="{{ route('billing-payments.show', $pay) }}" class="btn btn-sm btn-outline-primary" style="border-radius:7px;"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($payments->hasPages())
    <div class="px-4 py-3 border-top">
        {{ $payments->links() }}
    </div>
    @endif
</div>

{{-- Record Payment Modal --}}
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:12px; border:none;">
            <div class="modal-header border-0"><h6 class="modal-title fw-bold">Catat Pembayaran Baru</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('billing-payments.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pilih Invoice</label>
                        <select name="invoice_id" class="form-select ts-select" required>
                            <option value="">-- Pilih Invoice --</option>
                            {{-- This should ideally be loaded via AJAX for better UX, but for MVP we load some or use a text search --}}
                            @php $unpaidInvoices = \App\Models\Invoice::whereIn('payment_status', ['UNPAID','PARTIAL','OVERDUE'])->with('partner')->orderBy('invoice_no')->get(); @endphp
                            @foreach($unpaidInvoices as $inv)
                                <option value="{{ $inv->id }}">{{ $inv->invoice_no }} - {{ $inv->partner?->name }} (Sisa: Rp {{ number_format($inv->grand_total - $inv->allocations->sum('amount_allocated'), 0, ',', '.') }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Jumlah Bayar</label>
                            <input type="text" name="amount" class="form-control currency-input" required placeholder="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Tanggal Bayar</label>
                            <input type="date" name="payment_date" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Metode</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="TRANSFER">Transfer Bank</option>
                                <option value="CASH">Cash</option>
                                <option value="CREDIT_BALANCE">Credit Balance</option>
                                <option value="OTHER">Lainnya</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">No Referensi</label>
                            <input type="text" name="reference_no" class="form-control" placeholder="TX-XXX-XXX">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Bukti Pembayaran (Image/PDF)</label>
                        <input type="file" name="proof_file" class="form-control form-control-sm">
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Catatan</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Informasi tambahan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Simpan Pembayaran</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if(window.TomSelect) {
            document.querySelectorAll('.ts-select').forEach(el => new TomSelect(el));
        }
    });
</script>
@endpush
