@extends('layouts.app')

@section('title', 'Employee-Partner Check')
@section('page-title', 'Employee-Partner Check')

@section('content')
<div class="page-hdr d-flex align-items-center justify-content-between mb-3">
    <div>
        <div class="page-title">Employee-Partner Cross Check</div>
        <div class="page-sub">Deteksi kesamaan data partner dengan karyawan</div>
    </div>
    <form method="POST" action="{{ route('admin.employee-partner-checks.run') }}">
        @csrf
        <button type="submit" class="btn btn-warning btn-sm"
                onclick="return confirm('Jalankan full cross-check? Proses ini bisa memakan waktu.')">
            <i class="bi bi-search me-1"></i> Jalankan Check
        </button>
    </form>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="card table-card">
    <div class="card-body p-0">
        @if($checks->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-shield-check fs-1 d-block mb-2"></i>
                Belum ada kecocokan terdeteksi. Jalankan check untuk memulai.
            </div>
        @else
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Partner</th>
                        <th>Karyawan</th>
                        <th>Tipe Cocok</th>
                        <th>Detail</th>
                        <th class="text-center">Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($checks as $check)
                    <tr class="{{ !$check->is_reviewed ? 'table-warning' : '' }}">
                        <td class="fw-semibold">{{ $check->partner?->nama_partner }}</td>
                        <td>{{ $check->user?->name }}</td>
                        <td>
                            <span class="badge bg-{{ $check->match_type === 'EMAIL' ? 'danger' : 'warning' }} text-dark">
                                {{ $check->match_type }}
                            </span>
                        </td>
                        <td><small class="text-muted">{{ $check->match_detail }}</small></td>
                        <td class="text-center">
                            @if($check->is_reviewed)
                                <span class="badge bg-success">Reviewed</span>
                                <div class="small text-muted">{{ $check->reviewedBy?->name }} — {{ $check->reviewed_at?->format('d/m/Y') }}</div>
                            @else
                                <span class="badge bg-warning text-dark">Pending</span>
                            @endif
                        </td>
                        <td>
                            @if(!$check->is_reviewed)
                            <button type="button" class="btn btn-xs btn-outline-primary"
                                    data-bs-toggle="modal" data-bs-target="#reviewModal{{ $check->id }}">
                                Review
                            </button>

                            <div class="modal fade" id="reviewModal{{ $check->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('admin.employee-partner-checks.review', $check) }}">
                                        @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title">Review: {{ $check->match_type }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-2"><strong>Partner:</strong> {{ $check->partner?->nama_partner }}</div>
                                                <div class="mb-3"><strong>Karyawan:</strong> {{ $check->user?->name }}</div>
                                                <div class="mb-3 text-muted small">{{ $check->match_detail }}</div>
                                                <label class="form-label">Catatan Review</label>
                                                <textarea name="review_notes" class="form-control" rows="3"
                                                          placeholder="Contoh: Sudah dicek, bukan partner dummy. Anak kandung karyawan."></textarea>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary">Tandai Reviewed</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @elseif($check->review_notes)
                                <span class="small text-muted" title="{{ $check->review_notes }}">
                                    <i class="bi bi-chat-text"></i>
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $checks->links() }}</div>
        @endif
    </div>
</div>
@endsection
