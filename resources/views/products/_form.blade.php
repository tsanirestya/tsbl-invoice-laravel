<div class="row g-3">
    <div class="col-12">
        <label class="form-label fw-semibold">Nama Produk <span class="text-danger">*</span></label>
        <input type="text" name="product_name" class="form-control @error('product_name') is-invalid @enderror"
               value="{{ old('product_name', $product->product_name ?? '') }}" required>
        @error('product_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">Deskripsi</label>
        <textarea name="description" class="form-control" rows="3">{{ old('description', $product->description ?? '') }}</textarea>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Harga Default (Rp) <span class="text-danger">*</span></label>
        <input type="text" inputmode="numeric" name="default_price" class="form-control currency-input @error('default_price') is-invalid @enderror"
               value="{{ old('default_price', $product->default_price ?? 0) }}" required>
        @error('default_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Satuan <span class="text-danger">*</span></label>
        <input type="text" name="unit" class="form-control @error('unit') is-invalid @enderror"
               value="{{ old('unit', $product->unit ?? 'Pax') }}" required placeholder="cth: Pax, Night, Unit">
        @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                   id="isActive" @checked(old('is_active', $product->is_active ?? true))>
            <label class="form-check-label fw-semibold" for="isActive">Produk Aktif</label>
        </div>
    </div>
</div>
