@extends('layouts.app')

@section('title', 'Pembayaran Credit')
@section('page-title', 'Pembayaran Credit (Batch)')

@section('content')
<div class="d-flex gap-2 mb-3 flex-wrap align-items-center justify-content-between">
    <div></div>
    <a href="{{ route('credit-payments.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Terima Pembayaran Credit
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

<div class="card card-clean">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>No. Batch</th>
                        <th>Partner</th>
                        <th>Tgl Bayar</th>
                        <th class="text-end">Total Diterima</th>
                        <th class="text-end">Dialokasikan</th>
                        <th class="text-center">Invoice</th>
                        <th class="text-center">Sisa→Deposit</th>
                        <th class="text-center">Status</th>
                        <th>Dibuat Oleh</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                    <tr>
                        <td class="fw-semibold">
                            <a href="{{ route('credit-payments.show', $batch) }}" class="text-decoration-none">
                                {{ $batch->batch_no }}
                            </a>
                        </td>
                        <td>{{ $batch->partner->nama_partner ?? '-' }}</td>
                        <td>{{ $batch->payment_date->format('d/m/Y') }}</td>
                        <td class="text-end">Rp {{ number_format($batch->total_received, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($batch->total_allocated, 0, ',', '.') }}</td>
                        <td class="text-center">
                            <span class="badge bg-secondary">{{ $batch->invoice_payments_count }}</span>
                        </td>
                        <td class="text-center">
                            @if($batch->excess_to_deposit > 0)
                                <span class="badge bg-warning text-dark">
                                    Rp {{ number_format($batch->excess_to_deposit, 0, ',', '.') }}
                                </span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($batch->is_voided)
                                <span class="badge bg-danger">DIBATALKAN</span>
                            @elseif($batch->isVoidPending())
                                <span class="badge bg-info">PENDING VOID</span>
                            @elseif($batch->excess_to_deposit > 0)
                                <span class="badge bg-warning text-dark">PARTIAL</span>
                            @else
                                <span class="badge bg-success">FULL</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $batch->creator->name ?? '-' }}</td>
                        <td>
                            <a href="{{ route('credit-payments.show', $batch) }}"
                               class="btn btn-sm btn-outline-secondary py-0">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Belum ada batch pembayaran credit.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($batches->hasPages())
    <div class="mt-3">
        {{ $batches->links() }}
    </div>
@endif
@endsection
