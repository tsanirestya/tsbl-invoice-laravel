{{-- Shared form partial for deposit invoices --}}
@php $editing = isset($depositInvoice); @endphp

<div class="row g-3">
    {{-- Partner --}}
    <div class="col-12">
        <label class="form-label fw-semibold" for="partner_id">
            Partner <span class="text-danger">*</span>
        </label>
        <select name="partner_id" id="partner_id"
                class="form-select @error('partner_id') is-invalid @enderror ts-partner">
            <option value="">— Pilih Partner —</option>
            @foreach($partners as $p)
                <option value="{{ $p->id }}"
                    {{ old('partner_id', $editing ? $depositInvoice->partner_id : request('partner_id')) == $p->id ? 'selected' : '' }}>
                    {{ $p->nama_partner }}
                </option>
            @endforeach
        </select>
        @error('partner_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Jumlah Deposit --}}
    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold" for="amount">
            Jumlah Deposit (Rp) <span class="text-danger">*</span>
        </label>
        <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="text" inputmode="numeric" name="amount" id="amount"
                   class="form-control currency-input @error('amount') is-invalid @enderror"
                   value="{{ old('amount', $editing ? (int)$depositInvoice->amount : '') }}"
                   placeholder="0">
            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="text-muted small mt-1">Masukkan jumlah deposit yang diminta dari partner</div>
    </div>

    {{-- Tanggal Invoice --}}
    <div class="col-12 col-md-3">
        <label class="form-label fw-semibold" for="invoice_date">
            Tanggal Invoice <span class="text-danger">*</span>
        </label>
        <input type="date" name="invoice_date" id="invoice_date"
               class="form-control @error('invoice_date') is-invalid @enderror"
               value="{{ old('invoice_date', $editing ? $depositInvoice->invoice_date?->toDateString() : now()->toDateString()) }}">
        @error('invoice_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Jatuh Tempo --}}
    <div class="col-12 col-md-3">
        <label class="form-label fw-semibold" for="due_date">
            Jatuh Tempo
        </label>
        <input type="date" name="due_date" id="due_date"
               class="form-control @error('due_date') is-invalid @enderror"
               value="{{ old('due_date', $editing ? $depositInvoice->due_date?->toDateString() : '') }}">
        @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="text-muted small mt-1">Kosongkan untuk otomatis ({{ $defaultDue ?? 14 }} hari)</div>
    </div>

    {{-- Catatan --}}
    <div class="col-12">
        <label class="form-label fw-semibold" for="notes">Catatan</label>
        <textarea name="notes" id="notes" rows="3"
                  class="form-control @error('notes') is-invalid @enderror"
                  placeholder="Catatan / keterangan tambahan (opsional)">{{ old('notes', $editing ? $depositInvoice->notes : '') }}</textarea>
        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('.ts-partner').forEach(el => {
        new TomSelect(el, { maxOptions: 200 });
    });

    // Auto-fill due date based on invoice_date
    const invoiceDateEl = document.getElementById('invoice_date');
    const dueDateEl = document.getElementById('due_date');
    const defaultDueDays = {{ $defaultDue ?? 14 }};

    invoiceDateEl.addEventListener('change', function () {
        if (!dueDateEl.value) {
            const d = new Date(this.value);
            d.setDate(d.getDate() + defaultDueDays);
            dueDateEl.value = d.toISOString().split('T')[0];
        }
    });
</script>
@endpush
