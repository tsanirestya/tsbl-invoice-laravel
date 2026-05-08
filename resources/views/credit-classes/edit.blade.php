@extends('layouts.app')

@section('title', 'Edit Credit Class')
@section('page-title', 'Credit Classes')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Edit Credit Class — <span class="badge bg-{{ $creditClass->color }}">{{ $creditClass->name }}</span></h5>
    <a href="{{ route('credit-classes.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('credit-classes.update', $creditClass) }}">
            @csrf @method('PUT')
            @include('credit-classes._form')
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i> Perbarui
                </button>
                <a href="{{ route('credit-classes.index') }}" class="btn btn-outline-secondary ms-2">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
