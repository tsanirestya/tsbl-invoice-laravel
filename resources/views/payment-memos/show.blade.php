@extends('layouts.app')

@section('title', $paymentMemo->memo_no)
@section('page-title', 'Detail Memo Tagihan')

@section('content')
<div class="d-flex gap-2 mb-3">
    <a href="{{ route('payment-memos.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
    <a href="{{ route('payment-memos.pdf', $paymentMemo) }}" target="_blank" class="btn btn-sm btn-primary">
        <i class="bi bi-file-pdf me-1"></i> Download PDF
    </a>
    <form method="POST" action="{{ route('payment-memos.destroy', $paymentMemo) }}"
          onsubmit="return confirm('Hapus memo {{ $paymentMemo->memo_no }}? Tindakan ini tidak mempengaruhi status invoice.')"
          class="ms-auto">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-trash me-1"></i> Hapus Memo
        </button>
    </form>
</div>

<div class="row g-3">
    {{-- Header info --}}
    <div class="col-lg-5">
        <div class="card card-clean">
            <div class="card-header">Informasi Memo</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">No. Memo</dt>
                    <dd class="col-7 fw-bold">{{ $paymentMemo->memo_no }}</dd>

                    <dt class="col-5 text-muted">Partner</dt>
                    <dd class="col-7">
                        <a href="{{ route('partners.show', $paymentMemo->partner) }}" class="text-decoration-none fw-semibold">
                            {{ $paymentMemo->partner->nama_partner }}
                        </a>
                    </dd>

                    <dt class="col-5 text-muted">Tanggal Memo</dt>
                    <dd class="col-7">{{ $paymentMemo->memo_date->format('d M Y') }}</dd>

                    <dt class="col-5 text-muted">Batas Bayar</dt>
                    <dd class="col-7">
                        @if($paymentMemo->payment_deadline->isPast())
                            <span class="text-danger fw-semibold">{{ $paymentMemo->payment_deadline->format('d M Y') }} <span class="badge badge-overdue">Lewat</span></span>
                        @else
                            <span class="text-success fw-semibold">{{ $paymentMemo->payment_deadline->format('d M Y') }}</span>
                        @endif
                    </dd>

                    <dt class="col-5 text-muted">Dibuat oleh</dt>
                    <dd class="col-7">{{ $paymentMemo->creator->full_name ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Total Tagihan (Memo)</dt>
                    <dd class="col-7 fw-bold">Rp {{ number_format($paymentMemo->totalOutstanding(), 0, ',', '.') }}</dd>

                    <dt class="col-5 text-muted">Total Bayar (S/D Hari Ini)</dt>
                    <dd class="col-7 text-success fw-semibold">Rp {{ number_format($paymentMemo->totalPaidToDate(), 0, ',', '.') }}</dd>

                    <dt class="col-5 text-muted">Sisa Tagihan Sekarang</dt>
                    <dd class="col-7 fw-bold text-primary" style="font-size: 1.1rem">Rp {{ number_format($paymentMemo->totalCurrentBalance(), 0, ',', '.') }}</dd>

                    @if($paymentMemo->notes)
                    <dt class="col-5 text-muted">Catatan</dt>
                    <dd class="col-7">{{ $paymentMemo->notes }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    {{-- Invoice list --}}
    <div class="col-lg-7">
        <div class="card card-clean">
            <div class="card-header">Invoice dalam Memo ({{ $paymentMemo->memoInvoices->count() }})</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>No. Invoice</th>
                                <th class="text-end">Tagihan Memo</th>
                                <th class="text-end">Terbayar</th>
                                <th class="text-end">Sisa Sekarang</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($paymentMemo->memoInvoices as $mi)
                            @php $inv = $mi->invoice; @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('invoices.show', $inv) }}" class="fw-semibold text-decoration-none">
                                        {{ $inv->invoice_no }}
                                    </a>
                                    <div class="small text-muted">{{ $inv->due_date?->format('d/m/Y') ?? '—' }}</div>
                                </td>
                                <td class="text-end text-muted">
                                    {{ number_format($mi->sisa_tagihan, 0, ',', '.') }}
                                </td>
                                <td class="text-end text-success">
                                    {{ number_format($mi->currentPaid(), 0, ',', '.') }}
                                </td>
                                <td class="text-end fw-bold {{ $mi->currentBalance() > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($mi->currentBalance(), 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    @php $st = $inv->payment_status; @endphp
                                    @if($st === 'PAID')
                                        <span class="badge badge-paid"><i class="bi bi-check-circle"></i></span>
                                    @elseif($st === 'OVERDUE')
                                        <span class="badge badge-overdue">O</span>
                                    @elseif($st === 'PARTIAL')
                                        <span class="badge badge-partial">P</span>
                                    @else
                                        <span class="badge badge-unpaid">U</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-light fw-bold">
                                <td class="text-end">Total</td>
                                <td class="text-end text-muted">{{ number_format($paymentMemo->totalOutstanding(), 0, ',', '.') }}</td>
                                <td class="text-end text-success">{{ number_format($paymentMemo->totalPaidToDate(), 0, ',', '.') }}</td>
                                <td class="text-end text-danger">{{ number_format($paymentMemo->totalCurrentBalance(), 0, ',', '.') }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
