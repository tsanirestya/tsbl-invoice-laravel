@extends('layouts.app')

@section('title', 'Tambah Template Booking Pass')
@section('page-title', 'Tambah Template Booking Pass')

@section('content')
<div class="page-hdr d-flex align-items-center justify-content-between mb-3">
    <div>
        <div class="page-title">Tambah Template Booking Pass</div>
    </div>
    <a href="{{ route('booking-pass-templates.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger mb-3"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="card" style="max-width:560px">
    <div class="card-body">
        <form method="POST" action="{{ route('booking-pass-templates.store') }}" enctype="multipart/form-data">
        @csrf
            <div class="mb-3">
                <label class="form-label">Nama Template <span class="text-danger">*</span></label>
                <input type="text" name="template_name" value="{{ old('template_name') }}" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Partner <small class="text-muted">(kosong = default untuk semua partner)</small></label>
                <select name="partner_id" class="form-select">
                    <option value="">— Default (semua partner) —</option>
                    @foreach($partners as $p)
                        <option value="{{ $p->id }}" {{ old('partner_id') == $p->id ? 'selected' : '' }}>{{ $p->nama_partner }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Tipe Template <small class="text-muted">(kosong = semua tipe)</small></label>
                <select name="template_type" class="form-select">
                    <option value="">— Semua Tipe —</option>
                    <option value="self_service" {{ old('template_type') == 'self_service' ? 'selected' : '' }}>Self Service</option>
                    <option value="internal"     {{ old('template_type') == 'internal'     ? 'selected' : '' }}>Internal</option>
                    <option value="partner"      {{ old('template_type') == 'partner'      ? 'selected' : '' }}>Partner</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Mode QR / Barcode</label>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input type="radio" name="qr_type" value="qr" id="qrTypeQr" class="form-check-input"
                               {{ old('qr_type', 'qr') == 'qr' ? 'checked' : '' }}>
                        <label class="form-check-label" for="qrTypeQr"><i class="bi bi-qr-code me-1"></i>QR Code</label>
                    </div>
                    <div class="form-check">
                        <input type="radio" name="qr_type" value="barcode" id="qrTypeBarcode" class="form-check-input"
                               {{ old('qr_type') == 'barcode' ? 'checked' : '' }}>
                        <label class="form-check-label" for="qrTypeBarcode"><i class="bi bi-upc-scan me-1"></i>Barcode</label>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">File Template (background gambar) <small class="text-muted">opsional, JPG/PNG, maks 5MB</small></label>
                <input type="file" name="template_file" class="form-control" accept=".jpg,.jpeg,.png">
            </div>
            <div class="mb-3 form-check">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" id="isActive" class="form-check-input" checked>
                <label class="form-check-label" for="isActive">Template Aktif</label>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('booking-pass-templates.index') }}" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
