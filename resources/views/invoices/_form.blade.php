{{-- Shared form partial for create/edit invoice --}}
@php $invoice ??= null; @endphp

@push('styles')
<style>
    .item-row td { vertical-align: middle; }
    .item-row .btn-remove-row { opacity: .4; }
    .item-row:hover .btn-remove-row { opacity: 1; }
    #items-table tfoot td { background: #f8f9fa; }
    #finance-check-card { border-left: 4px solid #ffc107 !important; }

    /* Finance table hidden on mobile, toggle button shown */
    @media (max-width: 767.98px) {
        #finance-check-card { display: none !important; }
        #finance-toggle-btn { display: inline-flex !important; }
    }
    #finance-toggle-btn { display: none; }

    /* Readonly fields — locked from import data */
    .form-control[readonly],
    .form-control:read-only {
        background-color: #e8f4fd;
        border-color: #90c8f0;
        color: #1a5f8a;
        font-weight: 500;
        cursor: not-allowed;
    }
    .form-control[readonly]:focus,
    .form-control:read-only:focus {
        box-shadow: 0 0 0 .25rem rgba(13, 110, 253, .1);
        border-color: #90c8f0;
    }

    /* ── Import Source Card ───────────────────────────────────────── */
    .import-source-card {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(13, 110, 253, .12);
        border: 1px solid rgba(13, 110, 253, .18);
    }
    .isc-header {
        background: linear-gradient(135deg, #0d47a1 0%, #1976d2 60%, #42a5f5 100%);
        padding: 1rem 1.25rem;
        color: #fff;
    }
    .isc-icon {
        width: 44px; height: 44px;
        background: rgba(255,255,255,.2);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }
    .isc-title {
        font-weight: 700;
        font-size: 1rem;
        letter-spacing: .02em;
    }
    .isc-subtitle {
        font-size: .78rem;
        opacity: .82;
        margin-top: 1px;
    }
    .isc-badge-items {
        background: rgba(255,255,255,.22);
        border: 1px solid rgba(255,255,255,.35);
        color: #fff;
        font-size: .78rem;
        font-weight: 600;
        padding: .25rem .75rem;
        border-radius: 20px;
        white-space: nowrap;
        flex-shrink: 0;
    }

    /* Stats row */
    .isc-stats {
        background: #f0f7ff;
        display: flex;
        flex-wrap: wrap;
        gap: 0;
        border-bottom: 1px solid #d0e8fb;
    }
    .isc-stat {
        display: flex;
        align-items: center;
        gap: .6rem;
        padding: .65rem 1.1rem;
        flex: 1 1 180px;
        min-width: 0;
        border-right: 1px solid #d0e8fb;
    }
    .isc-stat:last-child { border-right: none; }
    .isc-stat > i {
        font-size: 1.1rem;
        color: #1976d2;
        flex-shrink: 0;
        opacity: .75;
    }
    .isc-stat-label {
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #6c757d;
        line-height: 1.2;
    }
    .isc-stat-value {
        font-size: .875rem;
        font-weight: 600;
        color: #1a1a2e;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .isc-filename {
        font-size: .78rem !important;
        font-weight: 500 !important;
        color: #495057 !important;
    }

    /* Item list */
    .isc-items {
        background: #fff;
    }
    .isc-items-header {
        font-size: .78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #1976d2;
        padding: .55rem 1.1rem;
        background: #e3f0fd;
        border-top: 1px solid #d0e8fb;
        border-bottom: 1px solid #d0e8fb;
    }
    .isc-items-body { padding: .25rem 0; }
    .isc-item-row {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .45rem 1.1rem;
        font-size: .84rem;
        border-bottom: 1px solid #f0f4f8;
        transition: background .12s;
    }
    .isc-item-row:last-child { border-bottom: none; }
    .isc-item-row:hover { background: #f5faff; }
    .isc-item-num {
        width: 20px; height: 20px;
        background: #1976d2;
        color: #fff;
        border-radius: 50%;
        font-size: .7rem;
        font-weight: 700;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .isc-item-name {
        flex: 1;
        font-weight: 500;
        color: #212529;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .isc-item-meta {
        display: flex;
        align-items: center;
        gap: .4rem;
        flex-shrink: 0;
        color: #495057;
        font-size: .82rem;
    }
    .isc-item-total {
        color: #1976d2;
        font-weight: 600;
    }
    @media (max-width: 575px) {
        .isc-stat { flex: 1 1 140px; padding: .5rem .75rem; }
        .isc-item-meta { flex-wrap: wrap; justify-content: flex-end; }
        .isc-item-total { width: 100%; text-align: right; }
    }


    /* Tom Select sizing tweak for table rows */
    .item-row .ts-wrapper.form-select { padding: 0; }
    .item-row .ts-control { font-size: .8rem; min-height: 31px; padding: .25rem .5rem; }
    .item-row .ts-dropdown { font-size: .8rem; }
    .ts-dropdown .ts-option-dsi { display: flex; align-items: center; gap: .4rem; padding: .3rem .5rem; }
    .ts-dropdown .ts-option-dsi .badge { font-size: .7rem; }

    /* Mobile: card layout — break table context with !important */
    @media (max-width: 575px) {
        #items-table                { display: block !important; width: 100% !important; }
        #items-table thead          { display: none !important; }
        #items-table tfoot          { display: none !important; }
        #items-table tbody          { display: block !important; width: 100% !important; }

        #items-table tbody tr.item-row {
            display: block !important;
            position: relative !important;
            width: 100% !important;
            border: 1px solid #dee2e6 !important;
            border-radius: .5rem !important;
            margin-bottom: .75rem !important;
            padding: .6rem !important;
            background: #fff !important;
            box-shadow: 0 1px 4px rgba(0,0,0,.08) !important;
            box-sizing: border-box !important;
        }

        /* Each cell: full-width block, label above */
        #items-table tbody tr.item-row td {
            display: block !important;
            width: 100% !important;
            border: none !important;
            padding: .2rem 0 !important;
            box-sizing: border-box !important;
        }

        /* Label above each field */
        #items-table tbody tr.item-row td[data-label]::before {
            content: attr(data-label) !important;
            display: block !important;
            font-size: .65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #6c757d;
            margin-bottom: .18rem;
        }

        /* Produk: pad-right agar tidak tertimpa tombol hapus */
        #items-table tbody tr.item-row td:nth-child(1) {
            padding-right: 2.5rem !important;
        }

        /* DSI: full width */
        #items-table tbody tr.item-row td:nth-child(2) { }

        /* Pax + Harga/Pax: side by side */
        #items-table tbody tr.item-row td:nth-child(3) {
            display: inline-block !important;
            width: 40% !important;
        }
        #items-table tbody tr.item-row td:nth-child(4) {
            display: inline-block !important;
            width: 58% !important;
            padding-left: .5rem !important;
        }
        #items-table tbody tr.item-row td:nth-child(3) input { text-align: center !important; width: 100% !important; }
        #items-table tbody tr.item-row td:nth-child(4) input { text-align: right !important;  width: 100% !important; }

        /* Jumlah: label kiri, angka kanan */
        #items-table tbody tr.item-row td:nth-child(5) {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: .35rem 0 .1rem !important;
            margin-top: .3rem;
            border-top: 1px dashed #dee2e6 !important;
        }
        #items-table tbody tr.item-row td:nth-child(5)::before { margin-bottom: 0 !important; }
        #items-table tbody tr.item-row td:nth-child(5) .item-amount {
            font-weight: 700 !important;
            color: #0d6efd !important;
            font-size: .95rem !important;
        }

        /* Delete: absolute top-right dalam card */
        #items-table tbody tr.item-row td:nth-child(6) {
            display: block !important;
            position: absolute !important;
            top: .4rem !important;
            right: .4rem !important;
            width: auto !important;
            padding: 0 !important;
        }

        /* Tom Select fill cell */
        #items-table tbody tr.item-row td .ts-wrapper { width: 100% !important; }

        .item-row .btn-remove-row { opacity: 1 !important; }
    }
