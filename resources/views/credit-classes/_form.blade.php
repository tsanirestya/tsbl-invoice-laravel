@php $editing = isset($creditClass); @endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Nama Class <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $creditClass->name ?? '') }}" required placeholder="Contoh: Entry, Standard, Premium...">
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">Warna Badge <span class="text-danger">*</span></label>
        <select name="color" id="colorSelect" class="form-select @error('color') is-invalid @enderror" required>
            @foreach(['primary','secondary','success','warning','danger','info','dark'] as $c)
                <option value="{{ $c }}" @selected(old('color', $creditClass->color ?? '') === $c)>{{ ucfirst($c) }}</option>
            @endforeach
        </select>
        @error('color') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="mt-2">
            Preview: <span id="colorPreview" class="badge bg-primary">{{ old('name', $creditClass->name ?? 'Class') }}</span>
        </div>
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">Sort Order <span class="text-danger">*</span></label>
        <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror"
               value="{{ old('sort_order', $creditClass->sort_order ?? 0) }}" min="0" required>
        <div class="form-text">Urutan tampil — kecil = tampil pertama.</div>
        @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Min Credit Limit (Rp) <span class="text-danger">*</span></label>
        <input type="number" name="min_limit" class="form-control @error('min_limit') is-invalid @enderror"
               value="{{ old('min_limit', $creditClass->min_limit ?? 0) }}" min="0" step="1" required>
        @error('min_limit') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Max Credit Limit (Rp)</label>
        <input type="number" name="max_limit" class="form-control @error('max_limit') is-invalid @enderror"
               value="{{ old('max_limit', $creditClass->max_limit ?? '') }}" min="1" step="1"
               placeholder="Kosongkan = tidak terbatas">
        <div class="form-text">Kosongkan untuk class tanpa batas atas (Premium, dll).</div>
        @error('max_limit') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">Deskripsi</label>
        <input type="text" name="description" class="form-control @error('description') is-invalid @enderror"
               value="{{ old('description', $creditClass->description ?? '') }}" maxlength="255"
               placeholder="Opsional — keterangan singkat">
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

@push('scripts')
<script>
(function () {
    const sel   = document.getElementById('colorSelect');
    const badge = document.getElementById('colorPreview');
    const nameInput = document.querySelector('input[name="name"]');

    function sync() {
        badge.className = 'badge bg-' + sel.value;
        badge.textContent = nameInput.value || 'Class';
    }

    sel.addEventListener('change', sync);
    nameInput.addEventListener('input', sync);
    sync();
})();
</script>
@endpush
