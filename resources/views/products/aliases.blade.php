@extends('layouts.app')

@section('title', 'Alias Produk — ' . $product->product_name)
@section('page-title', 'Alias Produk')

@section('content')

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h5 class="mb-0 fw-semibold">Alias: {{ $product->product_name }}</h5>
        <small class="text-muted">Mapping nama tiket dari Excel ke produk ini</small>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2">{{ session('success') }}</div>
@endif

@if($errors->any())
<div class="alert alert-danger py-2">
    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
</div>
@endif

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold">Tambah Alias</div>
            <div class="card-body">
                <form method="POST" action="{{ route('product-aliases.store', $product) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nama Alias <span class="text-danger">*</span></label>
                        <input type="text" name="alias_name" class="form-control"
                               placeholder="mis: HTL DELUXE ROOM" value="{{ old('alias_name') }}" required>
                        <div class="form-text">Akan disimpan UPPERCASE. Harus unik di seluruh alias.</div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Alias
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold">
                Alias Terdaftar
                <span class="badge bg-secondary ms-2">{{ $aliases->count() }}</span>
            </div>
            <div class="card-body p-0">
                @if($aliases->isEmpty())
                <div class="text-center text-muted py-4 px-3">
                    <i class="bi bi-tag fs-3 d-block mb-2"></i>
                    Belum ada alias. Tambah alias agar Excel import bisa match ke produk ini.
                </div>
                @else
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Alias Name</th>
                            <th>Dibuat Oleh</th>
                            <th>Tgl</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($aliases as $alias)
                        <tr>
                            <td class="fw-semibold font-monospace">{{ $alias->alias_name }}</td>
                            <td class="text-muted small">{{ $alias->creator->full_name ?? '-' }}</td>
                            <td class="text-muted small">{{ $alias->created_at?->format('d/m/Y') ?? '-' }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('product-aliases.destroy', [$product, $alias]) }}"
                                      onsubmit="return confirm('Hapus alias ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
