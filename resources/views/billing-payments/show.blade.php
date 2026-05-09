@extends('layouts.app')
@section('title', 'Payment Detail — '.$billingPayment->reference_no)
@section('page-title', 'Payment Detail')

@push('styles')
<style>
    .pay-detail-card { background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(15,23,41,.07); overflow:hidden; border:1px solid #f1f5f9; }
    .pay-detail-hdr { padding:.8rem 1.1rem; border-bottom:1px solid #f1f5f9; background:#f8fafc; font-weight:700; color:#475569; }
    .pay-detail-body { padding:1.5rem; }
    .label-grid { font-size:.65rem; font-weight:700; text-transform:uppercase; color:#94a3b8; margin-bottom:2px; }
    .value-grid { font-size:.9rem; font-weight:600; color:#1e293b; margin-bottom:1rem; }

    .alloc-list { background:#f8fafc; border-radius:8px; padding:1rem; border:1px solid #e2e8f0; }
    .alloc-item { display:flex; justify-content:space-between; padding:.5rem 0; border-bottom:1px solid #e2e8f0; }
    .alloc-item:last-child { border-bottom:none; }

    .status-pill { display:inline-block; padding:4px 12px; border-radius:20px; font-size:.75rem; font-weight:700; }
    .ps-pending  { background:#fff7ed; color:#c2410c; }
    .ps-verified { background:#f0fdf4; color:#166534; }
    .ps-rejected { background:#fef2f2; color:#991b1b; }
</style>
@endpush

@section('content')

<div class="d-flex align-items-center gap-2 mb-3 page-hdr">
    <a href="{{ route('billing-payments.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;"><i class="bi bi-arrow-left"></i></a>
    <div>
        <div class="page-title">Pembayaran {{ $billingPayment->reference_no }}</div>
        <div class="page-sub">Detail data pembayaran dan alokasi</div>
    </div>
    <div class="ms-auto">
        <span class="status-pill ps-{{ strtolower($billingPayment->status) }}">{{ $billingPayment->status }}</span>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="pay-detail-card mb-4">
            <div class="pay-detail-hdr"><i class="bi bi-info-circle me-1"></i>Informasi Pembayaran</div>
            <div class="pay-detail-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="label-grid">Partner</div>
                        <div class="value-grid">{{ $billingPayment->invoice?->partner?->name ?? 'N/A' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="label-grid">Invoice (Pencatatan Awal)</div>
                        <div class="value-grid">
                            @if($billingPayment->invoice)
                                <a href="{{ route('billing-invoices.show', $billingPayment->invoice) }}">{{ $billingPayment->invoice->invoice_no }}</a>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="label-grid">Jumlah Pembayaran</div>
                        <div class="value-grid fs-5 fw-800 text-primary">Rp {{ number_format($billingPayment->amount, 0, ',', '.') }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="label-grid">Tanggal Pembayaran</div>
                        <div class="value-grid">{{ $billingPayment->payment_date?->format('d M Y') }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="label-grid">Metode</div>
                        <div class="value-grid">{{ $billingPayment->payment_method }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="label-grid">Referensi</div>
                        <div class="value-grid">{{ $billingPayment->reference_no ?? '-' }}</div>
                    </div>
                    @if($billingPayment->proof_file)
                    <div class="col-12 mb-3">
                        <div class="label-grid">Bukti Pembayaran</div>
                        <div class="mt-2">
                            <a href="{{ asset('storage/'.$billingPayment->proof_file) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-file-earmark-image me-1"></i> Lihat Bukti Pembayaran
                            </a>
                        </div>
                    </div>
                    @endif
                    @if($billingPayment->notes)
                    <div class="col-12">
                        <div class="label-grid">Catatan</div>
                        <div class="value-grid small fw-normal">{{ $billingPayment->notes }}</div>
                    </div>
                    @endif
                    @if($billingPayment->reject_reason)
                    <div class="col-12">
                        <div class="label-grid text-danger">Alasan Penolakan</div>
                        <div class="value-grid text-danger small">{{ $billingPayment->reject_reason }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="pay-detail-card">
            <div class="pay-detail-hdr"><i class="bi bi-diagram-3 me-1"></i>Riwayat Alokasi Tagihan</div>
            <div class="pay-detail-body p-0">
                <table class="table mb-0 small">
                    <thead>
                        <tr class="bg-light">
                            <th class="ps-4">No Invoice</th>
                            <th class="text-end">Jumlah Alokasi</th>
                            <th>Tanggal Alokasi</th>
                            <th class="pe-4">Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($billingPayment->allocations as $alloc)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('billing-invoices.show', $alloc->invoice) }}" class="fw-bold">{{ $alloc->invoice?->invoice_no }}</a>
                            </td>
                            <td class="text-end fw-bold text-success">Rp {{ number_format($alloc->amount_allocated, 0, ',', '.') }}</td>
                            <td>{{ $alloc->created_at->format('d/m/Y H:i') }}</td>
                            <td class="pe-4">{{ $alloc->allocatedBy?->full_name ?? 'System' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada alokasi tagihan</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        {{-- Allocation Status Card --}}
        <div class="card card-clean mb-4 shadow-sm border-0">
            <div class="card-header bg-white border-bottom fw-bold"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Status Alokasi Dana</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="label-grid">Total Dana</div>
                        <div class="fw-bold fs-6">Rp {{ number_format($billingPayment->amount, 0, ',', '.') }}</div>
                    </div>
                    <div class="col-6">
                        <div class="label-grid text-success">Sudah Dialokasi</div>
                        <div class="fw-bold fs-6 text-success">Rp {{ number_format($billingPayment->amount_allocated, 0, ',', '.') }}</div>
                    </div>
                    <div class="col-12 pt-2 border-top">
                        <div class="label-grid text-warning">Sisa Belum Dialokasi</div>
                        <div class="fw-bold fs-4 text-warning">Rp {{ number_format($billingPayment->amount_unallocated, 0, ',', '.') }}</div>
                    </div>
                </div>

                @if($billingPayment->amount_unallocated > 0 && $billingPayment->status === 'VERIFIED')
                    <div class="mt-4">
                        <button class="btn btn-primary w-100 py-2" data-bs-toggle="modal" data-bs-target="#allocateModal">
                            <i class="bi bi-plus-circle me-1"></i> Alokasikan Sisa Dana
                        </button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Verification Actions --}}
        @if($billingPayment->status === 'PENDING' && (auth()->user()->isAdmin() || auth()->user()->user_status === 'FINANCE'))
        <div class="card card-clean shadow-sm border-0">
            <div class="card-header bg-white border-bottom fw-bold"><i class="bi bi-shield-check me-2 text-warning"></i>Verifikasi Pembayaran</div>
            <div class="card-body d-grid gap-2">
                <form method="POST" action="{{ route('billing-payments.verify', $billingPayment) }}" onsubmit="return confirm('Apakah Anda yakin ingin memverifikasi pembayaran ini?')">
                    @csrf
                    <button type="submit" class="btn btn-success w-100 py-2">
                        <i class="bi bi-check-circle me-1"></i> Verifikasi (Setujui)
                    </button>
                </form>
                <button class="btn btn-outline-danger w-100 py-2" data-bs-toggle="modal" data-bs-target="#rejectPaymentModal">
                    <i class="bi bi-x-circle me-1"></i> Tolak Pembayaran
                </button>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Allocation Modal --}}
@if($billingPayment->amount_unallocated > 0)
<div class="modal fade" id="allocateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0" style="border-radius:12px;">
            <div class="modal-header border-0"><h6 class="modal-title fw-bold">Alokasikan Dana Pembayaran</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('billing-payments.allocate', $billingPayment) }}">
                @csrf
                <div class="modal-body p-4 pt-0">
                    <p class="small text-muted mb-4">Dana sebesar <strong>Rp {{ number_format($billingPayment->amount_unallocated, 0, ',', '.') }}</strong> akan dialokasikan ke invoice terpilih.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pilih Invoice Target</label>
                        <select name="invoice_id" class="form-select ts-select" required>
                            <option value="">-- Pilih Invoice --</option>
                            @php
                                $partnerId = $billingPayment->invoice?->partner_id;
                                $targetInvoices = \App\Models\Invoice::where('partner_id', $partnerId)
                                    ->whereIn('payment_status', ['UNPAID','PARTIAL','OVERDUE'])
                                    ->with('allocations')
                                    ->get();
                            @endphp
                            @foreach($targetInvoices as $inv)
                                @php $rem = $inv->grand_total - $inv->allocations->sum('amount_allocated'); @endphp
                                <option value="{{ $inv->id }}">{{ $inv->invoice_no }} (Sisa: Rp {{ number_format($rem, 0, ',', '.') }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Catatan Alokasi</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Contoh: Alokasi sisa pembayaran bulan lalu..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Proses Alokasi</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Reject Modal --}}
<div class="modal fade" id="rejectPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0" style="border-radius:12px;">
            <div class="modal-header border-0 text-danger"><h6 class="modal-title fw-bold">Tolak Pembayaran</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('billing-payments.reject', $billingPayment) }}">
                @csrf
                <div class="modal-body p-4 pt-0">
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Alasan Penolakan</label>
                        <textarea name="reject_reason" class="form-control" rows="3" required placeholder="Contoh: Bukti transfer tidak valid, jumlah tidak sesuai..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4">Tolak Sekarang</button>
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
