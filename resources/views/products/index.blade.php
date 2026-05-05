@extends('layouts.app')

@section('title', 'Produk')
@section('page-title', 'Manajemen Produk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Daftar Produk</h5>
    <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Tambah Produk
    </a>
</div>

<form method="GET" class="card card-body mb-3 py-2">
    <div class="row g-2">
        <div class="col-sm-6 col-md-5">
            <input type="text" name="search" class="form-control form-control-sm"
                   placeholder="Cari nama produk..." value="{{ request('search') }}">
        </div>
        <div class="col-sm-4 col-md-3">
            <select name="active" class="form-select form-select-sm">
                <option value="">Semua Status</option>
                <option value="1" @selected(request('active') === '1')>Aktif</option>
                <option value="0" @selected(request('active') === '0')>Nonaktif</option>
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
            <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i></a>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Nama Produk</th>
                    <th>Deskripsi</th>
                    <th>Satuan</th>
                    <th class="text-end">Harga Default</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $i => $product)
                <tr>
                    <td class="text-muted small">{{ $products->firstItem() + $i }}</td>
                    <td class="fw-semibold">{{ $product->product_name }}</td>
                    <td class="small text-muted">{{ Str::limit($product->description, 60) ?? '—' }}</td>
                    <td class="small">{{ $product->unit }}</td>
                    <td class="text-end fw-semibold">Rp {{ number_format($product->default_price, 0, ',', '.') }}</td>
                    <td>
                        @if($product->is_active)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-secondary">Nonaktif</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('products.destroy', $product) }}" class="d-inline"
                              onsubmit="return confirm('Hapus produk {{ $product->product_name }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Tidak ada produk ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($products->hasPages())
    <div class="card-footer">{{ $products->links() }}</div>
    @endif
</div>
@endsection