</style>
@endpush

@php $importRow ??= null; $importRows ??= collect(); @endphp

{{-- ═══════════════════════════════════════════════════════════════
     Import Source Panel — Premium Card
═══════════════════════════════════════════════════════════════ --}}
@if($importRows->isNotEmpty())
@php
    $firstRow       = $importRows->first();
    $totalAmount    = $importRows->sum('total_amount');
    $totalKomisi    = $importRows->sum('komisi_amount');
    $itemCount      = $importRows->count();
@endphp

<div class="import-source-card mb-4">
    {{-- Header --}}
    <div class="isc-header d-flex align-items-center gap-3">
        <div class="isc-icon">
            <i class="bi bi-file-earmark-spreadsheet-fill"></i>
        </div>
        <div class="flex-grow-1">
            <div class="isc-title">Import Source</div>
            <div class="isc-subtitle">Data ditarik otomatis dari file transaksi DSI</div>
        </div>
        <span class="isc-badge-items">{{ $itemCount }} item</span>
    </div>

    {{-- Stats --}}
    <div class="isc-stats">
        <div class="isc-stat">
            <i class="bi bi-hash"></i>
            <div>
                <div class="isc-stat-label">No. Transaksi</div>
                <div class="isc-stat-value font-monospace">{{ $firstRow->transaction_no ?? '—' }}</div>
            </div>
        </div>
        <div class="isc-stat">
            <i class="bi bi-calendar-event"></i>
            <div>
                <div class="isc-stat-label">Tanggal Kunjungan</div>
                <div class="isc-stat-value">{{ $firstRow->date?->format('d M Y') ?? '—' }}</div>
            </div>
        </div>
        <div class="isc-stat">
            <i class="bi bi-cash-stack"></i>
            <div>
                <div class="isc-stat-label">Total Amount</div>
                <div class="isc-stat-value">Rp {{ number_format($totalAmount, 0, ',', '.') }}</div>
            </div>
        </div>
        @if($totalKomisi > 0)
        <div class="isc-stat">
            <i class="bi bi-percent"></i>
            <div>
                <div class="isc-stat-label">Total Komisi</div>
                <div class="isc-stat-value text-success">Rp {{ number_format($totalKomisi, 0, ',', '.') }}</div>
            </div>
        </div>
        @endif
        <div class="isc-stat">
            <i class="bi bi-file-earmark-text"></i>
            <div>
                <div class="isc-stat-label">File Import</div>
                <div class="isc-stat-value isc-filename">{{ $firstRow->import->original_filename ?? '—' }}</div>
            </div>
        </div>
    </div>

    {{-- Item list --}}
    @if($itemCount > 0)
    <div class="isc-items">
        <div class="isc-items-header">
            <i class="bi bi-list-ul me-1"></i> Daftar Item dalam Transaksi Ini
        </div>
        <div class="isc-items-body">
            @foreach($importRows as $idx => $r)
            <div class="isc-item-row">
                <span class="isc-item-num">{{ $idx + 1 }}</span>
                <span class="isc-item-name">{{ $r->ticket_name ?? '—' }}</span>
                <span class="isc-item-meta">
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border">{{ $r->qty }} pax</span>
                    <span class="text-muted">@</span>
                    <span class="fw-semibold">Rp {{ number_format($r->unit_price, 0, ',', '.') }}</span>
                    <span class="isc-item-total">= Rp {{ number_format($r->total_amount, 0, ',', '.') }}</span>
                </span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Hidden fields --}}
    <input type="hidden" name="import_row_id" value="{{ $firstRow->id }}">
    @if($firstRow->transaction_no)
    <input type="hidden" name="dsi_transaction_no_prefill" value="{{ $firstRow->transaction_no }}">
    @endif
</div>

@elseif($importRow)
<div class="import-source-card mb-4">
    <div class="isc-header d-flex align-items-center gap-3">
        <div class="isc-icon">
            <i class="bi bi-file-earmark-spreadsheet-fill"></i>
        </div>
        <div class="flex-grow-1">
            <div class="isc-title">Import Source</div>
            <div class="isc-subtitle">Data ditarik otomatis dari file transaksi DSI</div>
        </div>
        <span class="isc-badge-items">1 item</span>
    </div>
    <div class="isc-stats">
        <div class="isc-stat">
            <i class="bi bi-hash"></i>
            <div>
                <div class="isc-stat-label">No. Transaksi</div>
                <div class="isc-stat-value font-monospace">{{ $importRow->transaction_no ?? '—' }}</div>
            </div>
        </div>
        <div class="isc-stat">
            <i class="bi bi-calendar-event"></i>
            <div>
                <div class="isc-stat-label">Tanggal</div>
                <div class="isc-stat-value">{{ $importRow->date?->format('d M Y') ?? '—' }}</div>
            </div>
        </div>
        <div class="isc-stat">
            <i class="bi bi-ticket-detailed"></i>
            <div>
                <div class="isc-stat-label">Ticket</div>
                <div class="isc-stat-value">{{ $importRow->ticket_name ?? '—' }}</div>
            </div>
        </div>
        <div class="isc-stat">
            <i class="bi bi-person"></i>
            <div>
                <div class="isc-stat-label">Cashier</div>
                <div class="isc-stat-value">{{ $importRow->cashier ?? '—' }}</div>
            </div>
        </div>
        <div class="isc-stat">
            <i class="bi bi-cash-stack"></i>
            <div>
                <div class="isc-stat-label">Unit Price × Qty</div>
                <div class="isc-stat-value">Rp {{ number_format($importRow->unit_price, 0, ',', '.') }} × {{ $importRow->qty }}</div>
            </div>
        </div>
        @if($importRow->komisi_amount !== null)
        <div class="isc-stat">
            <i class="bi bi-percent"></i>
            <div>
                <div class="isc-stat-label">Komisi</div>
                <div class="isc-stat-value text-success">Rp {{ number_format($importRow->komisi_amount, 0, ',', '.') }}</div>
            </div>
        </div>
        @endif
    </div>
    <input type="hidden" name="import_row_id" value="{{ $importRow->id }}">
    @if($importRow->transaction_no)
    <input type="hidden" name="dsi_transaction_no_prefill" value="{{ $importRow->transaction_no }}">
    @endif
