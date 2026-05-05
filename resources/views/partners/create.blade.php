@extends('layouts.app')

@section('title', 'Tambah Partner')
@section('page-title', 'Tambah Partner')

@section('content')
<div class="mb-3">
    <a href="{{ route('partners.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="card">
    <div class="card-header fw-semibold">Form Partner Baru</div>
    <div class="card-body">
        <form method="POST" action="{{ route('partners.store') }}" enctype="multipart/form-data">
            @csrf
            @include('partners._form')
            <hr>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i> Simpan
            </button>
        </form>
    </div>
</div>
@endsection
