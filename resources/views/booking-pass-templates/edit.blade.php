@extends('layouts.app')

@section('title', 'Visual Editor — ' . $bookingPassTemplate->template_name)
@section('page-title', 'Visual Editor Booking Pass')

@push('styles')
<style>
/* ── Editor Layout ─────────────────────────────────────────── */
#bp-editor-wrap {
    display: flex;
    gap: 0;
    height: calc(100vh - 130px);
    min-height: 600px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    background: #f8fafc;
}

/* ── Sidebar ────────────────────────────────────────────────── */
#bp-sidebar {
    width: 270px;
    min-width: 270px;
    background: #fff;
    border-right: 1px solid #dee2e6;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
#bp-sidebar-header {
    padding: 12px 14px;
    background: #0f1729;
    color: #fff;
    font-size: 13px;
    font-weight: 600;
}
#bp-sidebar-body {
    flex: 1;
    overflow-y: auto;
    padding: 10px 10px 0;
}
.sidebar-section-title {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .8px;
    color: #94a3b8;
    padding: 8px 4px 4px;
}
.bp-var-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 7px 10px;
    margin-bottom: 3px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    cursor: grab;
    font-size: 12px;
    font-weight: 500;
    color: #1e293b;
    transition: background .15s, border-color .15s;
    user-select: none;
}
.bp-var-item:hover { background: #e2e8f0; border-color: #cbd5e1; }
.bp-var-item.on-canvas { background: #dcfce7; border-color: #86efac; color: #166534; cursor: default; opacity: .7; }
.bp-var-item.on-canvas .var-drag-icon { display: none; }
.var-drag-icon { color: #94a3b8; font-size: 14px; }
.var-label { flex: 1; }
.var-key { font-size: 10px; color: #94a3b8; font-family: monospace; }

#bp-sidebar-footer {
    padding: 10px;
    border-top: 1px solid #dee2e6;
    background: #fff;
}

/* ── Canvas Area ────────────────────────────────────────────── */
#bp-canvas-wrap {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
#bp-toolbar {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: #fff;
    border-bottom: 1px solid #dee2e6;
    flex-shrink: 0;
}
#bp-canvas-scroller {
    flex: 1;
    overflow: auto;
    padding: 20px;
    background: #e2e8f0;
    display: flex;
    justify-content: center;
}
#bp-canvas {
    position: relative;
    width: 794px;
    height: 1123px;
    background: #fff;
    box-shadow: 0 4px 24px rgba(0,0,0,.18);
    flex-shrink: 0;
    overflow: hidden;
}
#bp-canvas.drag-over { outline: 3px dashed #3b82f6; }
#bp-bg-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: fill;
    pointer-events: none;
    z-index: 0;
}
.bp-no-bg {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #cbd5e1;
    font-size: 14px;
    z-index: 0;
    pointer-events: none;
}

/* ── Field Boxes ────────────────────────────────────────────── */
.bp-field-box {
    position: absolute;
    z-index: 10;
    background: rgba(255,255,255,0.82);
    border: 2px dashed #3b82f6;
    border-radius: 4px;
    padding: 4px 8px;
    cursor: move;
    min-width: 80px;
    min-height: 28px;
    box-sizing: border-box;
    line-height: 1.3;
    word-break: break-word;
}
.bp-field-box:hover { border-style: solid; box-shadow: 0 2px 8px rgba(59,130,246,.25); }
.bp-field-box.selected { border-style: solid; border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,.2); }
.bp-field-label {
    font-size: 9px;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .4px;
    display: block;
    margin-bottom: 1px;
    pointer-events: none;
}
.bp-field-value {
    display: block;
    pointer-events: none;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}
