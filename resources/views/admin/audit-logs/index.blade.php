@extends('layouts.app')

@section('title', 'Audit Trail')
@section('page-title', 'Audit Trail')

@push('styles')
<style>
    .log-card {
        border-radius: 14px;
        border: 1px solid #e8edf5;
        box-shadow: 0 2px 8px rgba(15,23,41,.06);
        background: #fff;
        overflow: hidden;
    }
    .log-table thead th {
        background: #f1f5fd;
        font-size: .65rem;
        font-weight: 700;
        letter-spacing: .6px;
        text-transform: uppercase;
        color: #6b7a99;
        padding: .8rem 1rem;
        border-bottom: 2px solid #e2e8f0;
    }
    .log-table tbody td {
        padding: .8rem 1rem;
        font-size: .82rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .badge-event {
        font-size: .65rem; font-weight: 700; padding: .25em .6em; border-radius: 6px; text-transform: uppercase;
    }
    .event-created { background: #d1fae5; color: #065f46; }
    .event-updated { background: #e0f2fe; color: #0369a1; }
    .event-deleted { background: #fee2e2; color: #991b1b; }
    .event-restored{ background: #fef3c7; color: #92400e; }
    
    .module-name {
        font-size: .72rem; color: #64748b; font-weight: 500;
    }
    .user-info { display: flex; align-items: center; gap: 8px; }
    .user-avatar {
        width: 24px; height: 24px; border-radius: 50%; background: #f1f5fc;
        display: flex; align-items: center; justify-content: center; font-size: .6rem; font-weight: 700; color: #64748b;
    }
    .filter-bar {
        background: #fff; border: 1px solid #e8edf5; border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem;
    }
    .json-diff {
        max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-family: monospace; font-size: .75rem; color: #94a3b8;
    }
</style>
@endpush

@section('content')
<div class="filter-bar">
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <label class="form-label small fw-bold">User</label>
            <select name="user_id" class="form-select form-select-sm">
                <option value="">Semua User</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold">Aksi</label>
            <select name="event" class="form-select form-select-sm">
                <option value="">Semua Aksi</option>
                <option value="created" @selected(request('event') == 'created')>Created</option>
                <option value="updated" @selected(request('event') == 'updated')>Updated</option>
                <option value="deleted" @selected(request('event') == 'deleted')>Deleted</option>
                <option value="restored" @selected(request('event') == 'restored')>Restored</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold">Module</label>
            <input type="text" name="module" class="form-control form-control-sm" placeholder="e.g. Partner, Invoice" value="{{ request('module') }}">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
        </div>
        <div class="col-md-1 d-flex align-items-end">
            <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
        </div>
    </form>
</div>

<div class="log-card">
    <table class="table log-table mb-0">
        <thead>
            <tr>
                <th>Waktu</th>
                <th>User</th>
                <th>Aksi</th>
                <th>Module</th>
                <th>ID</th>
                <th>Perubahan</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td class="text-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                <td>
                    <div class="user-info">
                        <div class="user-avatar">{{ $log->user ? strtoupper(substr($log->user->full_name, 0, 1)) : '?' }}</div>
                        <span>{{ $log->user->full_name ?? 'System' }}</span>
                    </div>
                </td>
                <td>
                    <span class="badge-event event-{{ $log->event }}">{{ $log->event }}</span>
                </td>
                <td>
                    <span class="module-name">{{ class_basename($log->auditable_type) }}</span>
                </td>
                <td class="fw-bold">#{{ $log->auditable_id }}</td>
                <td>
                    @if($log->event === 'updated')
                        <div class="json-diff" title="{{ json_encode($log->new_values) }}">
                            {{ implode(', ', array_keys($log->new_values)) }}
                        </div>
                    @else
                        <span class="text-muted small">—</span>
                    @endif
                </td>
                <td class="text-end">
                    <a href="{{ route('admin.audit-logs.show', $log) }}" class="btn btn-sm btn-light py-0 px-2" style="font-size: .75rem;">Detail</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">Belum ada jejak audit yang tercatat.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">
    {{ $logs->links() }}
</div>
@endsection
