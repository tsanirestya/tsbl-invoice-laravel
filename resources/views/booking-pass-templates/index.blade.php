@extends('layouts.app')

@section('title', 'Template Booking Pass')
@section('page-title', 'Template Booking Pass')

@section('content')
<div class="page-hdr d-flex align-items-center justify-content-between mb-3">
    <div>
        <div class="page-title">Template Booking Pass</div>
        <div class="page-sub">Kelola template booking pass per partner</div>
    </div>
    <a href="{{ route('booking-pass-templates.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Tambah Template
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="card table-card">
    <div class="card-body p-0">
        @if($templates->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-file-image fs-1 d-block mb-2"></i>
                Belum ada template. Tambah template baru.
            </div>
        @else
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Nama Template</th>
                        <th>Partner</th>
                        <th>Tipe</th>
                        <th>QR/Barcode</th>
                        <th>File</th>
                        <th class="text-center">Status</th>
                        <th>Dibuat oleh</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($templates as $tpl)
                    <tr>
                        <td class="fw-semibold">{{ $tpl->template_name }}</td>
                        <td>{!! $tpl->partner?->nama_partner ?? '<span class="badge bg-secondary">Default (semua)</span>' !!}</td>
                        <td>
                            @php $typeLabels = ['self_service'=>'Self Service','internal'=>'Internal','partner'=>'Partner']; @endphp
                            @if($tpl->template_type)
                                <span class="badge bg-info text-dark">{{ $typeLabels[$tpl->template_type] ?? $tpl->template_type }}</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            @if(($tpl->qr_type ?? 'qr') === 'barcode')
                                <span class="badge bg-secondary"><i class="bi bi-upc-scan me-1"></i>Barcode</span>
                            @else
                                <span class="badge bg-dark"><i class="bi bi-qr-code me-1"></i>QR Code</span>
                            @endif
                        </td>
                        <td>
                            @if($tpl->template_file)
                                <a href="{{ asset('storage/' . $tpl->template_file) }}" target="_blank" class="small">
                                    <i class="bi bi-file me-1"></i>Lihat file
                                </a>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($tpl->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td class="small">{{ $tpl->creator?->name ?? '—' }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('booking-pass-templates.edit', $tpl) }}" class="btn btn-xs btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('booking-pass-templates.destroy', $tpl) }}"
                                      onsubmit="return confirm('Hapus template ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $templates->links() }}</div>
        @endif
    </div>
</div>
@endsection
