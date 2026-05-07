@extends('layouts.app')
@section('title', 'Upload Transaksi')
@section('page-title', 'Upload Transaksi')

@push('styles')
<style>
    .upload-wrap {
        max-width: 640px; margin: 0 auto;
    }
    .upload-card {
        background: #fff; border-radius: 16px;
        box-shadow: 0 1px 4px rgba(15,23,41,.07), 0 6px 20px rgba(15,23,41,.06);
        overflow: hidden;
    }
    .upload-card-header {
        padding: 1.2rem 1.5rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; gap: .75rem;
    }
    .upload-card-header .up-icon {
        width: 42px; height: 42px; border-radius: 11px;
        background: linear-gradient(135deg,#3b82f6,#2563eb);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 1.1rem; flex-shrink: 0;
    }
    .upload-card-body { padding: 1.5rem; }

    /* Drop zone */
    .drop-zone {
        border: 2px dashed #cbd5e1;
        border-radius: 14px;
        padding: 2.5rem 1.5rem;
        text-align: center;
        cursor: pointer;
        transition: border-color .2s, background .2s;
        position: relative;
    }
    .drop-zone:hover, .drop-zone.drag-over {
        border-color: #3b82f6;
        background: #f0f7ff;
    }
    .drop-zone .dz-icon {
        width: 64px; height: 64px; border-radius: 18px;
        background: linear-gradient(135deg,#f0fdf4,#dcfce7);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 1rem;
        font-size: 1.8rem; color: #16a34a;
    }
    .drop-zone .dz-title { font-weight: 700; font-size: .95rem; color: #1e293b; margin-bottom: .3rem; }
    .drop-zone .dz-sub   { font-size: .8rem; color: #94a3b8; }
    .drop-zone .dz-badge {
        display: inline-flex; align-items: center; gap: .3rem;
        background: #f1f5f9; border-radius: 20px;
        padding: .25rem .7rem; font-size: .73rem; font-weight: 600; color: #475569;
        margin-top: .75rem;
    }

    /* File preview */
    .file-preview {
        background: #f0fdf4; border: 1px solid #bbf7d0;
        border-radius: 10px; padding: .7rem 1rem;
        display: flex; align-items: center; gap: .75rem;
    }
    .file-preview .fp-icon {
        width: 36px; height: 36px; border-radius: 9px;
        background: #dcfce7; color: #16a34a;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0;
    }
    .file-preview .fp-name { font-weight: 600; font-size: .85rem; color: #166534; }
    .file-preview .fp-size { font-size: .75rem; color: #4ade80; }

    /* Info box */
    .info-box {
        background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px;
        padding: .75rem 1rem; font-size: .8rem; color: #1e40af;
    }
    .info-box .info-title { font-weight: 700; margin-bottom: .25rem; }

    /* Submit btn */
    .btn-upload {
        width: 100%; padding: .7rem; border-radius: 10px; font-size: .9rem; font-weight: 700;
        border: none; cursor: pointer;
        background: linear-gradient(135deg,#3b82f6,#2563eb);
        color: #fff; transition: opacity .2s, transform .15s;
    }
    .btn-upload:hover:not(:disabled) { opacity: .92; transform: translateY(-1px); }
    .btn-upload:disabled { opacity: .55; cursor: not-allowed; transform: none; }
</style>
@endpush

@section('content')

<div class="upload-wrap">

    {{-- Back nav --}}
    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="{{ route('imports.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:9px;">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h5 class="mb-0 fw-bold" style="letter-spacing:-.2px;">Upload File Transaksi</h5>
    </div>

    @if($errors->any())
    <div class="alert d-flex gap-2 align-items-start mb-3" role="alert"
         style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;color:#991b1b;font-size:.84rem;">
        <i class="bi bi-exclamation-circle-fill flex-shrink-0 mt-1" style="color:#ef4444;"></i>
        <div>
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    </div>
    @endif

    <div class="upload-card">
        <div class="upload-card-header">
            <div class="up-icon"><i class="bi bi-file-earmark-arrow-up"></i></div>
            <div>
                <div class="fw-bold" style="font-size:.92rem;">Import Data Transaksi</div>
                <div style="font-size:.75rem;color:#94a3b8;">Upload file Excel atau CSV untuk diproses</div>
            </div>
        </div>
        <div class="upload-card-body">
            <form method="POST" action="{{ route('imports.store') }}" enctype="multipart/form-data" id="uploadForm">
                @csrf

                {{-- Drop zone --}}
                <div id="dropZone" class="drop-zone mb-3" onclick="document.getElementById('fileInput').click()">
                    <div class="dz-icon"><i class="bi bi-file-earmark-spreadsheet"></i></div>
                    <div class="dz-title">Drag & drop file di sini</div>
                    <div class="dz-sub">atau klik untuk memilih file dari komputer</div>
                    <div class="dz-badge">
                        <i class="bi bi-file-earmark-check"></i>
                        .xlsx &nbsp;·&nbsp; .xls &nbsp;·&nbsp; .csv &nbsp;·&nbsp; Maks 10 MB
                    </div>
                    <input type="file" id="fileInput" name="file" accept=".xlsx,.xls,.csv" class="d-none" required>
                </div>

                {{-- File preview --}}
                <div id="filePreview" class="file-preview mb-3 d-none">
                    <div class="fp-icon"><i class="bi bi-file-earmark-check-fill"></i></div>
                    <div class="flex-fill">
                        <div class="fp-name" id="fileName"></div>
                        <div class="fp-size" id="fileSize"></div>
                    </div>
                    <button type="button" id="clearFile" class="btn btn-sm" style="padding:.2rem .4rem;border-radius:7px;border:1px solid #86efac;color:#166534;background:transparent;">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                {{-- Info --}}
                <div class="info-box mb-4">
                    <div class="info-title"><i class="bi bi-info-circle me-1"></i>Ketentuan Format File</div>
                    <div>Kolom wajib: <strong>ticket_type, ticket_name, unit_price, qty</strong></div>
                    <div class="mt-1">Ticket type valid: <strong>HTL &nbsp;·&nbsp; TRD &nbsp;·&nbsp; TVL</strong></div>
                    <div class="mt-1 text-muted" style="font-size:.75rem;">3 huruf pertama ticket_name harus sesuai dengan ticket_type yang dipilih.</div>
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn-upload" id="submitBtn" disabled>
                    <i class="bi bi-upload me-2"></i>Proses Import
                </button>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
const dropZone   = document.getElementById('dropZone');
const fileInput  = document.getElementById('fileInput');
const filePreview= document.getElementById('filePreview');
const fileName   = document.getElementById('fileName');
const fileSize   = document.getElementById('fileSize');
const clearFile  = document.getElementById('clearFile');
const submitBtn  = document.getElementById('submitBtn');
const uploadForm = document.getElementById('uploadForm');

function fmtSize(bytes) {
    if (bytes < 1024)      return bytes + ' B';
    if (bytes < 1048576)   return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(2) + ' MB';
}

function showFile(file) {
    if (!file) return;
    fileName.textContent = file.name;
    fileSize.textContent = fmtSize(file.size);
    filePreview.classList.remove('d-none');
    dropZone.style.display = 'none';
    submitBtn.disabled = false;
}

fileInput.addEventListener('change', () => showFile(fileInput.files[0]));

clearFile.addEventListener('click', () => {
    fileInput.value = '';
    filePreview.classList.add('d-none');
    dropZone.style.display = '';
    submitBtn.disabled = true;
});

dropZone.addEventListener('dragover', e => {
    e.preventDefault();
    dropZone.classList.add('drag-over');
});
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
        showFile(file);
    }
});

uploadForm.addEventListener('submit', () => {
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
});
</script>
@endpush

@endsection
