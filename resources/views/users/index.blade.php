@extends('layouts.app')

@section('title', 'Pengguna')
@section('page-title', 'Manajemen Pengguna')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Daftar Pengguna</h5>
    <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-person-plus me-1"></i> Tambah Pengguna
    </a>
</div>

{{-- Filters --}}
<form method="GET" class="card card-body mb-3 py-2">
    <div class="row g-2">
        <div class="col-sm-6 col-md-5">
            <input type="text" name="search" class="form-control form-control-sm"
                   placeholder="Cari nama / email..." value="{{ request('search') }}">
        </div>
        <div class="col-sm-4 col-md-3">
            <select name="status" class="form-select form-select-sm">
                <option value="">Semua Role</option>
                @foreach(['ADMIN','FINANCE','SALES','VIEWER','ADMISSION'] as $role)
                    <option value="{{ $role }}" @selected(request('status') === $role)>{{ $role }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-sm btn-outline-secondary" type="submit">
                <i class="bi bi-search"></i>
            </button>
            <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-x"></i>
            </a>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Jabatan</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Tanda Tangan</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $i => $user)
                <tr>
                    <td class="text-muted small">{{ $users->firstItem() + $i }}</td>
                    <td class="fw-semibold">{{ $user->full_name }}</td>
                    <td class="text-muted small">{{ $user->email }}</td>
                    <td class="small">{{ $user->position_name ?? '—' }}</td>
                    <td>
                        <span class="badge bg-{{ match($user->user_status) {
                            'ADMIN'     => 'danger',
                            'FINANCE'   => 'primary',
                            'SALES'     => 'success',
                            'ADMISSION' => 'info',
                            default     => 'secondary'
                        } }}">{{ $user->user_status }}</span>
                    </td>
                    <td>
                        @if($user->is_active)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-secondary">Nonaktif</span>
                        @endif
                    </td>
                    <td>
                        @if($user->signature_image)
                            <img src="{{ Storage::url($user->signature_image) }}"
                                 alt="TTD" height="32" class="rounded border">
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @if($user->id !== auth()->id())
                        <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline"
                              onsubmit="return confirm('Hapus pengguna {{ $user->full_name }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">Tidak ada pengguna ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="card-footer">
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection
