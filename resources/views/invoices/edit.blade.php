@extends('layouts.app')
@section('title', 'Edit ' . $invoice->invoice_no)
@section('page-title', 'Edit Invoice')

@section('content')
<div class="d-flex align-items-center mb-3">
    <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-outline-secondary me-2">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-semibold">Edit — {{ $invoice->invoice_no }}</h5>
</div>

<form id="invoice-form" method="POST" action="{{ route('invoices.update', $invoice) }}" novalidate>
    @csrf @method('PUT')
    @include('invoices._form')

    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-outline-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i> Simpan Perubahan
        </button>
    </div>
</form>
@endsection
