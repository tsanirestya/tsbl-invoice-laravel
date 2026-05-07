@extends('layouts.app')

@section('title', 'Pengaturan')
@section('page-title', 'Pengaturan Sistem')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-10">
        <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Company Profile --}}
            <div class="card mb-4">
                <div class="card-header fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-building text-primary"></i> Profil Perusahaan
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Nama Perusahaan</label>
                            <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror"
                                value="{{ old('company_name', $settings['company_name'] ?? '') }}">
                            @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Email Perusahaan</label>
                            <input type="email" name="company_email" class="form-control @error('company_email') is-invalid @enderror"
                                value="{{ old('company_email', $settings['company_email'] ?? '') }}">
                            @error('company_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Telepon</label>
                            <input type="text" name="company_phone" class="form-control"
                                value="{{ old('company_phone', $settings['company_phone'] ?? '') }}">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">NPWP</label>
                            <input type="text" name="company_npwp" class="form-control"
                                value="{{ old('company_npwp', $settings['company_npwp'] ?? '') }}"
                                placeholder="00.000.000.0-000.000">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea name="company_address" class="form-control" rows="3">{{ old('company_address', $settings['company_address'] ?? '') }}</textarea>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Logo Perusahaan</label>
                            @if(!empty($settings['logo_path']))
                                <div class="mb-2">
                                    <img src="{{ asset($settings['logo_path']) }}" alt="Logo" style="max-height:60px;max-width:180px;object-fit:contain;">
                                    <div class="text-muted small mt-1">Upload baru untuk mengganti.</div>
                                </div>
                            @endif
                            <input type="file" name="logo" class="form-control @error('logo') is-invalid @enderror" accept="image/png,image/jpeg">
                            <div class="form-text">PNG/JPG, maks 2MB. Disarankan PNG transparan.</div>
                            @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Logo Navbar</label>
                            @if(!empty($settings['navbar_logo_path']))
                                <div class="mb-2 d-flex align-items-center gap-2 p-2 rounded" style="background:#1a2235;max-width:180px;">
                                    <img src="{{ asset($settings['navbar_logo_path']) }}" alt="Navbar Logo" style="max-height:32px;max-width:140px;object-fit:contain;">
                                </div>
                                <div class="text-muted small mb-1">Upload baru untuk mengganti.</div>
                            @endif
                            <input type="file" name="navbar_logo" class="form-control @error('navbar_logo') is-invalid @enderror" accept="image/png,image/jpeg">
                            <div class="form-text">PNG/JPG, maks 1MB. Tampil di sidebar atas.</div>
                            @error('navbar_logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Favicon</label>
                            @if(!empty($settings['favicon_path']))
                                <div class="mb-2 d-flex align-items-center gap-2">
                                    <img src="{{ asset($settings['favicon_path']) }}" alt="Favicon" style="width:32px;height:32px;object-fit:contain;border:1px solid #dee2e6;border-radius:4px;">
                                    <span class="text-muted small">Upload baru untuk mengganti.</span>
                                </div>
                            @endif
                            <input type="file" name="favicon" class="form-control @error('favicon') is-invalid @enderror" accept="image/png,image/jpeg,image/x-icon">
                            <div class="form-text">PNG/ICO, maks 512KB. Ukuran ideal 32×32 atau 64×64.</div>
                            @error('favicon')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bank Info --}}
            <div class="card mb-4">
                <div class="card-header fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-bank text-success"></i> Informasi Bank
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Nama Bank</label>
                            <input type="text" name="bank_name" class="form-control"
                                value="{{ old('bank_name', $settings['bank_name'] ?? '') }}"
                                placeholder="cth: BCA, Mandiri, BNI">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">No. Rekening</label>
                            <input type="text" name="bank_account_no" class="form-control"
                                value="{{ old('bank_account_no', $settings['bank_account_no'] ?? '') }}">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Atas Nama</label>
                            <input type="text" name="bank_account_name" class="form-control"
                                value="{{ old('bank_account_name', $settings['bank_account_name'] ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Invoice Config --}}
            <div class="card mb-4">
                <div class="card-header fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-receipt text-warning"></i> Konfigurasi Invoice
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Prefix Invoice <span class="text-danger">*</span></label>
                            <input type="text" name="invoice_prefix"
                                class="form-control @error('invoice_prefix') is-invalid @enderror"
                                value="{{ old('invoice_prefix', $settings['invoice_prefix'] ?? 'INV') }}"
                                maxlength="10" style="text-transform:uppercase">
                            <div class="form-text">Format: {{ old('invoice_prefix', $settings['invoice_prefix'] ?? 'INV') }}-2026-0001</div>
                            @error('invoice_prefix')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Jatuh Tempo Default (hari) <span class="text-danger">*</span></label>
                            <input type="number" name="default_due_days"
                                class="form-control @error('default_due_days') is-invalid @enderror"
                                value="{{ old('default_due_days', $settings['default_due_days'] ?? 14) }}"
                                min="1" max="365">
                            @error('default_due_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan Invoice Default</label>
                            <textarea name="invoice_notes" class="form-control" rows="3"
                                placeholder="Catatan yang muncul di bawah invoice PDF...">{{ old('invoice_notes', $settings['invoice_notes'] ?? '') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Syarat & Ketentuan</label>
                            <textarea name="terms_conditions" class="form-control" rows="5"
                                placeholder="Terms & Conditions yang muncul di invoice PDF...">{{ old('terms_conditions', $settings['terms_conditions'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Deposit Config --}}
            <div class="card mb-4">
                <div class="card-header fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-wallet2 text-info"></i> Pengaturan Deposit
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-5">
                            <label class="form-label">Batas Minimum Saldo Deposit (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" inputmode="numeric" name="deposit_low_threshold"
                                    class="form-control currency-input @error('deposit_low_threshold') is-invalid @enderror"
                                    value="{{ old('deposit_low_threshold', $settings['deposit_low_threshold'] ?? 1000000) }}">
                            </div>
                            <div class="form-text">Partner dengan saldo di bawah angka ini akan muncul sebagai peringatan.</div>
                            @error('deposit_low_threshold')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end mb-4">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i> Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelector('[name=invoice_prefix]')?.addEventListener('input', function() {
    const preview = this.closest('.col-12').querySelector('.form-text');
    if (preview) preview.textContent = `Format: ${this.value || 'INV'}-2026-0001`;
    this.value = this.value.toUpperCase();
});
</script>
@endpush