</div>
@endif


{{-- Invoice header — full width --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-bottom fw-semibold py-2">Info Invoice</div>
    <div class="card-body">
        {{-- Partner --}}
        <div class="mb-3">
            <label class="form-label fw-semibold">Partner <span class="text-danger">*</span></label>
            <select name="partner_id" id="partner_id" class="form-select @error('partner_id') is-invalid @enderror" required>
                <option value=""></option>
                @foreach($partners as $p)
                    <option value="{{ $p->id }}"
                            data-due="{{ $p->payment_due_days }}"
                            @selected(old('partner_id', $invoice?->partner_id ?? '') == $p->id)>
                        {{ $p->nama_partner }} ({{ $p->partner_type }})
                    </option>
                @endforeach
            </select>
            @error('partner_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="row g-2">
            {{-- Guest Name --}}
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Nama Tamu <span class="text-danger">*</span></label>
                <input type="text" name="guest_name" class="form-control @error('guest_name') is-invalid @enderror"
                       value="{{ old('guest_name', $invoice?->guest_name ?? '') }}" maxlength="200" required>
                @error('guest_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            {{-- Booking Pass --}}
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Booking Pass No <span class="text-danger">*</span></label>
                <input type="text" name="booking_pass_no" class="form-control @error('booking_pass_no') is-invalid @enderror"
                       value="{{ old('booking_pass_no', $invoice?->booking_pass_no ?? '') }}" maxlength="100" required>
                @error('booking_pass_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            {{-- DSI --}}
            @php
                $fromImport   = $importRows->isNotEmpty() || $importRow;
                $trxNoValue   = old('dsi_transaction_no',
                    $invoice?->dsi_transaction_no
                    ?? $importRows->first()?->transaction_no
                    ?? $importRow?->transaction_no
                    ?? ''
                );
            @endphp
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">No. Transaksi DSI</label>
                <input type="text" name="dsi_transaction_no" class="form-control @error('dsi_transaction_no') is-invalid @enderror"
                       value="{{ $trxNoValue }}" maxlength="100"
                       {{ $fromImport ? 'readonly' : '' }}>
                @if($fromImport)
                    <div class="form-text text-muted"><i class="bi bi-lock-fill me-1"></i>Terisi otomatis dari data transaksi</div>
                @endif
                @error('dsi_transaction_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            {{-- Visit Date --}}
            @php
                $visitDateValue = old('visit_date',
                    $invoice?->visit_date?->format('Y-m-d')
                    ?? $importRows->first()?->date?->format('Y-m-d')
                    ?? $importRow?->date?->format('Y-m-d')
                    ?? ''
                );
            @endphp
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Tanggal Kunjungan <span class="text-danger">*</span></label>
                <input type="date" name="visit_date" class="form-control @error('visit_date') is-invalid @enderror"
                       value="{{ $visitDateValue }}" required
                       {{ $fromImport ? 'readonly' : '' }}>
                @if($fromImport)
                    <div class="form-text text-muted"><i class="bi bi-lock-fill me-1"></i>Terisi otomatis dari data transaksi</div>
                @endif
                @error('visit_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            {{-- Invoice Date --}}
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Tgl Invoice <span class="text-danger">*</span></label>
                <input type="date" name="invoice_date" id="invoice_date" class="form-control @error('invoice_date') is-invalid @enderror"
                       value="{{ old('invoice_date', $invoice?->invoice_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
                @error('invoice_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            {{-- Due Date --}}
            <div class="col-12 col-md-4">
                <label class="form-label">Jatuh Tempo</label>
                <input type="date" name="due_date" id="due_date" class="form-control @error('due_date') is-invalid @enderror"
                       value="{{ old('due_date', $invoice?->due_date?->format('Y-m-d') ?? '') }}">
                <div class="form-text">Kosongkan = auto dari partner due days</div>
                @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- Notes --}}
        <div class="mt-2">
            <label class="form-label">Catatan</label>
            <textarea name="notes" class="form-control" rows="2" maxlength="1000">{{ old('notes', $invoice?->notes ?? '') }}</textarea>
        </div>
    </div>
</div>

{{-- Credit Status Panel — appears via JS when partner with credit limit is selected --}}
<div id="credit-panel" class="mb-3"></div>

{{-- Override reason — shown by JS when projected usage > credit limit --}}
<div id="credit-override-wrap" class="mb-3" style="{{ $errors->has('credit_override_reason') ? '' : 'display:none' }}">
    <div class="alert alert-danger border-danger p-3 mb-0">
        <div class="fw-semibold mb-2 small">
            <i class="bi bi-exclamation-octagon-fill me-1"></i>
            Credit Limit Terlampaui — Alasan Override Wajib Diisi
        </div>
        <textarea name="credit_override_reason" id="credit-override-reason"
                  class="form-control @error('credit_override_reason') is-invalid @enderror"
                  rows="2" maxlength="500"
                  placeholder="Jelaskan alasan mengapa invoice ini melebihi credit limit partner...">{{ old('credit_override_reason', $invoice?->credit_override_reason ?? '') }}</textarea>
        @error('credit_override_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

{{-- Hidden deposit field always submitted --}}
<input type="hidden" name="deposit" id="deposit" value="{{ old('deposit', $invoice?->deposit ?? 0) }}">

{{-- Line Items --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold">Item Invoice</span>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow()">
            <i class="bi bi-plus-lg me-1"></i> Tambah Baris
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0" id="items-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:33%">Produk / Layanan <span class="text-danger">*</span></th>
                        <th style="width:22%">DSI Code</th>
                        <th class="text-center" style="width:10%">Pax</th>
                        <th class="text-end" style="width:17%">Harga/Pax</th>
                        <th class="text-end" style="width:15%">Jumlah</th>
                        <th style="width:3%"></th>
                    </tr>
                </thead>
                <tbody id="items-body">
                    @php
                        $existingItems = old('items',
                            isset($invoice) && isset($invoice->items) && is_iterable($invoice->items)
                                ? $invoice->items->toArray()
                                : []
                        );
                    @endphp
                    @forelse($existingItems as $i => $item)
                    @php $existingProductId = $item['product_id'] ?? ''; @endphp
                    @php $existingName = $item['product_name'] ?? ''; @endphp
                    <tr class="item-row" data-product-id="{{ $existingProductId }}">
                        <td class="ps-3" data-label="Produk">
                            <select name="items[{{ $i }}][product_name]"
                                    class="form-select form-select-sm product-name-picker @error("items.{$i}.product_name") is-invalid @enderror"
                                    required>
                                <option value=""></option>
                                @foreach($products as $prod)
                                    <option value="{{ $prod->product_name }}"
                                            @selected($existingName === $prod->product_name)>
                                        {{ $prod->product_name }}
                                    </option>
                                @endforeach
                                @if($existingName && !$products->contains('product_name', $existingName))
                                    <option value="{{ $existingName }}" selected>{{ $existingName }}</option>
                                @endif
                            </select>
                            @error("items.{$i}.product_name")<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </td>
                        <td data-label="DSI">
                            <input type="hidden" name="items[{{ $i }}][product_id]" class="item-product-id" value="{{ $existingProductId }}">
                            <select class="form-select form-select-sm product-picker">
                                <option value=""></option>
                                @foreach($products as $prod)
                                    <option value="{{ $prod->id }}"
                                            data-price="{{ $prod->nett_price }}"
                                            data-name="{{ $prod->product_name }}"
                                            data-product-name="{{ $prod->product_name }}"
                                            @selected($existingProductId == $prod->id)>
                                        {{ $prod->dsi_code }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td class="text-center" data-label="Pax">
                            <input type="number" name="items[{{ $i }}][pax]"
                                   class="form-control form-control-sm text-center item-pax @error("items.{$i}.pax") is-invalid @enderror"
                                   value="{{ $item['pax'] ?? 1 }}" min="1" required oninput="recalcRow(this)">
                        </td>
                        <td class="text-end" data-label="Harga/Pax">
                            <input type="text" inputmode="numeric" name="items[{{ $i }}][price_per_pax]"
                                   class="form-control form-control-sm text-end item-price currency-input @error("items.{$i}.price_per_pax") is-invalid @enderror"
                                   value="{{ $item['price_per_pax'] ?? 0 }}" required oninput="recalcRow(this)">
                        </td>
                        <td class="text-end" data-label="Jumlah">
                            <span class="item-amount text-nowrap">{{ number_format(($item['pax'] ?? 1) * ($item['price_per_pax'] ?? 0), 0, ',', '.') }}</span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-link text-danger btn-remove-row p-0" onclick="removeRow(this)">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    {{-- default blank row injected by JS --}}
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end fw-semibold ps-3">Subtotal</td>
                        <td class="text-end"><span id="foot-subtotal">0</span></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @error('items')<div class="alert alert-danger m-3 py-2">{{ $message }}</div>@enderror
    </div>
</div>

{{-- Finance Double Check — desktop only; toggle on mobile --}}
<button type="button" id="finance-toggle-btn"
        class="btn btn-outline-warning btn-sm mb-3 align-items-center gap-1"
        onclick="document.getElementById('finance-check-card').style.display='block';this.style.display='none';">
    <i class="bi bi-calculator"></i> Tampilkan Pengecekan Finance
</button>
<div class="card border-0 shadow-sm mb-3" id="finance-check-card">
    <div class="card-header bg-warning-subtle border-bottom d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold"><i class="bi bi-calculator me-1"></i>Pengecekan Finance</span>
        <span class="badge bg-warning text-dark">Internal — tidak muncul di invoice</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0" id="finance-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:4%">#</th>
                        <th style="width:11%">DSI Code</th>
                        <th style="width:22%">Produk</th>
                        <th class="text-center" style="width:6%">Pax</th>
                        <th class="text-end" style="width:14%">Publish Rate</th>
                        <th class="text-end" style="width:14%">Total Publish</th>
                        <th class="text-end" style="width:12%">Komisi</th>
                        <th class="text-end" style="width:12%">Nett Price</th>
                        <th class="text-end" style="width:9%">% Komisi</th>
                    </tr>
                </thead>
                <tbody id="finance-body">
                    <tr id="finance-empty-row">
                        <td colspan="9" class="text-center text-muted py-3 ps-3">Pilih produk di atas untuk melihat kalkulasi</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="table-light fw-semibold">
                        <td colspan="5" class="text-end ps-3">Total</td>
                        <td class="text-end" id="finance-total-publish">—</td>
                        <td class="text-end" id="finance-total-komisi">—</td>
                        <td class="text-end" id="finance-total-nett">—</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- Ringkasan & Grand Total — below Finance Check --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-bottom fw-semibold py-2">
        <i class="bi bi-receipt me-1 text-primary"></i>Ringkasan Tagihan
    </div>
    <div class="card-body">
        <div class="row align-items-start g-3">
            {{-- Deposit panel (left column) --}}
            <div class="col-12 col-md-7">
                <div id="deposit-panel"></div>
            </div>
            {{-- Totals (right column) --}}
            <div class="col-12 col-md-5">
                <div class="bg-light rounded p-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal</span>
                        <span id="summary-subtotal" class="fw-semibold">Rp 0</span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold fs-6">Grand Total</span>
                        <span id="summary-grand" class="fw-bold fs-5 text-primary">Rp 0</span>
                    </div>
                    <div id="summary-deposit-info" class="text-muted small mt-2 p-2 bg-white rounded border" style="display:none">
                        <div class="d-flex justify-content-between">
                            <span><i class="bi bi-wallet2 me-1"></i>Dibayar via Deposit</span>
                            <span class="fw-semibold text-success" id="summary-deposit-val">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <span>Sisa Tagihan</span>
                            <span class="fw-semibold" id="summary-deposit-sisa">Rp 0</span>
                        </div>
                    </div>
                    <div id="summary-terbilang" class="text-muted small fst-italic mt-2"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@php
$productsJs = $products->map(function($p) {
    return [
        'id'           => $p->id,
        'dsi_code'     => $p->dsi_code,
        'name'         => $p->product_name,
        'price'        => (float) $p->nett_price,
        'publish_rate' => (float) $p->publish_rate,
        'komisi'       => (float) $p->komisi,
        'nett_price'   => (float) $p->nett_price,
    ];
})->values();
@endphp
<script>
const products = @json($productsJs);
@php
// Build prefill array for JS:
// Jika ada $importRows (multi-item dari 1 transaksi), gunakan semua.
// Jika ada $importRow tunggal (legacy), bungkus ke array 1 item.
$importPrefillItems = null;
if (!old('items')) {
    if ($importRows->isNotEmpty()) {
        $importPrefillItems = $importRows->map(fn($r) => [
            'product_id'   => $r->matched_product_id,
            'product_name' => $r->ticket_name,
            'pax'          => (int) $r->qty,
            'price'        => (float) $r->unit_price,
        ])->values()->all();
    } elseif ($importRow) {
        $importPrefillItems = [[
            'product_id'   => $importRow->matched_product_id,
            'product_name' => $importRow->ticket_name,
            'pax'          => (int) $importRow->qty,
            'price'        => (float) $importRow->unit_price,
        ]];
    }
}
@endphp
const importPrefillItems = @json($importPrefillItems);
{{-- backward compat --}}
const importPrefill = importPrefillItems ? importPrefillItems[0] : null;
let rowIdx = {{ count($existingItems) }};
const defaultDue = {{ $defaultDue ?? 14 }};
const depositBalanceBaseUrl = '{{ url('/api/partners') }}';
const depositTopupBaseUrl   = '{{ url('/partners') }}';
const creditApiBaseUrl      = '{{ url('/api/partners') }}';
const creditWarningThreshold = {{ (float) \App\Models\Setting::get('credit_warning_threshold', 80) }};
// In edit mode: current invoice grand_total is included in creditUsed() → subtract it for projection
const currentInvoiceGrandTotal = {{ ($invoice && in_array($invoice->payment_status ?? '', ['UNPAID', 'PARTIAL', 'OVERDUE'])) ? (float) $invoice->grand_total : 0 }};

function fmt(n) {
    return new Intl.NumberFormat('id-ID').format(Math.round(n));
}

// ── Tom Select: product NAME picker ────────────────────────────────────────
function makeNamePicker(sel) {
    const row = sel.closest('tr');
    const ts = new TomSelect(sel, {
        create: true,
        createOnBlur: true,
        placeholder: '— Pilih atau ketik nama layanan —',
        dropdownParent: 'body',
        searchField: ['text'],
        onChange: function(value) {
            pickProductByName(row, value);
        }
    });
    if (row) row._tsName = ts;
    return ts;
}

// ── Tom Select: DSI code picker ────────────────────────────────────────────
function makePicker(sel) {
    const row = sel.closest('tr');
    const ts = new TomSelect(sel, {
        placeholder: '— Ketik DSI Code —',
        dropdownParent: 'body',
        searchField: ['text', 'product_name'],
        render: {
            option: function(data, escape) {
                const name = escape(data.product_name || '');
                const code = escape(data.text || '');
                return `<div class="ts-option-dsi">
                    <span class="badge bg-secondary font-monospace">${code}</span>
                    <span class="text-muted">${name}</span>
                </div>`;
            },
            item: function(data, escape) {
                const name = escape(data.product_name || '');
                const code = escape(data.text || '');
                return `<div class="d-flex align-items-center gap-1">
                    <span class="badge bg-secondary font-monospace">${code}</span>
                    <small class="text-muted">${name}</small>
                </div>`;
            },
            no_results: function(data, escape) {
                return `<div class="no-results px-2 py-1">Tidak ditemukan: "${escape(data.input)}"</div>`;
            }
        },
        onChange: function(value) {
            pickProduct(row, value);
        }
    });
    if (row) row._tsDsi = ts;
    return ts;
}

// When DSI code selected → sync name picker + price
function pickProduct(row, value) {
    const idInput = row.querySelector('.item-product-id');
    if (idInput) idInput.value = value || '';
    row.dataset.productId = value || '';

    if (value) {
        const prod = products.find(p => p.id == value);
        if (prod) {
            const tsName = row._tsName;
            if (tsName) {
                if (!tsName.options[prod.name]) {
                    tsName.addOption({ value: prod.name, text: prod.name });
                }
                tsName.setValue(prod.name, true); // silent — no loop
            }
            row.querySelector('.item-price').value = fmtCurrency(prod.price);
            recalcRow(row.querySelector('.item-pax'));
            return;
        }
    }
    const tsName = row._tsName;
    if (tsName) tsName.setValue('', true);
    refreshFinance();
}

// When product name selected → sync DSI picker + price
function pickProductByName(row, value) {
    const prod = products.find(p => p.name === value);
    const idInput = row.querySelector('.item-product-id');
    if (idInput) idInput.value = prod ? prod.id : '';
    row.dataset.productId = prod ? prod.id : '';

    if (prod) {
        const tsDsi = row._tsDsi;
        if (tsDsi) tsDsi.setValue(String(prod.id), true); // silent — no loop
        row.querySelector('.item-price').value = fmtCurrency(prod.price);
        recalcRow(row.querySelector('.item-pax'));
    } else {
        // Custom name — clear DSI
        const tsDsi = row._tsDsi;
        if (tsDsi) tsDsi.setValue('', true);
        refreshFinance();
    }
}

// ── Tom Select: partner picker ─────────────────────────────────────────────
function makePartnerPicker() {
    const sel = document.getElementById('partner_id');
    if (!sel) return;
    new TomSelect(sel, {
        placeholder: '— Ketik nama partner —',
        searchField: ['text'],
        onChange: function(value) {
            applyDueDate(value);
            if (value) {
                loadDepositInfo(value);
                loadCreditInfo(value);
            } else {
                clearDepositPanel();
                clearCreditPanel();
            }
        }
    });
}

function applyDueDate(partnerId) {
    const sel = document.getElementById('partner_id');
    const opt = Array.from(sel.options).find(o => o.value == partnerId);
    if (!opt) return;
    const due  = parseInt(opt.dataset.due || defaultDue);
    const base = document.getElementById('invoice_date').value;
    if (base && due) {
        const d = new Date(base);
        d.setDate(d.getDate() + due);
        document.getElementById('due_date').value = d.toISOString().split('T')[0];
    }
}

// ── Deposit panel ──────────────────────────────────────────────────────────
let depositBalance = 0;

async function loadDepositInfo(partnerId) {
    try {
        const res  = await fetch(`${depositBalanceBaseUrl}/${partnerId}/deposit-balance`);
        const data = await res.json();
        depositBalance = data.balance;
        renderDepositPanel(data);
    } catch(e) {
        clearDepositPanel();
    }
}

function clearDepositPanel() {
    depositBalance = 0;
    document.getElementById('deposit-panel').innerHTML = '';
    setDepositAmount(0);
}

function setDepositAmount(val) {
    document.getElementById('deposit').value = val;
    recalc();
}

function renderDepositPanel(data) {
    const panel = document.getElementById('deposit-panel');
    const currentDeposit = parseFloat(document.getElementById('deposit').value) || 0;

    // Scenario C: never had deposit (balance=0 and no existing deposit on this invoice)
    if (data.balance <= 0 && currentDeposit === 0) {
        // Scenario D: truly empty (partner has no deposit at all)
        if (data.is_empty) {
            panel.innerHTML = `
            <div class="alert alert-danger py-2 mb-0 small">
                <i class="bi bi-x-circle-fill me-1"></i>
                <strong>Saldo Deposit Habis</strong><br>
                Partner belum memiliki saldo deposit.
                <a href="${depositTopupBaseUrl}/${data.partner_id}/deposits/topup" target="_blank" class="alert-link">Top-up sekarang ↗</a>
            </div>`;
            setDepositAmount(0);
            return;
        }
        panel.innerHTML = '';
        setDepositAmount(0);
        return;
    }

    // Saldo yang tersedia (termasuk deposit yang sudah dipakai di invoice ini — reverse pada edit)
    const available = data.balance;

    if (data.is_empty && currentDeposit === 0) {
        panel.innerHTML = `
        <div class="alert alert-danger py-2 mb-0 small">
            <i class="bi bi-x-circle-fill me-1"></i>
            <strong>Saldo Deposit Habis.</strong>
            <a href="${depositTopupBaseUrl}/${data.partner_id}/deposits/topup" target="_blank" class="alert-link">Top-up sekarang ↗</a>
        </div>`;
        setDepositAmount(0);
        return;
    }

    const warningHtml = data.is_low ? `
        <div class="alert alert-warning py-1 mb-2 small">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            Saldo hampir habis (threshold: Rp ${fmt(data.threshold)}). Mohon informasikan partner untuk segera top-up.
        </div>` : '';

    // Re-use existing deposit value or auto-fill
    const inputVal = currentDeposit > 0 ? currentDeposit : '';

    panel.innerHTML = `
        ${warningHtml}
        <div class="border rounded p-2 bg-light mb-1">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-muted fw-semibold">Saldo Deposit Partner</small>
                <small class="fw-bold ${data.is_low ? 'text-warning' : 'text-success'}">Rp ${fmt(available)}</small>
            </div>
            <div class="form-check mb-1">
                <input class="form-check-input" type="checkbox" id="deposit-use-chk" ${inputVal ? 'checked' : ''}>
                <label class="form-check-label small" for="deposit-use-chk">Gunakan pembayaran deposit</label>
            </div>
            <div id="deposit-amount-wrap" style="display:${inputVal ? 'block' : 'none'}">
                <div class="input-group input-group-sm">
                    <span class="input-group-text">Rp</span>
                    <input type="text" inputmode="numeric" id="deposit-amount-input" class="form-control currency-input"
                           value="${fmtCurrency(inputVal)}"
                           placeholder="Maks: Rp ${fmt(available)}">
                </div>
                <div class="form-text">Maks: Rp ${fmt(available)}</div>
            </div>
        </div>`;
    initCurrencyInputs(panel);

    document.getElementById('deposit-use-chk').addEventListener('change', function() {
        const wrap = document.getElementById('deposit-amount-wrap');
        if (this.checked) {
            wrap.style.display = 'block';
            const subtotal = getCurrentSubtotal();
            const max      = Math.min(available, subtotal);
            document.getElementById('deposit-amount-input').value = fmtCurrency(max);
            setDepositAmount(max);
        } else {
            wrap.style.display = 'none';
            setDepositAmount(0);
        }
    });

    document.getElementById('deposit-amount-input')?.addEventListener('input', function() {
        const subtotal = getCurrentSubtotal();
        const max      = Math.min(available, subtotal);
        let val        = parseRaw(this.value);
        if (val > max) { val = max; this.value = fmtCurrency(max); }
        setDepositAmount(val);
    });

    // Set initial deposit
    if (inputVal) setDepositAmount(parseFloat(inputVal));
}

function getCurrentSubtotal() {
    let s = 0;
    document.querySelectorAll('#items-body .item-row').forEach(row => {
        const pax   = parseFloat(row.querySelector('.item-pax')?.value) || 0;
        const price = parseRaw(row.querySelector('.item-price')?.value);
        s += pax * price;
    });
    return s;
}

// ── Credit panel ──────────────────────────────────────────────────────────
let creditData = null;

async function loadCreditInfo(partnerId) {
    try {
        const res = await fetch(`${creditApiBaseUrl}/${partnerId}/credit-info`);
        creditData = await res.json();
    } catch(e) {
        creditData = null;
    }
    updateCreditFromSubtotal();
}

function clearCreditPanel() {
    creditData = null;
    document.getElementById('credit-panel').innerHTML = '';
    setOverLimit(false);
}

function updateCreditFromSubtotal() {
    const panel = document.getElementById('credit-panel');
    if (!creditData || creditData.limit <= 0) {
        panel.innerHTML = '';
        setOverLimit(false);
        return;
    }

    const subtotal   = getCurrentSubtotal();
    // Subtract current invoice's grand_total from base (edit mode: it's already in creditUsed)
    const baseUsed   = creditData.used - currentInvoiceGrandTotal;
    const projUsed   = baseUsed + subtotal;
    const projUtil   = creditData.limit > 0 ? (projUsed / creditData.limit * 100) : 0;

    let borderColor = 'success';
    let statusClass = 'success';
    let statusText  = 'Normal';
    let bannerHtml  = '';

    if (projUtil > 100) {
        borderColor = 'danger';
        statusClass = 'danger';
        statusText  = 'Over Limit';
        bannerHtml  = `<div class="alert alert-danger py-2 mt-2 mb-0 small">
            <i class="bi bi-exclamation-octagon-fill me-1"></i>
            <strong>Credit limit akan terlampaui!</strong>
            Proyeksi terpakai: <strong>Rp ${fmt(projUsed)}</strong> dari limit <strong>Rp ${fmt(creditData.limit)}</strong>
            (${projUtil.toFixed(1)}%). Alasan override wajib diisi di bawah.
        </div>`;
        setOverLimit(true);
    } else if (projUtil >= creditWarningThreshold) {
        borderColor = 'warning';
        statusClass = 'warning text-dark';
        statusText  = 'Warning';
        bannerHtml  = `<div class="alert alert-warning py-2 mt-2 mb-0 small">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            Utilisasi kredit partner akan mencapai <strong>${projUtil.toFixed(1)}%</strong> setelah invoice ini ditambahkan.
        </div>`;
        setOverLimit(false);
    } else {
        setOverLimit(false);
    }

    const barColor  = projUtil > 100 ? 'danger' : (projUtil >= creditWarningThreshold ? 'warning' : 'success');
    const barWidth  = Math.min(projUtil, 100);
    const textColor = projUtil > 100 ? 'text-danger' : (projUtil >= creditWarningThreshold ? 'text-warning' : 'text-success');
    const classHtml = creditData.credit_class_name
        ? `<span class="badge bg-${creditData.credit_class_color} text-dark ms-1">${creditData.credit_class_name}</span>`
        : '';

    panel.innerHTML = `
        <div class="card border-${borderColor} shadow-sm" style="border-width:2px!important">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center py-2">
                <span class="small"><i class="bi bi-shield-check me-1"></i>Status Kredit Partner${classHtml}</span>
                <span class="badge bg-${statusClass}">${statusText}</span>
            </div>
            <div class="card-body py-2">
                <div class="row g-2 text-center mb-2">
                    <div class="col-4">
                        <div class="p-2 bg-light rounded small">
                            <div class="text-muted">Limit</div>
                            <div class="fw-bold">Rp ${fmt(creditData.limit)}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-light rounded small">
                            <div class="text-muted">Terpakai saat ini</div>
                            <div class="fw-bold">Rp ${fmt(baseUsed < 0 ? 0 : baseUsed)}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-light rounded small">
                            <div class="text-muted">Setelah invoice ini</div>
                            <div class="fw-bold ${textColor}">Rp ${fmt(projUsed)}</div>
                        </div>
                    </div>
                </div>
                <div class="mb-1 d-flex justify-content-between small">
                    <span class="text-muted">Proyeksi Utilisasi</span>
                    <span class="fw-semibold ${textColor}">${projUtil.toFixed(1)}%</span>
                </div>
                <div class="progress" style="height:6px">
                    <div class="progress-bar bg-${barColor}" style="width:${barWidth}%"></div>
                </div>
                ${bannerHtml}
            </div>
        </div>`;
}

function setOverLimit(isOver) {
    const wrap = document.getElementById('credit-override-wrap');
    if (wrap) wrap.style.display = isOver ? '' : 'none';
}

// ── Invoice date change → recalc due date ─────────────────────────────────
document.getElementById('invoice_date')?.addEventListener('change', function() {
    const partnerSel = document.getElementById('partner_id');
    if (partnerSel?.value) applyDueDate(partnerSel.value);
});

// ── Row management ─────────────────────────────────────────────────────────
function addRow(name = '', productId = '', pax = 1, price = 0, lockPicker = false) {
    const i = rowIdx++;
    const opts = products.map(p =>
        `<option value="${p.id}"
            data-price="${p.price}"
            data-name="${p.name}"
            data-product-name="${p.name}"
            ${p.id == productId ? 'selected' : ''}>${p.dsi_code}</option>`
    ).join('');
    const nameOpts = products.map(p =>
        `<option value="${p.name}" ${p.name === name ? 'selected' : ''}>${p.name}</option>`
    ).join('');
    const isCustomName = name && !products.find(p => p.name === name);
    const customOpt    = isCustomName ? `<option value="${name}" selected>${name}</option>` : '';

    const amount = pax * price;
    const tbody  = document.getElementById('items-body');
    const tr     = document.createElement('tr');
    tr.className = 'item-row';
    tr.dataset.productId = productId;
    tr.innerHTML = `
        <td class="ps-3" data-label="Produk">
            <select name="items[${i}][product_name]" class="form-select form-select-sm product-name-picker" required>
                <option value=""></option>${nameOpts}${customOpt}
            </select>
        </td>
        <td data-label="DSI">
            <input type="hidden" name="items[${i}][product_id]" class="item-product-id" value="${productId}">
            <select class="form-select form-select-sm product-picker">
                <option value=""></option>${opts}
            </select>
        </td>
        <td class="text-center" data-label="Pax">
            <input type="number" name="items[${i}][pax]" class="form-control form-control-sm text-center item-pax"
                   value="${pax}" min="1" required oninput="recalcRow(this)">
        </td>
        <td class="text-end" data-label="Harga/Pax">
            <input type="text" inputmode="numeric" name="items[${i}][price_per_pax]" class="form-control form-control-sm text-end item-price currency-input"
                   value="${fmtCurrency(price)}" required oninput="recalcRow(this)">
        </td>
        <td class="text-end" data-label="Jumlah">
            <span class="item-amount text-nowrap">${fmt(amount)}</span>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-link text-danger btn-remove-row p-0" onclick="removeRow(this)">
                <i class="bi bi-x-lg"></i>
            </button>
        </td>`;
    tbody.appendChild(tr);
    initCurrencyInputs(tr);
    // Name picker first — so row._tsName is ready when DSI picker's onChange fires
    const tsName = makeNamePicker(tr.querySelector('.product-name-picker'));
    const ts     = makePicker(tr.querySelector('.product-picker'));

    if (lockPicker) {
        // setValue triggers pickProduct → syncs tsName silently
        if (productId) ts.setValue(String(productId));
        ts.disable();
        tsName.disable();
    }

    recalc();
}

function removeRow(btn) {
    const rows = document.querySelectorAll('#items-body .item-row');
    if (rows.length <= 1) return;
    btn.closest('tr').remove();
    recalc();
}

// ── Calculations ───────────────────────────────────────────────────────────
function recalcRow(input) {
    const row   = input.closest('tr');
    const pax   = parseFloat(row.querySelector('.item-pax').value) || 0;
    const price = parseRaw(row.querySelector('.item-price').value);
    row.querySelector('.item-amount').textContent = fmt(pax * price);
    recalc();
}

function recalc() {
    let subtotal = 0;
    document.querySelectorAll('#items-body .item-row').forEach(row => {
        const pax   = parseFloat(row.querySelector('.item-pax')?.value) || 0;
        const price = parseRaw(row.querySelector('.item-price')?.value);
        subtotal   += pax * price;
    });

    let deposit = parseFloat(document.getElementById('deposit').value) || 0;
    // Clamp deposit to subtotal when subtotal shrinks
    if (deposit > subtotal) {
        deposit = subtotal;
        document.getElementById('deposit').value = subtotal;
        const inp = document.getElementById('deposit-amount-input');
        if (inp) inp.value = subtotal;
    }

    // Grand total = subtotal; deposit is a payment method (not a deduction)
    const grandTotal = subtotal;
    const sisaTagihan = Math.max(0, subtotal - deposit);

    document.getElementById('summary-subtotal').textContent  = 'Rp ' + fmt(subtotal);
    document.getElementById('summary-grand').textContent     = 'Rp ' + fmt(grandTotal);
    document.getElementById('foot-subtotal').textContent     = fmt(subtotal);
    document.getElementById('summary-terbilang').textContent = terbilang(grandTotal) + ' rupiah';

    // Show deposit payment info below grand total
    const infoEl = document.getElementById('summary-deposit-info');
    if (deposit > 0 && infoEl) {
        infoEl.style.display = '';
        document.getElementById('summary-deposit-val').textContent  = 'Rp ' + fmt(deposit);
        document.getElementById('summary-deposit-sisa').textContent = 'Rp ' + fmt(sisaTagihan);
    } else if (infoEl) {
        infoEl.style.display = 'none';
    }

    refreshFinance();
    updateCreditFromSubtotal();
}

function refreshFinance() {
    const tbody    = document.getElementById('finance-body');
    let totalPub   = 0, totalKom = 0, totalNett = 0;
    let rowNum     = 1;
    let hasProduct = false;
    const newRows  = [];

    document.querySelectorAll('#items-body .item-row').forEach(row => {
        const productId = row.dataset.productId;
        const pax       = parseFloat(row.querySelector('.item-pax')?.value) || 0;
        const prod      = products.find(p => p.id == productId);
        if (!prod) return;

        hasProduct = true;
        const tPub  = prod.publish_rate * pax;
        const tKom  = prod.komisi * pax;
        const tNett = prod.nett_price * pax;
        const pct   = prod.publish_rate > 0 ? (prod.komisi / prod.publish_rate * 100) : null;

        totalPub  += tPub;
        totalKom  += tKom;
        totalNett += tNett;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="ps-3 text-muted">${rowNum++}</td>
            <td><span class="badge bg-secondary font-monospace">${prod.dsi_code || '—'}</span></td>
            <td class="text-truncate" style="max-width:160px" title="${prod.name}">${prod.name}</td>
            <td class="text-center">${pax}</td>
            <td class="text-end text-muted">Rp ${fmt(prod.publish_rate)}</td>
            <td class="text-end fw-semibold">Rp ${fmt(tPub)}</td>
            <td class="text-end">Rp ${fmt(tKom)}</td>
            <td class="text-end">Rp ${fmt(tNett)}</td>
            <td class="text-end">${pct !== null ? '<span class="badge bg-info text-dark">' + pct.toFixed(1) + '%</span>' : '—'}</td>
        `;
        newRows.push(tr);
    });

    tbody.innerHTML = '';
    if (!hasProduct) {
        const empty = document.createElement('tr');
        empty.innerHTML = '<td colspan="9" class="text-center text-muted py-3 ps-3">Pilih produk di atas untuk melihat kalkulasi</td>';
        tbody.appendChild(empty);
        document.getElementById('finance-total-publish').textContent = '—';
        document.getElementById('finance-total-komisi').textContent  = '—';
        document.getElementById('finance-total-nett').textContent    = '—';
    } else {
        newRows.forEach(tr => tbody.appendChild(tr));
        document.getElementById('finance-total-publish').textContent = 'Rp ' + fmt(totalPub);
        document.getElementById('finance-total-komisi').textContent  = 'Rp ' + fmt(totalKom);
        document.getElementById('finance-total-nett').textContent    = 'Rp ' + fmt(totalNett);
    }
}

// ── Terbilang ──────────────────────────────────────────────────────────────
function terbilang(n) {
    n = Math.round(n);
    if (n === 0) return 'nol';
    const ones = ['','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan',
                  'sepuluh','sebelas','dua belas','tiga belas','empat belas','lima belas',
                  'enam belas','tujuh belas','delapan belas','sembilan belas'];
    const tens = ['','','dua puluh','tiga puluh','empat puluh','lima puluh',
                  'enam puluh','tujuh puluh','delapan puluh','sembilan puluh'];
    function spell(x) {
        if (x < 20)   return ones[x];
        if (x < 100)  { const u = x%10; return tens[Math.floor(x/10)] + (u ? ' '+ones[u] : ''); }
        if (x < 1000) { const h=Math.floor(x/100),r=x%100; return (h===1?'seratus':ones[h]+' ratus')+(r?' '+spell(r):''); }
        if (x < 1e6)  { const t=Math.floor(x/1000),r=x%1000; return (t===1?'seribu':spell(t)+' ribu')+(r?' '+spell(r):''); }
        if (x < 1e9)  { const m=Math.floor(x/1e6),r=x%1e6; return spell(m)+' juta'+(r?' '+spell(r):''); }
        const b=Math.floor(x/1e9),r=x%1e9; return spell(b)+' miliar'+(r?' '+spell(r):'');
    }
    return spell(n);
}

// ── Init ───────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    makePartnerPicker();

    const rows = document.querySelectorAll('#items-body .item-row');
    if (rows.length === 0) {
        if (importPrefillItems && importPrefillItems.length > 0) {
            // Pre-fill semua item dari transaksi yang sama
            importPrefillItems.forEach(item => {
                addRow(item.product_name, item.product_id || '', item.pax, item.price, true);
            });
        } else {
            addRow();
        }
    } else {
        rows.forEach(row => {
            makeNamePicker(row.querySelector('.product-name-picker'));
            makePicker(row.querySelector('.product-picker'));
        });
    }

    recalc();

    // Load deposit + credit info if partner already selected (edit mode)
    const partnerSel = document.getElementById('partner_id');
    if (partnerSel?.value) {
        loadDepositInfo(partnerSel.value);
        loadCreditInfo(partnerSel.value);
    }

    // Submit guard: require override reason if over limit
    document.querySelector('form')?.addEventListener('submit', function(e) {
        const wrap = document.getElementById('credit-override-wrap');
        if (wrap && wrap.style.display !== 'none') {
            const reason = document.getElementById('credit-override-reason')?.value?.trim();
            if (!reason) {
                e.preventDefault();
                const ta = document.getElementById('credit-override-reason');
                ta?.classList.add('is-invalid');
                ta?.focus();
                ta?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
});
</script>
@endpush
