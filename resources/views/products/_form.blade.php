<div class="row g-3">

    {{-- ── Identifikasi ─────────────────────────────────────────── --}}
    <div class="col-12">
        <div class="sect-label">Identifikasi Produk</div>
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">Nama Produk <span class="text-danger">*</span></label>
        <input type="text" name="product_name" class="form-control @error('product_name') is-invalid @enderror"
               value="{{ old('product_name', $product->product_name ?? '') }}" required>
        @error('product_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">DSI Code</label>
        <input type="text" name="dsi_code" class="form-control font-monospace @error('dsi_code') is-invalid @enderror"
               value="{{ old('dsi_code', $product->dsi_code ?? '') }}"
               placeholder="e.g. HTL-001-F-A-N">
        <div class="form-text">3 karakter kiri auto-isi Kategori saat import.</div>
        @error('dsi_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">Kategori</label>
        <input type="text" name="category" maxlength="10"
               class="form-control @error('category') is-invalid @enderror"
               value="{{ old('category', $product->category ?? '') }}"
               placeholder="HTL / TRD / TVL">
        @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">Partner Type</label>
        <input type="text" name="partner_type" maxlength="50"
               class="form-control @error('partner_type') is-invalid @enderror"
               value="{{ old('partner_type', $product->partner_type ?? '') }}"
               placeholder="e.g. Hotel, Travel, etc.">
        @error('partner_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">Deskripsi</label>
        <textarea name="description" class="form-control" rows="2">{{ old('description', $product->description ?? '') }}</textarea>
    </div>

    {{-- ── Harga ────────────────────────────────────────────────── --}}
    <div class="col-12 mt-1">
        <div class="sect-label">Harga</div>
    </div>

    <div class="col-md-3 col-6">
        <label class="form-label fw-semibold">Publish Rate <small class="text-muted fw-normal">(Rp)</small></label>
        <input type="text" inputmode="numeric" name="publish_rate"
               class="form-control currency-input @error('publish_rate') is-invalid @enderror"
               value="{{ old('publish_rate', $product->publish_rate ?? 0) }}">
        @error('publish_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3 col-6">
        <label class="form-label fw-semibold">Komisi <small class="text-muted fw-normal">(Rp)</small></label>
        <input type="text" inputmode="numeric" name="komisi"
               class="form-control currency-input @error('komisi') is-invalid @enderror"
               value="{{ old('komisi', $product->komisi ?? 0) }}">
        @error('komisi') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3 col-6">
        <label class="form-label fw-semibold">Nett Price <small class="text-muted fw-normal">(Rp)</small></label>
        <input type="text" inputmode="numeric" name="nett_price"
               class="form-control currency-input @error('nett_price') is-invalid @enderror"
               value="{{ old('nett_price', $product->nett_price ?? 0) }}">
        @error('nett_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3 col-6">
        <label class="form-label fw-semibold">Unit Price DSI <small class="text-muted fw-normal">(Rp)</small></label>
        <input type="text" inputmode="numeric" name="unit_price_dsi"
               class="form-control currency-input @error('unit_price_dsi') is-invalid @enderror"
               value="{{ old('unit_price_dsi', $product->unit_price_dsi ?? 0) }}">
        @error('unit_price_dsi') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Harga Default <small class="text-muted fw-normal">(Rp)</small> <span class="text-danger">*</span></label>
        <input type="text" inputmode="numeric" name="default_price"
               class="form-control currency-input @error('default_price') is-invalid @enderror"
               value="{{ old('default_price', $product->default_price ?? 0) }}" required>
        @error('default_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3 col-6">
        <label class="form-label fw-semibold">Satuan <span class="text-danger">*</span></label>
        <input type="text" name="unit" class="form-control @error('unit') is-invalid @enderror"
               value="{{ old('unit', $product->unit ?? 'Pax') }}" required placeholder="Pax / Night / Unit">
        @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- ── Klasifikasi ──────────────────────────────────────────── --}}
    <div class="col-12 mt-1">
        <div class="sect-label">Klasifikasi</div>
    </div>

    <div class="col-md-3 col-6">
        <label class="form-label fw-semibold">Market Type</label>
        <select name="market_type" class="form-select @error('market_type') is-invalid @enderror">
            <option value="">— Pilih —</option>
            <option value="foreign"  @selected(old('market_type',  $product->market_type  ?? '') === 'foreign')>Foreign</option>
            <option value="domestic" @selected(old('market_type',  $product->market_type  ?? '') === 'domestic')>Domestic</option>
        </select>
        @error('market_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3 col-6">
        <label class="form-label fw-semibold">Sub Market Type</label>
        <select name="sub_market_type" class="form-select @error('sub_market_type') is-invalid @enderror">
            <option value="">— Pilih —</option>
            <option value="adult" @selected(old('sub_market_type', $product->sub_market_type ?? '') === 'adult')>Adult</option>
            <option value="child" @selected(old('sub_market_type', $product->sub_market_type ?? '') === 'child')>Child</option>
        </select>
        @error('sub_market_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3 col-6">
        <label class="form-label fw-semibold">Sub Payment Mode</label>
        <select name="sub_payment_mode" class="form-select @error('sub_payment_mode') is-invalid @enderror">
            <option value="">— Pilih —</option>
            <option value="NETT"  @selected(old('sub_payment_mode', $product->sub_payment_mode ?? '') === 'NETT')>NETT</option>
            <option value="GROSS" @selected(old('sub_payment_mode', $product->sub_payment_mode ?? '') === 'GROSS')>GROSS</option>
        </select>
        @error('sub_payment_mode') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3 col-6">
        <label class="form-label fw-semibold">Payment Mode</label>
        <input type="text" name="payment_mode" maxlength="20"
               class="form-control @error('payment_mode') is-invalid @enderror"
               value="{{ old('payment_mode', $product->payment_mode ?? '') }}"
               placeholder="e.g. NETT, GROSS">
        @error('payment_mode') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- ── Status ───────────────────────────────────────────────── --}}
    <div class="col-12 mt-1">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                   id="isActive" @checked(old('is_active', $product->is_active ?? true))>
            <label class="form-check-label fw-semibold" for="isActive">Produk Aktif</label>
        </div>
    </div>

</div>
