@extends('layouts.app')

@section('title', 'Review Komisi')
@section('page-title', 'Review Komisi Di-hold')

@section('content')
<div class="page-hdr d-flex align-items-center justify-content-between mb-3">
    <div>
        <div class="page-title">Review Komisi Di-hold</div>
        <div class="page-sub">Komisi yang ditahan akibat fraud risk partner</div>
    </div>
    <a href="{{ route('anomalies.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Anomali
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="card table-card">
    <div class="card-body p-0">
        @if($held->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-check-circle fs-1 d-block mb-2"></i>
                Tidak ada komisi yang di-hold saat ini.
            </div>
        @else
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Reservasi</th>
                        <th>Partner</th>
                        <th>Tgl Reservasi</th>
                        <th>Metode</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">Komisi</th>
                        <th>Alasan Hold</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($held as $pay)
                    <tr>
                        <td>
                            <a href="{{ route('reservations.show', $pay->reservation) }}" class="fw-semibold text-decoration-none small">
                                {{ $pay->reservation?->reservation_no }}
                            </a>
                        </td>
                        <td>{{ $pay->reservation?->partner?->nama_partner ?? '—' }}</td>
                        <td class="small">{{ $pay->created_at->format('d/m/Y') }}</td>
                        <td><small>{{ str_replace('_', ' ', $pay->payment_method) }}</small></td>
                        <td class="text-end">Rp {{ number_format($pay->gross_amount, 0, ',', '.') }}</td>
                        <td class="text-end fw-semibold text-danger">Rp {{ number_format($pay->commission_amount, 0, ',', '.') }}</td>
                        <td><small class="text-muted">{{ $pay->commission_hold_reason }}</small></td>
                        <td class="text-center">
                            @if(auth()->user()->status === 'ADMIN')
                            <div class="d-flex gap-1 justify-content-center">
                                <form method="POST" action="{{ route('commission-review.release', $pay) }}"
                                      onsubmit="return confirm('Release komisi ini? Komisi akan dibayarkan ke partner.')">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-outline-success">
                                        <i class="bi bi-check-lg"></i> Release
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('commission-review.revoke', $pay) }}"
                                      onsubmit="return confirm('Revoke komisi ini? Komisi akan dibatalkan secara permanen.')">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-outline-danger">
                                        <i class="bi bi-x-lg"></i> Revoke
                                    </button>
                                </form>
                            </div>
                            @else
                                <span class="text-muted small">ADMIN only</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $held->links() }}</div>
        @endif
    </div>
</div>
@endsection