.bp-field-box .bp-remove-btn {
    position: absolute;
    top: -8px;
    right: -8px;
    width: 18px;
    height: 18px;
    background: #ef4444;
    color: #fff;
    border: none;
    border-radius: 50%;
    font-size: 11px;
    line-height: 18px;
    text-align: center;
    cursor: pointer;
    display: none;
    padding: 0;
    z-index: 20;
}
.bp-field-box.selected .bp-remove-btn { display: block; }
.bp-resize-handle {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 12px;
    height: 12px;
    background: #3b82f6;
    cursor: se-resize;
    border-radius: 2px 0 4px 0;
}
.bp-field-box.selected .bp-resize-handle { display: block; }
.bp-resize-handle { display: none; }

/* ── Style Panel ────────────────────────────────────────────── */
#bp-style-panel {
    width: 220px;
    min-width: 220px;
    background: #fff;
    border-left: 1px solid #dee2e6;
    padding: 12px;
    overflow-y: auto;
    display: none;
    flex-direction: column;
    gap: 10px;
}
#bp-style-panel.visible { display: flex; }
#bp-style-panel h6 { font-size: 12px; font-weight: 700; color: #0f1729; margin-bottom: 6px; }
#bp-style-panel label { font-size: 11px; color: #64748b; margin-bottom: 2px; display: block; }
#bp-style-panel input, #bp-style-panel select {
    font-size: 12px;
    padding: 3px 6px;
    height: auto;
}

/* ── Upload overlay ─────────────────────────────────────────── */
#bg-upload-zone {
    border: 2px dashed #94a3b8;
    border-radius: 8px;
    padding: 10px;
    text-align: center;
    font-size: 12px;
    color: #64748b;
    cursor: pointer;
    transition: border-color .2s;
    position: relative;
}
#bg-upload-zone:hover { border-color: #3b82f6; color: #3b82f6; }
#bg-upload-zone input[type=file] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
}

/* ── Toast ──────────────────────────────────────────────────── */
#bp-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
    min-width: 200px;
}

/* ── Custom Field Modal ─────────────────────────────────────── */
.custom-field-row { display: flex; gap: 6px; align-items: flex-start; margin-bottom: 6px; }
.custom-field-row input { flex: 1; font-size: 12px; }
</style>
@endpush

@section('content')

