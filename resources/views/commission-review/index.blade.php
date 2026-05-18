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
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- Finance Manager: pending requests needing review --}}
@if(auth()->user()->canApproveFinance() && $pendingRequests->isNotEmpty())
<div class="card mb-3 border-warning">
    <div class="card-header bg-warning-subtle fw-semibold">
        <i class="bi bi-hourglass-split me-1"></i>
        Request Menunggu Persetujuan ({{ $pendingRequests->count() }})
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Reservasi</th>
                        <th>Partner</th>
                        <th>Aksi Diminta</th>
                        <th>Alasan</th>
                        <th>Diajukan oleh</th>
                        <th>Tgl Pengajuan</th>
                        <th class="text-center">Review</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingRequests as $req)
                    <tr>
                        <td>
                            <a href="{{ route('reservations.show', $req->reservationPayment->reservation) }}" class="fw-semibold text-decoration-none small">
                                {{ $req->reservationPayment->reservation?->reservation_no }}
                            </a>
                        </td>
                        <td>{{ $req->reservationPayment->reservation?->partner?->nama_partner ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $req->action === 'release' ? 'bg-success' : 'bg-danger' }}">
                                {{ strtoupper($req->action) }}
                            </span>
                        </td>
                        <td><small class="text-muted">{{ $req->reason }}</small></td>
                        <td><small>{{ $req->requestedBy?->full_name }}</small></td>
                        <td><small>{{ $req->created_at->format('d/m/Y H:i') }}</small></td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <button type="button" class="btn btn-xs btn-success"
                                        data-bs-toggle="modal"
                                        data-bs-target="#approveModal{{ $req->id }}">
                                    <i class="bi bi-check-lg"></i> Setuju
                                </button>
                                <button type="button" class="btn btn-xs btn-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#rejectModal{{ $req->id }}">
                                    <i class="bi bi-x-lg"></i> Tolak
                                </button>
                            </div>

                            {{-- Approve Modal --}}
                            <div class="modal fade" id="approveModal{{ $req->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form method="POST" action="{{ route('commission-requests.approve', $req) }}">
                                        @csrf
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Setujui Request {{ strtoupper($req->action) }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body text-start">
                                                <p class="mb-2">Reservasi: <strong>{{ $req->reservationPayment->reservation?->reservation_no }}</strong></p>
                                                <p class="mb-3">Alasan pengaju: <em>{{ $req->reason }}</em></p>
                                                <label class="form-label">Catatan Review (opsional)</label>
                                                <textarea name="review_notes" class="form-control" rows="2" placeholder="Catatan untuk pengaju..."></textarea>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-success btn-sm">Konfirmasi Setuju</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Reject Modal --}}
                            <div class="modal fade" id="rejectModal{{ $req->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form method="POST" action="{{ route('commission-requests.reject', $req) }}">
                                        @csrf
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title text-danger">Tolak Request</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body text-start">
                                                <p class="mb-2">Reservasi: <strong>{{ $req->reservationPayment->reservation?->reservation_no }}</strong></p>
                                                <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                                                <textarea name="review_notes" class="form-control" rows="2" required placeholder="Jelaskan alasan penolakan..."></textarea>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-danger btn-sm">Tolak Request</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Main: komisi yang masih di-hold --}}
<div class="card table-card">
    <div class="card-header fw-semibold">Komisi Di-hold</div>
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
                        <th>Tgl</th>
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
                            @if(auth()->user()->canApproveFinance())
                                {{-- Finance Manager / Admin: direct action --}}
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
                            @elseif(auth()->user()->isFinanceStaff())
                                {{-- Finance Staff: request action --}}
                                @if($pay->pendingRequest)
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-hourglass-split me-1"></i>Pending Review
                                    </span>
                                @else
                                    <button type="button" class="btn btn-xs btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#requestModal{{ $pay->id }}">
                                        <i class="bi bi-send me-1"></i>Ajukan
                                    </button>

                                    <div class="modal fade" id="requestModal{{ $pay->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <form method="POST" action="{{ route('commission-review.request-action', $pay) }}">
                                                @csrf
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Ajukan Tindakan Komisi</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body text-start">
                                                        <p class="mb-3">Reservasi: <strong>{{ $pay->reservation?->reservation_no }}</strong></p>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Tindakan yang Diajukan <span class="text-danger">*</span></label>
                                                            <select name="action" class="form-select" required>
                                                                <option value="">— Pilih tindakan —</option>
                                                                <option value="release">Release — Bayarkan komisi ke partner</option>
                                                                <option value="revoke">Revoke — Batalkan komisi secara permanen</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label class="form-label fw-semibold">Alasan <span class="text-danger">*</span></label>
                                                            <textarea name="reason" class="form-control" rows="3" required
                                                                      placeholder="Jelaskan alasan pengajuan tindakan ini..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-primary btn-sm">Ajukan Request</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <span class="text-muted small">—</span>
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
