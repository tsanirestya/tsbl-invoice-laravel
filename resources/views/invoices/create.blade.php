@extends('layouts.app')
@section('title', 'Buat Invoice')
@section('page-title', 'Buat Invoice')

@section('content')
<div class="d-flex align-items-center mb-3">
    <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-outline-secondary me-2">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-semibold">Buat Invoice Baru</h5>
</div>

<form method="POST" action="{{ route('invoices.store') }}" novalidate>
    @csrf
    @include('invoices._form')

    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i> Simpan Invoice
        </button>
    </div>
</form>
@endsection
