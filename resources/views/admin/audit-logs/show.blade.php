@extends('layouts.app')

@section('title', 'Detail Audit')
@section('page-title', 'Detail Audit Log')

@section('content')
<div class="mb-3">
    <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Kembali ke Daftar
    </a>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card card-clean h-100">
            <div class="card-header">Metadata Aksi</div>
            <div class="card-body">
                <table class="table table-sm mb-0 small">
                    <tr><td class="text-muted border-0">Waktu</td><td class="border-0 fw-bold">{{ $log->created_at->format('d M Y, H:i:s') }}</td></tr>
                    <tr><td class="text-muted border-0">User</td><td class="border-0 fw-bold">{{ $log->user->full_name ?? 'System' }}</td></tr>
                    <tr><td class="text-muted border-0">Aksi</td><td class="border-0"><span class="badge bg-primary">{{ strtoupper($log->event) }}</span></td></tr>
                    <tr><td class="text-muted border-0">Module</td><td class="border-0">{{ class_basename($log->auditable_type) }}</td></tr>
                    <tr><td class="text-muted border-0">Record ID</td><td class="border-0">#{{ $log->auditable_id }}</td></tr>
                    <tr><td class="text-muted border-0">IP Address</td><td class="border-0">{{ $log->ip_address }}</td></tr>
                </table>
                <hr>
                <div class="small text-muted mb-1">User Agent:</div>
                <div class="bg-light p-2 rounded small" style="font-size: .7rem;">{{ $log->user_agent }}</div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card card-clean h-100">
            <div class="card-header">Detail Perubahan</div>
            <div class="card-body">
                @if($log->event === 'updated')
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm small">
                            <thead class="bg-light">
                                <tr>
                                    <th>Field</th>
                                    <th>Sebelum (Old)</th>
                                    <th>Sesudah (New)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($log->new_values as $key => $newVal)
                                    @php $oldVal = $log->old_values[$key] ?? 'N/A'; @endphp
                                    <tr>
                                        <td class="fw-bold">{{ $key }}</td>
                                        <td class="bg-light-subtle">{{ is_array($oldVal) ? json_encode($oldVal) : $oldVal }}</td>
                                        <td class="table-success">{{ is_array($newVal) ? json_encode($newVal) : $newVal }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="mb-3">
                        <h6 class="small fw-bold">Full Data Payload:</h6>
                        <pre class="bg-light p-3 rounded small" style="font-size: .75rem;">{{ json_encode($log->event === 'created' ? $log->new_values : $log->old_values, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