{{-- Page header --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <div class="page-title">{{ $bookingPassTemplate->template_name }}</div>
        <div class="page-sub text-muted small">Visual Editor — drag variabel ke canvas untuk mengatur layout</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('booking-pass-templates.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- Info form (template name, partner, active) --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="POST" action="{{ route('booking-pass-templates.update', $bookingPassTemplate) }}" class="row g-2 align-items-end">
            @csrf @method('PUT')
            <div class="col-md-4">
                <label class="form-label small mb-1">Nama Template</label>
                <input type="text" name="template_name" value="{{ $bookingPassTemplate->template_name }}" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Partner <span class="text-muted">(kosong = default semua)</span></label>
                <select name="partner_id" class="form-select form-select-sm">
                    <option value="">— Default (semua partner) —</option>
                    @foreach($partners as $p)
                        <option value="{{ $p->id }}" {{ $bookingPassTemplate->partner_id == $p->id ? 'selected' : '' }}>{{ $p->nama_partner }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Tipe</label>
                <select name="template_type" class="form-select form-select-sm">
                    <option value="">— Semua —</option>
                    <option value="self_service" {{ $bookingPassTemplate->template_type == 'self_service' ? 'selected' : '' }}>Self Service</option>
                    <option value="internal"     {{ $bookingPassTemplate->template_type == 'internal'     ? 'selected' : '' }}>Internal</option>
                    <option value="partner"      {{ $bookingPassTemplate->template_type == 'partner'      ? 'selected' : '' }}>Partner</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Mode QR/Barcode</label>
                <select name="qr_type" class="form-select form-select-sm">
                    <option value="qr"      {{ ($bookingPassTemplate->qr_type ?? 'qr') == 'qr'      ? 'selected' : '' }}>QR Code</option>
                    <option value="barcode" {{ ($bookingPassTemplate->qr_type ?? 'qr') == 'barcode' ? 'selected' : '' }}>Barcode</option>
                </select>
            </div>
            <div class="col-auto">
                <div class="form-check mt-1">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="isActive" class="form-check-input"
                           {{ $bookingPassTemplate->is_active ? 'checked' : '' }}>
                    <label class="form-check-label small" for="isActive">Aktif</label>
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-outline-primary">Simpan Info</button>
            </div>
        </form>
    </div>
</div>

{{-- Main Editor --}}
<div id="bp-editor-wrap">

    {{-- ── Sidebar ── --}}
    <div id="bp-sidebar">
        <div id="bp-sidebar-header">
            <i class="bi bi-grip-vertical me-1"></i> Variabel Reservasi
        </div>
        <div id="bp-sidebar-body">

            {{-- Upload BG --}}
            <div class="sidebar-section-title">Background Template</div>
            <div id="bg-upload-zone" class="mb-2" title="Upload gambar background (JPG/PNG, max 5MB)">
                <input type="file" id="bg-file-input" accept=".jpg,.jpeg,.png">
                <i class="bi bi-image me-1"></i>
                <span id="bg-upload-label">
                    @if($bookingPassTemplate->template_file)
                        Ganti background
                    @else
                        Upload background (JPG/PNG)
                    @endif
                </span>
                <div class="mt-1 text-muted" style="font-size:10px;">Max 5MB</div>
            </div>

            {{-- Core variables --}}
            <div class="sidebar-section-title">Data Reservasi</div>

            @php
            $coreVars = [
                ['key' => 'reservation_no',  'label' => 'No. Reservasi',      'icon' => 'bi-hash'],
                ['key' => 'guest_name',      'label' => 'Nama Tamu',          'icon' => 'bi-person'],
                ['key' => 'guest_country',   'label' => 'Negara Asal',        'icon' => 'bi-globe'],
                ['key' => 'visit_date',      'label' => 'Tanggal Kunjungan',  'icon' => 'bi-calendar-event'],
                ['key' => 'partner_name',    'label' => 'Nama Partner',       'icon' => 'bi-building'],
                ['key' => 'product_name',    'label' => 'Nama Produk',        'icon' => 'bi-box-seam'],
                ['key' => 'payment_method',  'label' => 'Metode Pembayaran',  'icon' => 'bi-credit-card'],
                ['key' => 'payment_channel', 'label' => 'Channel Pembayaran', 'icon' => 'bi-bank'],
                ['key' => 'total_amount',    'label' => 'Total Amount',       'icon' => 'bi-cash-stack'],
                ['key' => 'status',          'label' => 'Status',             'icon' => 'bi-check-circle'],
                ['key' => 'notes',           'label' => 'Catatan',            'icon' => 'bi-sticky'],
                ['key' => 'created_at',      'label' => 'Tanggal Dibuat',     'icon' => 'bi-clock'],
            ];
            $specialVars = [
                ['key' => 'items_table', 'label' => 'Tabel Produk',   'icon' => 'bi-table'],
                ['key' => 'items_list',  'label' => 'List Produk',    'icon' => 'bi-list-ul'],
                ['key' => 'qr_code',     'label' => 'QR Code',        'icon' => 'bi-qr-code'],
                ['key' => 'logo',        'label' => 'Logo Perusahaan','icon' => 'bi-image'],
            ];
            @endphp

            @foreach($coreVars as $v)
            <div class="bp-var-item" draggable="true"
                 data-key="{{ $v['key'] }}" data-label="{{ $v['label'] }}">
                <i class="bi {{ $v['icon'] }} var-drag-icon"></i>
                <div class="var-label">
                    {{ $v['label'] }}
                    <div class="var-key">{{ $v['key'] }}</div>
                </div>
                <i class="bi bi-grip-vertical text-muted" style="font-size:14px;"></i>
            </div>
            @endforeach

            <div class="sidebar-section-title">Elemen Khusus</div>
            @foreach($specialVars as $v)
            <div class="bp-var-item" draggable="true"
                 data-key="{{ $v['key'] }}" data-label="{{ $v['label'] }}">
                <i class="bi {{ $v['icon'] }} var-drag-icon"></i>
                <div class="var-label">
                    {{ $v['label'] }}
                    <div class="var-key">{{ $v['key'] }}</div>
                </div>
                <i class="bi bi-grip-vertical text-muted" style="font-size:14px;"></i>
            </div>
            @endforeach

            {{-- Custom fields from existing field_mapping --}}
            <div class="sidebar-section-title">Custom Fields</div>
            <div id="custom-vars-list"></div>

        </div>
        <div id="bp-sidebar-footer">
            <button class="btn btn-sm btn-outline-secondary w-100 mb-2" id="btn-add-custom-field">
                <i class="bi bi-plus-circle me-1"></i> Tambah Custom Field
            </button>
        </div>
    </div>

    {{-- ── Canvas ── --}}
    <div id="bp-canvas-wrap">
        <div id="bp-toolbar">
            <button class="btn btn-sm btn-outline-secondary" id="btn-undo" title="Undo (Ctrl+Z)" disabled>
                <i class="bi bi-arrow-counterclockwise"></i>
            </button>
            <button class="btn btn-sm btn-outline-secondary" id="btn-redo" title="Redo (Ctrl+Y)" disabled>
                <i class="bi bi-arrow-clockwise"></i>
            </button>
            <div class="vr mx-1"></div>
            <button class="btn btn-sm btn-outline-secondary" id="btn-grid" title="Toggle grid">
                <i class="bi bi-grid-3x3"></i> Grid
            </button>
            <div class="vr mx-1"></div>
            <select class="form-select form-select-sm" id="select-zoom" style="width:90px;">
                <option value="0.5">50%</option>
                <option value="0.75">75%</option>
                <option value="1" selected>100%</option>
                <option value="1.25">125%</option>
            </select>
            <div class="ms-auto d-flex gap-2 align-items-center">
                <div class="d-flex align-items-center gap-1">
                    <span class="text-muted small">Preview:</span>
                    <select id="select-preview-reservation" class="form-select form-select-sm" style="width:210px;font-size:11px;"
                            title="Pilih reservasi untuk preview dengan data nyata">
                        <option value="">— Data Dummy —</option>
                        @foreach($recentReservations as $r)
                            <option value="{{ $r->id }}">{{ $r->reservation_no }} · {{ $r->guest_name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-sm btn-outline-info" id="btn-preview">
                    <i class="bi bi-eye me-1"></i> Preview PDF
                </button>
                <button class="btn btn-sm btn-success" id="btn-save">
                    <i class="bi bi-floppy me-1"></i> Simpan Layout
                </button>
            </div>
        </div>

        <div id="bp-canvas-scroller">
            <div id="bp-canvas-zoom-wrap" style="transform-origin: top center;">
                <div id="bp-canvas"
                     data-template-id="{{ $bookingPassTemplate->id }}"
                     data-upload-bg-url="{{ route('booking-pass-templates.upload-bg', $bookingPassTemplate) }}"
                     data-save-url="{{ route('booking-pass-templates.update-mapping', $bookingPassTemplate) }}"
                     data-preview-url="{{ route('booking-pass-templates.preview', $bookingPassTemplate) }}"
                     data-csrf="{{ csrf_token() }}">

                    {{-- Background --}}
                    @if($bookingPassTemplate->template_file)
                        <img id="bp-bg-img" src="{{ asset('storage/' . $bookingPassTemplate->template_file) }}" alt="background">
                    @else
                        <div class="bp-no-bg" id="bp-no-bg-msg">
                            <div class="text-center">
                                <i class="bi bi-image fs-1 d-block mb-2"></i>
                                Upload background di sidebar kiri
                            </div>
                        </div>
                    @endif

                    {{-- Drop zone overlay (invisible, full canvas) --}}
                    <div id="bp-drop-zone" style="position:absolute;inset:0;z-index:5;"></div>

                </div>
            </div>
        </div>
    </div>

    {{-- ── Style Panel ── --}}
    <div id="bp-style-panel">
        <h6><i class="bi bi-palette me-1"></i> Style</h6>
        <div id="style-field-name" class="badge bg-primary mb-2" style="font-size:11px;"></div>

        <div>
            <label>Font Size (px)</label>
            <input type="number" id="sp-font-size" class="form-control form-control-sm" min="6" max="72" value="12">
        </div>
        <div>
            <label>Font Weight</label>
            <select id="sp-font-weight" class="form-select form-select-sm">
                <option value="normal">Normal</option>
                <option value="bold">Bold</option>
            </select>
        </div>
        <div>
            <label>Warna Nilai</label>
            <input type="color" id="sp-color" class="form-control form-control-sm form-control-color" value="#000000">
        </div>
        <div>
            <label>Alignment</label>
            <div class="btn-group btn-group-sm w-100" role="group">
                <button type="button" class="btn btn-outline-secondary align-btn" data-align="left"><i class="bi bi-text-left"></i></button>
                <button type="button" class="btn btn-outline-secondary align-btn" data-align="center"><i class="bi bi-text-center"></i></button>
                <button type="button" class="btn btn-outline-secondary align-btn" data-align="right"><i class="bi bi-text-right"></i></button>
            </div>
        </div>
        <div>
            <label>Lebar Box (%)</label>
            <input type="number" id="sp-width" class="form-control form-control-sm" min="5" max="100" step="1" value="30">
        </div>
        <div class="mt-2">
            <label class="d-flex align-items-center gap-2">
                <input type="checkbox" id="sp-show-label" checked> Tampilkan label
            </label>
        </div>
        <div id="sp-label-controls">
            <label>Ukuran Label (px)</label>
            <input type="number" id="sp-label-font-size" class="form-control form-control-sm" min="6" max="36" value="9">
        </div>
        <div>
            <label>Warna Label</label>
            <input type="color" id="sp-label-color" class="form-control form-control-sm form-control-color" value="#64748b">
        </div>
        <hr class="my-2">
        <div>
            <label>Tipe Output</label>
            <select id="sp-output-type" class="form-select form-select-sm">
                <option value="text">Teks</option>
                <option value="qr">QR Code</option>
                <option value="barcode">Barcode</option>
            </select>
        </div>
        <hr class="my-2">
        <div>
            <label>Posisi X (%)</label>
            <input type="number" id="sp-x" class="form-control form-control-sm" min="0" max="100" step="0.1">
        </div>
        <div>
            <label>Posisi Y (%)</label>
            <input type="number" id="sp-y" class="form-control form-control-sm" min="0" max="100" step="0.1">
        </div>
    </div>

</div>{{-- /bp-editor-wrap --}}

{{-- Toast --}}
<div id="bp-toast" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

{{-- Custom Field Modal --}}
<div class="modal fade" id="customFieldModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0">Tambah Custom Field</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-2">Field ini diisi user saat membuat reservasi dan disimpan di <code>booking_pass_data</code>.</p>
                <div class="mb-2">
                    <label class="form-label small mb-1">Label</label>
                    <input type="text" id="cf-label" class="form-control form-control-sm" placeholder="cth: Kode Voucher">
                </div>
                <div class="mb-2">
                    <label class="form-label small mb-1">Key <span class="text-muted">(auto dari label)</span></label>
                    <input type="text" id="cf-key" class="form-control form-control-sm" placeholder="cth: voucher_code">
                    <div class="form-text">Full key: <code>booking_pass_data.<span id="cf-key-preview">...</span></code></div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-sm btn-primary" id="btn-confirm-custom-field">Tambah</button>
            </div>
        </div>
    </div>
</div>

{{-- Existing field_mapping JSON for JS initialization --}}
<script id="bp-initial-mapping" type="application/json">
{!! json_encode($bookingPassTemplate->field_mapping ?? ['canvas'=>['width_px'=>794,'height_px'=>1123],'fields'=>[],'custom_fields'=>[]], JSON_UNESCAPED_UNICODE) !!}
</script>

@endsection

@push('scripts')
<script src="{{ asset('js/booking-pass-editor.js') }}"></script>
@endpush
