@php $editing = isset($partner); @endphp

{{-- Nav tabs --}}
<ul class="nav nav-tabs mb-3" id="partnerTabs">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-info">Informasi Umum</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bank">Bank & Pembayaran</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-docs">Dokumen Legal</button>
    </li>
</ul>

<div class="tab-content">

    {{-- Tab: Info --}}
    <div class="tab-pane fade show active" id="tab-info">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Tipe Partner <span class="text-danger">*</span></label>
                <select name="partner_type" class="form-select @error('partner_type') is-invalid @enderror" required>
                    @foreach(['HOTEL','TRAVEL','TOURDESK'] as $type)
                        <option value="{{ $type }}" @selected(old('partner_type', $partner->partner_type ?? '') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
                @error('partner_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Nama Partner <span class="text-danger">*</span></label>
                <input type="text" name="nama_partner" class="form-control @error('nama_partner') is-invalid @enderror"
                       value="{{ old('nama_partner', $partner->nama_partner ?? '') }}" required>
                @error('nama_partner') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Nama PT</label>
                <input type="text" name="nama_pt" class="form-control"
                       value="{{ old('nama_pt', $partner->nama_pt ?? '') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Kategori</label>
                <input type="text" name="category" class="form-control"
                       value="{{ old('category', $partner->category ?? '') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Channel</label>
                <input type="text" name="channel" class="form-control"
                       value="{{ old('channel', $partner->channel ?? '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">PIC TSBL</label>
                <input type="text" name="pic_tsbl" class="form-control"
                       value="{{ old('pic_tsbl', $partner->pic_tsbl ?? '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">PIC Partner</label>
                <input type="text" name="pic_partner" class="form-control"
                       value="{{ old('pic_partner', $partner->pic_partner ?? '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Telepon PIC Partner</label>
                <input type="text" name="pic_partner_phone" class="form-control"
                       value="{{ old('pic_partner_phone', $partner->pic_partner_phone ?? '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Email PIC Partner</label>
                <input type="email" name="pic_partner_email" class="form-control @error('pic_partner_email') is-invalid @enderror"
                       value="{{ old('pic_partner_email', $partner->pic_partner_email ?? '') }}">
                @error('pic_partner_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Alamat</label>
                <textarea name="address" class="form-control" rows="2">{{ old('address', $partner->address ?? '') }}</textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">NPWP</label>
                <input type="text" name="npwp" class="form-control"
                       value="{{ old('npwp', $partner->npwp ?? '') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Kontrak Mulai</label>
                <input type="date" name="contract_start" class="form-control"
                       value="{{ old('contract_start', $partner->contract_start?->format('Y-m-d') ?? '') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Kontrak Selesai</label>
                <input type="date" name="contract_end" class="form-control @error('contract_end') is-invalid @enderror"
                       value="{{ old('contract_end', $partner->contract_end?->format('Y-m-d') ?? '') }}">
                @error('contract_end') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Catatan</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $partner->notes ?? '') }}</textarea>
            </div>

            <div class="col-md-6">
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                           id="isActive" @checked(old('is_active', $partner->is_active ?? true))>
                    <label class="form-check-label fw-semibold" for="isActive">Partner Aktif</label>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab: Bank --}}
    <div class="tab-pane fade" id="tab-bank">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Nama Bank</label>
                <input type="text" name="bank_name" class="form-control"
                       value="{{ old('bank_name', $partner->bank_name ?? '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">No. Rekening</label>
                <input type="text" name="bank_account_no" class="form-control"
                       value="{{ old('bank_account_no', $partner->bank_account_no ?? '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Atas Nama</label>
                <input type="text" name="bank_account_name" class="form-control"
                       value="{{ old('bank_account_name', $partner->bank_account_name ?? '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Tipe Pembayaran</label>
                <input type="text" name="payment_type" class="form-control"
                       placeholder="cth: Transfer, Tunai"
                       value="{{ old('payment_type', $partner->payment_type ?? '') }}">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Jatuh Tempo (hari) <span class="text-danger">*</span></label>
                <input type="number" name="payment_due_days" class="form-control @error('payment_due_days') is-invalid @enderror"
                       value="{{ old('payment_due_days', $partner->payment_due_days ?? 14) }}" min="0" required>
                @error('payment_due_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Limit Kredit (Rp) <span class="text-danger">*</span></label>
                <input type="number" name="limit_credit" class="form-control @error('limit_credit') is-invalid @enderror"
                       value="{{ old('limit_credit', $partner->limit_credit ?? 0) }}" min="0" step="1000" required>
                @error('limit_credit') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    {{-- Tab: Docs --}}
    <div class="tab-pane fade" id="tab-docs">
        <p class="text-muted small mb-3">Format: PDF, JPG, PNG. Maks 5MB per file.</p>
        <div class="row g-3">
            @php
                $docLabels = [
                    'doc_akta_pendirian' => 'Akta Pendirian',
                    'doc_akta_perubahan' => 'Akta Perubahan',
                    'doc_surat_kuasa'    => 'Surat Kuasa',
                    'doc_ktp'            => 'KTP',
                    'doc_nib'            => 'NIB',
                    'doc_npwp'           => 'NPWP',
                ];
            @endphp
            @foreach($docLabels as $field => $label)
            <div class="col-md-6">
                <label class="form-label fw-semibold">{{ $label }}</label>
                <input type="file" name="{{ $field }}"
                       class="form-control @error($field) is-invalid @enderror"
                       accept=".pdf,.jpg,.jpeg,.png">
                @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                @if($editing && isset($partner) && $partner->$field)
                    <div class="mt-1">
                        <a href="{{ Storage::url($partner->$field) }}" target="_blank" class="small text-primary">
                            <i class="bi bi-file-earmark me-1"></i>File saat ini
                        </a>
                    </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

</div>
