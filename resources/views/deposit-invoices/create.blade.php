@extends('layouts.app')
@section('title', 'Buat Invoice Deposit')
@section('page-title', 'Buat Invoice Deposit')

@section('content')
<div class="d-flex align-items-center mb-3 gap-2">
    <a href="{{ route('deposit-invoices.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-semibold">Buat Invoice Deposit</h5>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom fw-semibold py-2">
                <i class="bi bi-wallet2 me-2 text-primary"></i>Data Invoice Deposit
            </div>
            <div class="card-body">
                <form action="{{ route('deposit-invoices.store') }}" method="POST">
                    @csrf
                    @include('deposit-invoices._form')
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Simpan Invoice Deposit
                        </button>
                        <a href="{{ route('deposit-invoices.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
