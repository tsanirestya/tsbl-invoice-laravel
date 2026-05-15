@extends('layouts.app')

@section('title', 'Edit Reservasi')
@section('page-title', 'Edit Reservasi')

@section('content')
<div class="page-hdr d-flex align-items-center justify-content-between mb-3">
    <div>
        <div class="page-title">Edit — {{ $reservation->reservation_no }}</div>
        <div class="page-sub">Update data reservasi</div>
    </div>
    <a href="{{ route('reservations.show', $reservation) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger mb-3">
    <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('reservations.update', $reservation) }}">
@csrf @method('PUT')

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Data Tamu</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-8">
                        <label class="form-label">Nama Tamu <span class="text-danger">*</span></label>
                        <input type="text" name="guest_name" value="{{ old('guest_name', $reservation->guest_name) }}"
                               class="form-control" required>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Negara</label>
                        <select name="guest_country" id="countrySelect" class="form-select">
                            @include('partials._country_options', ['selected' => old('guest_country', $reservation->guest_country)])
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold">Detail Reservasi</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-4">
                        <label class="form-label">Tanggal Kunjungan <span class="text-danger">*</span></label>
                        <input type="date" name="visit_date"
                               value="{{ old('visit_date', $reservation->visit_date->format('Y-m-d')) }}"
                               class="form-control" required>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            @foreach(['PENDING','CONFIRMED','NO_SHOW','COMPLETED'] as $s)
                                <option value="{{ $s }}" {{ old('status', $reservation->status) === $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Metode Pembayaran</label>
                        <select name="payment_method" class="form-select">
                            <option value="">— Pilih —</option>
                            @foreach(['TRANSFER_GROSS','TRANSFER_NETT','ON_THE_SPOT'] as $m)
                                <option value="{{ $m }}" {{ old('payment_method', $reservation->payment_method) === $m ? 'selected' : '' }}>
                                    {{ str_replace('_', ' ', $m) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $reservation->notes) }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 justify-content-end">
    <a href="{{ route('reservations.show', $reservation) }}" class="btn btn-outline-secondary">Batal</a>
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Simpan Perubahan</button>
</div>
</form>
@endsection

@push('scripts')
<script>
new TomSelect('#countrySelect', {
    create: false,
    allowEmptyOption: true,
    placeholder: 'Cari negara...',
});
</script>
@endpush
