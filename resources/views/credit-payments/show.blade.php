@extends('layouts.app')

@section('title', 'Batch ' . $creditPayment->batch_no)
@section('page-title', 'Detail Pembayaran — ' . $creditPayment->batch_no)

@section('content')
<div class="d-flex gap-2 mb-3">
    <a href="{{ route('credit-payments.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show py-2">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($creditPayment->is_voided)
    <div class="alert alert-danger d-flex align-items-center gap-2 py-2">
        <i class="bi bi-x-octagon-fill fs-5"></i>
        <div>
            <strong>BATCH DIBATALKAN</strong>
            pada {{ $creditPayment->voided_at?->format('d/m/Y H:i') }}
            oleh {{ $creditPayment->voidedByUser?->name ?? '—' }}.
            Semua alokasi invoice telah di-rollback.
        </div>
    </div>
@endif

<div class="row g-3">

    {{-- Header Card --}}
    <div class="col-12">
        <div class="card card-clean">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span class="fw-bold fs-6">{{ $creditPayment->batch_no }}</span>
                @if($creditPayment->is_voided)
                    <span class="badge bg-danger">DIBATALKAN</span>
                @elseif($creditPayment->excess_to_deposit > 0)
                    <span class="badge bg-warning text-dark">PARTIAL → ADA SISA KE DEPOSIT</span>
                @else
                    <span class="badge bg-success">FULL ALLOCATED</span>
                @endif
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6 col-lg-3">
                        <small class="text-muted d-block">Partner</small>
                        <span class="fw-semibold">{{ $creditPayment->partner->nama_partner ?? '—' }}</span>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <small class="text-muted d-block">Tanggal Bayar</small>
                        <span class="fw-semibold">{{ $creditPayment->payment_date->format('d/m/Y') }}</span>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <small class="text-muted d-block">Metode</small>
                        <span>{{ $creditPayment->payment_method }}</span>
                        @if($creditPayment->reference_no)
                            <span class="text-muted small"> — {{ $creditPayment->reference_no }}</span>
                        @endif
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <small class="text-muted d-block">Dibuat Oleh</small>
                        <span class="small">{{ $creditPayment->creator?->name ?? '—' }}</span>
                    </div>
                </div>

                <hr class="my-3">

                <div class="row g-3">
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Total Diterima</small>
                        <span class="fs-5 fw-bold text-primary">
                            Rp {{ number_format($creditPayment->total_received, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Total Dialokasikan</small>
                        <span class="fs-5 fw-bold text-success">
                            Rp {{ number_format($creditPayment->total_allocated, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Sisa → Deposit</small>
                        <span class="fs-5 fw-bold {{ $creditPayment->excess_to_deposit > 0 ? 'text-warning' : 'text-muted' }}">
                            Rp {{ number_format($creditPayment->excess_to_deposit, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                @if($creditPayment->notes)
                    <div class="mt-3">
                        <small class="text-muted d-block">Catatan</small>
                        <span class="small">{{ $creditPayment->notes }}</span>
                    </div>
                @endif

                @if($creditPayment->proof_file)
                    <div class="mt-3">
                        <a href="{{ asset('storage/' . $creditPayment->proof_file) }}"
                           target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-file-earmark-image me-1"></i> Lihat Bukti Transfer
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Alokasi Invoice --}}
    <div class="col-12">
        <div class="card card-clean">
            <div class="card-header">Alokasi Invoice</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>No. Invoice</th>
                                <th>Tgl Invoice</th>
                                <th>Jatuh Tempo</th>
                                <th class="text-end">Grand Total</th>
                                <th class="text-end">Alokasi Batch Ini</th>
                                <th class="text-center">Status Invoice</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($creditPayment->invoicePayments as $pmt)
                                @php $inv = $pmt->invoice; @endphp
                                <tr>
                                    <td class="fw-semibold">
                                        @if($inv)
                                            <a href="{{ route('invoices.show', $inv) }}" class="text-decoration-none">
                                                {{ $inv->invoice_no }}
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="small">{{ $inv?->invoice_date?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="small">{{ $inv?->due_date?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="text-end">
                                        Rp {{ number_format($inv?->grand_total ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end fw-semibold text-success">
                                        Rp {{ number_format($pmt->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        @php $st = $inv?->payment_status ?? '—'; @endphp
                                        @if($st === 'PAID')
                                            <span class="badge badge-paid"><i class="bi bi-check-circle-fill me-1"></i>PAID</span>
                                        @elseif($st === 'PARTIAL')
                                            <span class="badge badge-partial">PARTIAL</span>
                                        @elseif($st === 'OVERDUE')
                                            <span class="badge badge-overdue">OVERDUE</span>
                                        @elseif($st === 'UNPAID')
                                            <span class="badge badge-unpaid">UNPAID</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $st }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Tidak ada alokasi invoice pada batch ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($creditPayment->invoicePayments->isNotEmpty())
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="4" class="text-end">Total Dialokasikan</td>
                                <td class="text-end text-success">
                                    Rp {{ number_format($creditPayment->total_allocated, 0, ',', '.') }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Deposit Info --}}
    @if($creditPayment->excess_to_deposit > 0)
    <div class="col-12">
        <div class="card card-clean border-warning">
            <div class="card-body d-flex align-items-center gap-3 flex-wrap">
                <div>
                    <i class="bi bi-piggy-bank-fill text-warning fs-4"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold">
                        Sisa Rp {{ number_format($creditPayment->excess_to_deposit, 0, ',', '.') }}
                        → Masuk ke Deposit Partner
                    </div>
                    @if($creditPayment->is_voided)
                        <small class="text-muted">Deposit ini sudah di-reverse saat batch dibatalkan.</small>
                    @else
                        <small class="text-muted">Deposit TOPUP otomatis telah dicatat.</small>
                    @endif
                </div>
                @if(!$creditPayment->is_voided && $creditPayment->partner)
                    <a href="{{ route('deposits.index', $creditPayment->partner) }}"
                       class="btn btn-sm btn-outline-warning">
                        <i class="bi bi-eye me-1"></i> Lihat Riwayat Deposit
                    </a>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Void Action --}}
    @if(!$creditPayment->is_voided)
    <div class="col-12">
        <div class="card card-clean border-danger">
            <div class="card-body d-flex align-items-center gap-3 flex-wrap">
                <div class="flex-grow-1">
                    <div class="fw-semibold text-danger">Void / Batalkan Batch</div>
                    <small class="text-muted">
                        Semua alokasi invoice akan di-rollback (kembali ke status sebelumnya).
                        Jika ada sisa yang masuk deposit, deposit tersebut juga akan dihapus.
                    </small>
                </div>
                <button type="button" class="btn btn-sm btn-danger" id="btn-void">
                    <i class="bi bi-x-octagon me-1"></i> Batalkan Batch
                </button>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('credit-payments.destroy', $creditPayment) }}"
          id="void-form" class="d-none">
        @csrf
        @method('DELETE')
    </form>
    @endif

</div>
@endsection

@push('scripts')
<script>
document.getElementById('btn-void')?.addEventListener('click', function () {
    if (confirm('Yakin ingin membatalkan batch {{ $creditPayment->batch_no }}?\n\nSemua alokasi invoice akan di-rollback dan tidak bisa dikembalikan.')) {
        document.getElementById('void-form').submit();
    }
});
</script>
@endpush
