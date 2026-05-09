@extends('layouts.app')
@section('title', 'Buat Reservasi')
@section('page-title', 'Reservasi')

@section('content')

<div class="d-flex align-items-center gap-2 mb-3 page-hdr">
    <a href="{{ route('reservations.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;"><i class="bi bi-arrow-left"></i></a>
    <div>
        <div class="page-title">Buat Reservasi Baru</div>
        <div class="page-sub">Isi detail reservasi tamu</div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-clean">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-calendar2-plus" style="color:#8b5cf6"></i>
                Detail Reservasi
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('reservations.store') }}" id="res-create-form">
                    @csrf
                    @if($errors->any())
                    <div class="alert alert-modern mb-3" style="background:#fef2f2;border-left:4px solid #ef4444;color:#991b1b;">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $e)
                                <li style="font-size:.84rem;">{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">Partner <span class="text-danger">*</span></label>
                            <select name="partner_id" class="form-select ts-select" required>
                                <option value="">-- Pilih Partner --</option>
                                @foreach($partners as $p)
                                    <option value="{{ $p->id }}" @selected(old('partner_id') == $p->id)>{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">Nama Tamu <span class="text-danger">*</span></label>
                            <input type="text" name="guest_name" class="form-control" placeholder="John Doe" value="{{ old('guest_name') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">Booking Reference</label>
                            <input type="text" name="booking_ref" class="form-control" placeholder="BK-2025-XXX" value="{{ old('booking_ref') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">Pax</label>
                            <input type="number" name="pax" class="form-control" min="1" value="{{ old('pax', 1) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">Tipe Produk</label>
                            <input type="text" name="product_type" class="form-control" placeholder="e.g. Entrance Ticket" value="{{ old('product_type') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">Check-in Date <span class="text-danger">*</span></label>
                            <input type="date" name="check_in_date" class="form-control" value="{{ old('check_in_date') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">Check-out Date</label>
                            <input type="date" name="check_out_date" class="form-control" value="{{ old('check_out_date') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">Estimasi Proforma</label>
                            <div class="input-group">
                                <span class="input-group-text" style="font-size:.82rem;background:#f8fafc;">Rp</span>
                                <input type="text" name="proforma_amount" class="form-control currency-input" placeholder="0" value="{{ old('proforma_amount') }}">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">Catatan</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Catatan internal...">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Simpan Reservasi
                        </button>
                        <a href="{{ route('reservations.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.ts-select').forEach(function(el) {
        new TomSelect(el, { maxOptions: 200 });
    });
});
</script>
@endpush
