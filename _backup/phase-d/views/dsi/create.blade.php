@extends('layouts.app')
@section('title', 'Import DSI')
@section('page-title', 'DSI Import')

@push('styles')
<style>
.drop-zone{border:2px dashed #cbd5e1;border-radius:12px;padding:3rem 2rem;text-align:center;transition:border-color .2s,background .2s;cursor:pointer}
.drop-zone.dragover,.drop-zone:hover{border-color:#3b82f6;background:#f0f7ff}
.drop-zone i{font-size:3rem;color:#cbd5e1;display:block;margin-bottom:1rem;transition:color .2s}
.drop-zone.dragover i,.drop-zone:hover i{color:#3b82f6}
.drop-zone-label{font-size:.88rem;font-weight:600;color:#64748b;margin-bottom:.25rem}
.drop-zone-sub{font-size:.78rem;color:#94a3b8}
.file-preview{display:flex;align-items:center;gap:.75rem;background:#f0f7ff;border-radius:8px;padding:.7rem 1rem;margin-top:.75rem}
.file-preview i{font-size:1.4rem;color:#3b82f6}

.stat-card-mini{background:#fff;border:none;border-radius:11px;padding:.9rem 1rem;box-shadow:0 1px 3px rgba(15,23,41,.07);display:flex;align-items:center;gap:.85rem}
.stat-icon-mini{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.05rem;flex-shrink:0}
.stat-label-mini{font-size:.64rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8}
.stat-value-mini{font-size:1.2rem;font-weight:800;color:#1e293b}
</style>
@endpush

@section('content')

<div class="d-flex align-items-center gap-2 mb-3 page-hdr">
    <a href="{{ route('dsi.batches.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;"><i class="bi bi-arrow-left"></i></a>
    <div>
        <div class="page-title">Import Data DSI</div>
        <div class="page-sub">Upload file CSV transaksi DSI</div>
    </div>
    <div class="ms-auto d-flex gap-2">
        <a href="{{ route('dsi.batches.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-list-ul me-1"></i>Riwayat Batch</a>
        <a href="{{ route('dsi.duplicates.review') }}" class="btn btn-outline-warning btn-sm"><i class="bi bi-flag me-1"></i>Review Duplikat</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card-mini">
            <div class="stat-icon-mini" style="background:#eff6ff;"><i class="bi bi-collection" style="color:#3b82f6"></i></div>
            <div><div class="stat-label-mini">Total Batch</div><div class="stat-value-mini">{{ App\Models\DsiImportBatch::count() }}</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card-mini">
            <div class="stat-icon-mini" style="background:#f0fdf4;"><i class="bi bi-check-circle" style="color:#10b981"></i></div>
            <div><div class="stat-label-mini">Completed</div><div class="stat-value-mini">{{ App\Models\DsiImportBatch::where('status','COMPLETED')->count() }}</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card-mini">
            <div class="stat-icon-mini" style="background:#fff7ed;"><i class="bi bi-exclamation-circle" style="color:#f59e0b"></i></div>
            <div><div class="stat-label-mini">Partial</div><div class="stat-value-mini">{{ App\Models\DsiImportBatch::where('status','PARTIAL')->count() }}</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card-mini">
            <div class="stat-icon-mini" style="background:#fef2f2;"><i class="bi bi-flag" style="color:#ef4444"></i></div>
            <div><div class="stat-label-mini">Duplikat Pending</div><div class="stat-value-mini">{{ App\Models\DsiDuplicateFlag::where('status','PENDING')->count() }}</div></div>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-clean">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-arrow-up" style="color:#3b82f6"></i>
                Upload File CSV
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('dsi.import') }}" enctype="multipart/form-data" id="dsi-import-form">
                    @csrf
                    @if($errors->any())
                    <div class="alert alert-modern mb-3" style="background:#fef2f2;border-left:4px solid #ef4444;color:#991b1b;">
                        <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li style="font-size:.84rem;">{{ $e }}</li>@endforeach</ul>
                    </div>
                    @endif

                    {{-- Drop Zone --}}
                    <div class="drop-zone" id="drop-zone" onclick="document.getElementById('csv-file').click()">
                        <i class="bi bi-cloud-upload" id="dz-icon"></i>
                        <div class="drop-zone-label">Seret & Lepas file CSV di sini</div>
                        <div class="drop-zone-sub">atau klik untuk pilih file</div>
                        <div class="drop-zone-sub mt-2" style="font-size:.72rem;">Format: .csv atau .txt — Maks. 10MB</div>
                    </div>
                    <input type="file" name="file" id="csv-file" accept=".csv,.txt" class="d-none" required>
                    <div id="file-preview" class="d-none file-preview">
                        <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                        <div>
                            <div id="file-name" style="font-weight:600;font-size:.84rem;"></div>
                            <div id="file-size" style="font-size:.73rem;color:#94a3b8;"></div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" onclick="clearFile()"><i class="bi bi-x"></i></button>
                    </div>

                    <div class="row g-3 mt-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">Sumber Data</label>
                            <select name="source" class="form-select">
                                <option value="CSV">CSV File</option>
                                <option value="API">API</option>
                            </select>
                        </div>
                    </div>

                    {{-- CSV Format Guide --}}
                    <div class="mt-4 p-3" style="background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;">
                        <div style="font-size:.75rem;font-weight:700;color:#64748b;margin-bottom:.5rem;"><i class="bi bi-info-circle me-1"></i>Format CSV yang diharapkan:</div>
                        <code style="font-size:.72rem;color:#1e40af;display:block;white-space:pre-wrap;">ref_no, transaction_date, guest_name, check_in_date, total_amount, product_type, ...</code>
                        <div style="font-size:.7rem;color:#94a3b8;margin-top:.5rem;">Sistem akan otomatis mendeteksi duplikat berdasarkan file hash, ref_no, dan business logic.</div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary" id="submit-btn" disabled>
                            <i class="bi bi-upload me-1"></i>Mulai Import
                        </button>
                        <a href="{{ route('dsi.batches.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
var fileInput = document.getElementById('csv-file');
var dropZone  = document.getElementById('drop-zone');
var preview   = document.getElementById('file-preview');
var submitBtn = document.getElementById('submit-btn');

fileInput.addEventListener('change', function() {
    if(this.files[0]) showFile(this.files[0]);
});

dropZone.addEventListener('dragover', function(e) { e.preventDefault(); this.classList.add('dragover'); });
dropZone.addEventListener('dragleave', function() { this.classList.remove('dragover'); });
dropZone.addEventListener('drop', function(e) {
    e.preventDefault(); this.classList.remove('dragover');
    var f = e.dataTransfer.files[0];
    if(f) { fileInput.files = e.dataTransfer.files; showFile(f); }
});

function showFile(f) {
    document.getElementById('file-name').textContent = f.name;
    document.getElementById('file-size').textContent = (f.size/1024).toFixed(1)+' KB';
    preview.classList.remove('d-none');
    dropZone.classList.add('d-none');
    submitBtn.disabled = false;
}
function clearFile() {
    fileInput.value = '';
    preview.classList.add('d-none');
    dropZone.classList.remove('d-none');
    submitBtn.disabled = true;
}

document.getElementById('dsi-import-form').addEventListener('submit', function() {
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Mengimpor...';
});
</script>
@endpush
