@extends('layouts.app')

@section('title', 'Tambah Produk')
@section('page-title', 'Tambah Produk')

@section('content')
<div class="mb-3">
    <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="card" style="max-width:640px">
    <div class="card-header fw-semibold">Form Produk Baru</div>
    <div class="card-body">
        <form method="POST" action="{{ route('products.store') }}">
            @csrf
            @include('products._form')
            <hr>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i> Simpan
            </button>
        </form>
    </div>
</div>
@endsection
