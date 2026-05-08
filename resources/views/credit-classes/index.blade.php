@extends('layouts.app')

@section('title', 'Credit Classes')
@section('page-title', 'Credit Classes')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Daftar Credit Class</h5>
    <a href="{{ route('credit-classes.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> Tambah Class
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show py-2">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:50px">Order</th>
                    <th>Nama Class</th>
                    <th>Warna</th>
                    <th>Min Limit</th>
                    <th>Max Limit</th>
                    <th>Deskripsi</th>
                    <th>Partner</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($creditClasses as $class)
                <tr>
                    <td class="text-center text-muted small">{{ $class->sort_order }}</td>
                    <td>
                        <span class="badge bg-{{ $class->color }} fs-6 px-3">{{ $class->name }}</span>
                    </td>
                    <td class="small text-muted">{{ ucfirst($class->color) }}</td>
                    <td class="small">Rp {{ number_format($class->min_limit, 0, ',', '.') }}</td>
                    <td class="small">
                        @if($class->max_limit)
                            Rp {{ number_format($class->max_limit, 0, ',', '.') }}
                        @else
                            <span class="text-muted">Tidak terbatas</span>
                        @endif
                    </td>
                    <td class="small text-muted">{{ $class->description ?? '—' }}</td>
                    <td class="text-center">
                        <span class="badge bg-secondary">{{ $class->partners_count }}</span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('credit-classes.edit', $class) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('credit-classes.destroy', $class) }}" class="d-inline"
                              onsubmit="return confirm('Hapus credit class \'{{ $class->name }}\'?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" {{ $class->partners_count > 0 ? 'disabled' : '' }}
                                    title="{{ $class->partners_count > 0 ? $class->partners_count . ' partner memakai class ini' : 'Hapus' }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">Belum ada credit class.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
