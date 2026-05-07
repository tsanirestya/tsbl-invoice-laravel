@extends('layouts.app')

@section('title', 'Produk')
@section('page-title', 'Produk')

@push('styles')
<style>
    .filter-pill {
        background: #f8fafc; border-radius: 8px;
        padding: .85rem 1rem; margin-bottom: 1rem;
        box-shadow: 0 1px 3px rgba(15,23,41,.06);
    }
    .filter-pill .form-control,
    .filter-pill .form-select {
        border-radius: 8px; border-color: #e2e8f0;
        font-size: .82rem; padding: .38rem .7rem;
        background: #fff;
    }

    .product-actions .btn-act {
        width: 28px; height: 28px; border-radius: 7px;
        border: 1px solid #e2e8f0; background: #fff; color: #64748b;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .78rem; text-decoration: none;
        transition: background .12s, color .12s;
    }
    .product-actions .btn-act.edit:hover { background: #eff6ff; color: #3b82f6; border-color: #bfdbfe; }
    .product-actions .btn-act.del:hover  { background: #fef2f2; color: #dc2626; border-color: #fca5a5; }

    .product-card-item {
        padding: .85rem 1rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .product-card-item:last-child { border-bottom: none; }
    .product-card-item:hover { background: #fafbff; }
    .product-name { font-weight: 700; font-size: .88rem; color: #1e293b; }
    .product-meta { font-size: .76rem; color: #64748b; margin-top: 2px; }
</style>
@endpush

@section('content')

{{-- ── Header ── --}}
<div class="d-flex justify-content-between align-items-center mb-3 page-hdr">
    <div>
        <div class="page-title">Daftar Produk</div>
    </div>
    <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm" style="border-radius:8px;">
        <i class="bi bi-plus-lg"></i>
        <span class="d-none d-sm-inline ms-1">Tambah Produk</span>
    </a>
</div>

{{-- ── Filter ── --}}
<form method="GET" class="filter-pill">
    <div class="row g-2 align-items-center">
        <div class="col-12 col-sm-6 col-md-5">
            <input type="text" name="search" class="form-control form-control-sm"
                   placeholder="Cari nama produk..." value="{{ request('search') }}">
        </div>
        <div class="col-6 col-sm-3 col-md-2">
            <select name="active" class="form-select form-select-sm">
                <option value="">Semua Status</option>
                <option value="1" @selected(request('active') === '1')>Aktif</option>
                <option value="0" @selected(request('active') === '0')>Nonaktif</option>
            </select>
        </div>
        <div class="col-auto d-flex gap-2">
            <button class="btn btn-sm btn-primary" type="submit" style="border-radius:8px;padding:.35rem .8rem;">
                <i class="bi bi-search"></i>
            </button>
            @if(request()->hasAny(['search','active']))
            <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;padding:.35rem .8rem;">
                <i class="bi bi-x-lg"></i>
            </a>
            @endif
        </div>
    </div>
</form>

{{-- ── List ── --}}
<div class="card card-clean overflow-hidden">

    {{-- Desktop table --}}
    <div class="d-none d-md-block">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th style="background:#f8fafc;font-size:.65rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#64748b;padding:.65rem 1rem;width:40px">#</th>
                    <th style="background:#f8fafc;font-size:.65rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#64748b;padding:.65rem 1rem;">Nama Produk</th>
                    <th style="background:#f8fafc;font-size:.65rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#64748b;padding:.65rem 1rem;" class="d-none d-lg-table-cell">Deskripsi</th>
                    <th style="background:#f8fafc;font-size:.65rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#64748b;padding:.65rem 1rem;">Satuan</th>
                    <th style="background:#f8fafc;font-size:.65rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#64748b;padding:.65rem 1rem;text-align:right;">Harga Default</th>
                    <th style="background:#f8fafc;font-size:.65rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#64748b;padding:.65rem 1rem;">Status</th>
                    <th style="background:#f8fafc;padding:.65rem 1rem;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $i => $product)
                <tr>
                    <td style="padding:.65rem 1rem;font-size:.83rem;border-bottom:1px solid #f8fafc;color:#94a3b8;">{{ $products->firstItem() + $i }}</td>
                    <td style="padding:.65rem 1rem;font-size:.83rem;border-bottom:1px solid #f8fafc;font-weight:600;">
                        {{ $product->product_name }}
                    </td>
                    <td style="padding:.65rem 1rem;font-size:.82rem;border-bottom:1px solid #f8fafc;color:#94a3b8;max-width:240px;" class="d-none d-lg-table-cell">
                        {{ Str::limit($product->description, 60) ?: '—' }}
                    </td>
                    <td style="padding:.65rem 1rem;font-size:.83rem;border-bottom:1px solid #f8fafc;color:#64748b;">
                        {{ $product->unit }}
                    </td>
                    <td style="padding:.65rem 1rem;font-size:.83rem;border-bottom:1px solid #f8fafc;font-weight:600;text-align:right;">
                        Rp {{ number_format($product->default_price, 0, ',', '.') }}
                    </td>
                    <td style="padding:.65rem 1rem;border-bottom:1px solid #f8fafc;">
                        @if($product->is_active)
                            <span class="badge" style="background:#dcfce7;color:#166534;font-size:.68rem;">Aktif</span>
                        @else
                            <span class="badge" style="background:#f1f5f9;color:#475569;font-size:.68rem;">Nonaktif</span>
                        @endif
                    </td>
                    <td style="padding:.65rem 1rem;border-bottom:1px solid #f8fafc;">
                        <div class="d-flex gap-1 justify-content-end product-actions">
                            <a href="{{ route('products.edit', $product) }}" class="btn-act edit" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('products.destroy', $product) }}" class="d-inline"
                                  onsubmit="return confirm('Hapus produk {{ addslashes($product->product_name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-act del" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="bi bi-box-seam fs-2 d-block mb-2 opacity-40"></i>
                        Tidak ada produk ditemukan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile card list --}}
    <div class="d-md-none">
        @forelse($products as $product)
        <div class="product-card-item">
            <div class="d-flex align-items-start justify-content-between gap-2">
                <div class="min-w-0">
                    <div class="product-name">{{ $product->product_name }}</div>
                    <div class="product-meta d-flex align-items-center gap-2 mt-1 flex-wrap">
                        <span><i class="bi bi-box me-1"></i>{{ $product->unit }}</span>
                        <span class="fw-semibold text-dark">Rp {{ number_format($product->default_price, 0, ',', '.') }}</span>
                    </div>
                    @if($product->description)
                    <div class="product-meta mt-1 text-truncate" style="max-width:200px;">{{ $product->description }}</div>
                    @endif
                </div>
                <div class="d-flex flex-column align-items-end gap-2 flex-shrink-0">
                    @if($product->is_active)
                        <span class="badge" style="background:#dcfce7;color:#166534;font-size:.65rem;">Aktif</span>
                    @else
                        <span class="badge" style="background:#f1f5f9;color:#475569;font-size:.65rem;">Nonaktif</span>
                    @endif
                    <div class="d-flex gap-1 product-actions">
                        <a href="{{ route('products.edit', $product) }}" class="btn-act edit" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('products.destroy', $product) }}"
                              onsubmit="return confirm('Hapus produk {{ addslashes($product->product_name) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-act del" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-5 text-muted">
            <i class="bi bi-box-seam fs-2 d-block mb-2 opacity-40"></i>
            Tidak ada produk ditemukan.
        </div>
        @endforelse
    </div>

    @if($products->hasPages())
    <div class="px-4 py-3" style="border-top:1px solid #f1f5f9;">
        {{ $products->links() }}
    </div>
    @endif
</div>

@endsection
