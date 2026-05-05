@extends('layouts.app')

@section('title', 'Edit Partner')
@section('page-title', 'Edit Partner')

@section('content')
<div class="mb-3">
    <a href="{{ route('partners.show', $partner) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="card">
    <div class="card-header fw-semibold">Edit: {{ $partner->nama_partner }}</div>
    <div class="card-body">
        <form method="POST" action="{{ route('partners.update', $partner) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            @include('partners._form')
            <hr>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
            </button>
        </form>
    </div>
</div>
@endsection
