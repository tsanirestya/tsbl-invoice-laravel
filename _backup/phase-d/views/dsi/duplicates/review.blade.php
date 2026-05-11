@extends('layouts.app')
@section('title', 'Review Duplikat DSI')
@section('page-title', 'DSI Duplicate Review')

@section('content')

<div class="d-flex align-items-center gap-2 mb-3 page-hdr">
    <a href="{{ route('dsi.import.create') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;"><i class="bi bi-arrow-left"></i></a>
    <div>
        <div class="page-title">Review Duplikat DSI</div>
        <div class="page-sub">Review transaksi yang dicurigai sebagai duplikat</div>
    </div>
</div>

<div class="card card-clean">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-flag-fill text-warning"></i>
        Antrian Review Duplikat
    </div>
    <div class="card-body p-0">
        @if($flags->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-check-circle fs-1 opacity-25 d-block mb-2"></i>
                <p>Tidak ada duplikat yang perlu direview.</p>
            </div>
        @else
        <div class="table-responsive">
            <table class="table mb-0" style="font-size:.83rem;">
                <thead>
                    <tr>
                        <th class="ps-4">Ref No</th>
                        <th>Batch</th>
                        <th>Guest Name</th>
                        <th class="text-end">Amount</th>
                        <th>Reason</th>
                        <th class="text-center pe-3">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($flags as $flag)
                    @php $tx = $flag->transaction; @endphp
                    <tr>
                        <td class="ps-4 fw-bold">
                            {{ $tx->ref_no }}
                            <div class="small text-muted">{{ $tx->created_at->format('d/m/Y H:i') }}</div>
                        </td>
                        <td>
                            <a href="{{ route('dsi.batches.show', $tx->batch) }}" class="text-decoration-none">{{ $tx->batch->batch_ref }}</a>
                        </td>
                        <td>{{ $tx->guest_name }}</td>
                        <td class="text-end fw-bold">Rp {{ number_format($tx->total_amount, 0, ',', '.') }}</td>
                        <td>
                            <span class="badge bg-warning text-dark">{{ $flag->reason }}</span>
                        </td>
                        <td class="text-center pe-3">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#resolveModal{{ $flag->id }}">
                                Resolve
                            </button>

                            {{-- Modal --}}
                            <div class="modal fade text-start" id="resolveModal{{ $flag->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h6 class="modal-title">Resolve Duplicate Flag #{{ $flag->id }}</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="{{ route('dsi.duplicates.resolve', $flag) }}">
                                            @csrf
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Pilih Tindakan</label>
                                                    <select name="action" class="form-select" required>
                                                        <option value="approve">Konfirmasi Duplikat (Kunci Transaksi)</option>
                                                        <option value="reject">Bukan Duplikat (Buka Transaksi)</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Catatan Review</label>
                                                    <textarea name="notes" class="form-control" rows="3" placeholder="Alasan keputusan ini..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary">Simpan Keputusan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($flags->hasPages())
        <div class="px-4 py-3 border-top">
            {{ $flags->links() }}
        </div>
        @endif
        @endif
    </div>
</div>

@endsection
