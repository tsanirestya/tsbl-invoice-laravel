@extends('layouts.app')

@section('title', 'Memo Tagihan')
@section('page-title', 'Memo Tagihan')

@section('content')
<div class="page-hdr d-flex align-items-center justify-content-between mb-3">
    <div>
        <div class="page-title">Memo Tagihan</div>
        <div class="page-sub">Riwayat memo pengajuan pembayaran ke partner</div>
    </div>
    <a href="{{ route('payment-memos.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Buat Memo
    </a>
</div>

<div class="card table-card">
    <div class="card-body p-0">
        @if($memos->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-file-earmark-text fs-1 d-block mb-2"></i>
                Belum ada memo tagihan.
            </div>
        @else
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>No. Memo</th>
                        <th>Partner</th>
                        <th>Tgl Memo</th>
                        <th>Batas Bayar</th>
                        <th class="text-end">Total Outstanding</th>
                        <th class="text-center">Jml Invoice</th>
                        <th>Dibuat oleh</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($memos as $memo)
                    <tr>
                        <td>
                            <a href="{{ route('payment-memos.show', $memo) }}" class="fw-semibold text-decoration-none">
                                {{ $memo->memo_no }}
                            </a>
                        </td>
                        <td>{{ $memo->partner->nama_partner }}</td>
                        <td>{{ $memo->memo_date->format('d/m/Y') }}</td>
                        <td>
                            @if($memo->payment_deadline->isPast())
                                <span class="text-danger fw-semibold">{{ $memo->payment_deadline->format('d/m/Y') }}</span>
                            @else
                                {{ $memo->payment_deadline->format('d/m/Y') }}
                            @endif
                        </td>
                        <td class="text-end fw-semibold">
                            Rp {{ number_format($memo->totalOutstanding(), 0, ',', '.') }}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary">{{ $memo->memoInvoices->count() }}</span>
                        </td>
                        <td class="text-muted small">{{ $memo->creator->full_name ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('payment-memos.show', $memo) }}" class="btn btn-xs btn-outline-secondary btn-sm">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('payment-memos.pdf', $memo) }}" target="_blank" class="btn btn-xs btn-outline-primary btn-sm">
                                <i class="bi bi-file-pdf"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-3 py-2">
            {{ $memos->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
