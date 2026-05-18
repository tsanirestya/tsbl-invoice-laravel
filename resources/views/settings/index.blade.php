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

            {{-- Credit Config --}}
            <div class="card mb-4">
                <div class="card-header fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-graph-up-arrow text-danger"></i> Pengaturan Kredit
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Credit Warning Threshold <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="credit_warning_threshold"
                                    class="form-control @error('credit_warning_threshold') is-invalid @enderror"
                                    value="{{ old('credit_warning_threshold', $settings['credit_warning_threshold'] ?? 80) }}"
                                    min="1" max="100" id="creditThreshold">
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text">Utilisasi di atas ini → peringatan kuning di form invoice.</div>
                            @error('credit_warning_threshold')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Credit Aging Buckets <span class="text-danger">*</span></label>
                            <div class="row g-2 mb-2">
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted">Bucket 1 (hari)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="credit_aging_bucket_1" id="bucket1"
                                            class="form-control @error('credit_aging_bucket_1') is-invalid @enderror"
                                            value="{{ old('credit_aging_bucket_1', $settings['credit_aging_bucket_1'] ?? 30) }}"
                                            min="1" max="999">
                                        <span class="input-group-text">hr</span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted">Bucket 2 (hari)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="credit_aging_bucket_2" id="bucket2"
                                            class="form-control @error('credit_aging_bucket_2') is-invalid @enderror"
                                            value="{{ old('credit_aging_bucket_2', $settings['credit_aging_bucket_2'] ?? 60) }}"
                                            min="1" max="999">
                                        <span class="input-group-text">hr</span>
                                    </div>
                                    @error('credit_aging_bucket_2')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted">Bucket 3 (hari)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="credit_aging_bucket_3" id="bucket3"
                                            class="form-control @error('credit_aging_bucket_3') is-invalid @enderror"
                                            value="{{ old('credit_aging_bucket_3', $settings['credit_aging_bucket_3'] ?? 90) }}"
                                            min="1" max="999">
                                        <span class="input-group-text">hr</span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted">Bucket 4 (hari)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="credit_aging_bucket_4" id="bucket4"
                                            class="form-control @error('credit_aging_bucket_4') is-invalid @enderror"
                                            value="{{ old('credit_aging_bucket_4', $settings['credit_aging_bucket_4'] ?? 120) }}"
                                            min="1" max="999">
                                        <span class="input-group-text">hr</span>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-light border py-2 px-3 mb-0 small" id="agingPreview"></div>
                            <div class="form-text">Bucket harus urut: Bucket 1 &lt; Bucket 2 &lt; Bucket 3 &lt; Bucket 4.</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Admission Config --}}
            <div class="card mb-4">
                <div class="card-header fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-door-open text-info"></i> Pengaturan Admission
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Toleransi Visit Date (hari)</label>
                            <div class="input-group">
                                <input type="number" name="admission_visit_date_tolerance_days"
                                    class="form-control @error('admission_visit_date_tolerance_days') is-invalid @enderror"
                                    value="{{ old('admission_visit_date_tolerance_days', $settings['admission_visit_date_tolerance_days'] ?? 0) }}"
                                    min="0" max="7">
                                <span class="input-group-text">hari</span>
                            </div>
                            <div class="form-text">0 = hanya bisa redeem tepat di visit_date. 1 = bisa redeem H-1 sampai H+1.</div>
                            @error('admission_visit_date_tolerance_days')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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

        {{-- Dev Mode Toggle — ADMIN only, outside main form ────────────────── --}}
        @php $devModeOn = \App\Models\Setting::get('dev_mode_enabled', '0') === '1'; @endphp
        <div class="card mb-4 {{ $devModeOn ? 'border-warning' : 'border-0' }}">
            <div class="card-header fw-semibold d-flex align-items-center gap-2
                        {{ $devModeOn ? 'bg-warning-subtle' : '' }}">
                <i class="bi bi-bug{{ $devModeOn ? '-fill text-warning' : ' text-secondary' }}"></i>
                Development Mode
                @if($devModeOn)
                    <span class="badge bg-warning text-dark ms-1">AKTIF</span>
                @endif
            </div>
            <div class="card-body">
                @if($devModeOn)
                    <div class="alert alert-warning d-flex gap-2 align-items-start mb-3 py-2">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                        <div>
                            <strong>Dev Mode sedang AKTIF.</strong>
                            Halaman login menampilkan tombol quick-login untuk semua role.
                            <strong>Jangan biarkan aktif di production.</strong>
                        </div>
                    </div>
                @else
                    <p class="text-muted small mb-3">
                        Ketika aktif, halaman login menampilkan tombol quick-login untuk setiap role.
                        Berguna untuk testing — <strong>jangan aktifkan di production.</strong>
                    </p>
                @endif

                <form method="POST" action="{{ route('settings.dev-mode') }}">
                    @csrf
                    <button type="submit"
                            class="btn {{ $devModeOn ? 'btn-warning' : 'btn-outline-secondary' }} btn-sm"
                            @if(!$devModeOn)
                                onclick="return confirm('Aktifkan Dev Mode? Halaman login akan menampilkan tombol quick-login semua role.')"
                            @endif>
                        <i class="bi bi-{{ $devModeOn ? 'stop-circle' : 'play-circle' }} me-1"></i>
                        {{ $devModeOn ? 'Nonaktifkan Dev Mode' : 'Aktifkan Dev Mode' }}
                    </button>
                </form>
            </div>
        </div>
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

(function () {
    const ids = ['bucket1', 'bucket2', 'bucket3', 'bucket4'];
    const preview = document.getElementById('agingPreview');

    function updatePreview() {
        const [b1, b2, b3, b4] = ids.map(id => parseInt(document.getElementById(id)?.value) || 0);
        if (b1 && b2 && b3 && b4) {
            preview.innerHTML = `<i class="bi bi-info-circle me-1"></i><strong>Aging tampil sebagai:</strong> `
                + `Current | 1–${b1} hr | ${b1+1}–${b2} hr | ${b2+1}–${b3} hr | ${b3+1}–${b4} hr | >${b4} hr`;
            const invalid = !(b1 < b2 && b2 < b3 && b3 < b4);
            preview.className = 'alert border py-2 px-3 mb-0 small ' + (invalid ? 'alert-danger' : 'alert-light');
        }
    }

    ids.forEach(id => document.getElementById(id)?.addEventListener('input', updatePreview));
    updatePreview();
})();
</script>
@endpush
