@extends('layouts.app')

@section('title', 'Buat Memo Tagihan')
@section('page-title', 'Buat Memo Tagihan')

@section('content')
<div class="d-flex gap-2 mb-3">
    <a href="{{ route('payment-memos.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<form method="POST" action="{{ route('payment-memos.store') }}" id="memo-form">
    @csrf

    <div class="row g-3">
        {{-- Step 1: Pilih Partner --}}
        <div class="col-12">
            <div class="card card-clean">
                <div class="card-header">1 — Pilih Partner</div>
                <div class="card-body">
                    <div class="col-lg-5">
                        <label class="form-label fw-semibold">Partner <span class="text-danger">*</span></label>
                        <select name="partner_id" id="partner-select"
                class="form-select @error('partner_id') is-invalid @enderror"
                required>
                            <option value="">— Pilih Partner —</option>
                            @foreach($partners as $p)
                                <option value="{{ $p->id }}"
                                    {{ (old('partner_id', $selectedPartner?->id) == $p->id) ? 'selected' : '' }}>
                                    {{ $p->nama_partner }}
                                </option>
                            @endforeach
                        </select>
                        @error('partner_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 2: Invoice Outstanding --}}
        <div class="col-12" id="invoice-section" style="{{ $selectedPartner ? '' : 'display:none' }}">
            <div class="card card-clean">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>2 — Invoice Outstanding</span>
                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-select-all">
                            <i class="bi bi-check-all me-1"></i> Pilih Semua
                        </button>
                        <span id="total-label" class="fw-semibold text-primary small ms-2"></span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="loading-invoices" class="text-center py-4 text-muted d-none">
                        <div class="spinner-border spinner-border-sm me-1"></div> Memuat invoice...
                    </div>
                    <div id="no-invoices" class="text-center py-4 text-muted d-none">
                        <i class="bi bi-inbox fs-2 d-block mb-1"></i>
                        Partner ini tidak memiliki invoice outstanding.
                    </div>
                    <div class="table-responsive" id="invoice-table-wrap">
                        <table class="table mb-0" id="invoice-table">
                            <thead>
                                <tr>
                                    <th style="width:40px"></th>
                                    <th>No. Invoice</th>
                                    <th>Tgl Invoice</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Status</th>
                                    <th class="text-end">Grand Total</th>
                                    <th class="text-end">Sisa Tagihan</th>
                                </tr>
                            </thead>
                            <tbody id="invoice-tbody">
                                {{-- filled by JS or server --}}
                                @if($selectedPartner && $outstandingInvoices->isNotEmpty())
                                    @foreach($outstandingInvoices as $inv)
                                    @php
                                        $paid = (float) $inv->payments()->sum('amount');
                                        $sisa = max(0, (float) $inv->grand_total - $paid);
                                        $daysJt = now()->startOfDay()->diffInDays($inv->due_date, false);
                                    @endphp
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="invoice_ids[]" value="{{ $inv->id }}"
                                                   class="form-check-input invoice-check" checked
                                                   data-sisa="{{ $sisa }}">
                                        </td>
                                        <td class="fw-semibold">{{ $inv->invoice_no }}</td>
                                        <td>{{ $inv->invoice_date?->format('d/m/Y') }}</td>
                                        <td>{{ $inv->due_date?->format('d/m/Y') }}</td>
                                        <td>
                                            @if($inv->payment_status === 'OVERDUE')
                                                <span class="badge badge-overdue"><i class="bi bi-exclamation-triangle-fill me-1"></i>OVERDUE</span>
                                            @elseif($daysJt >= 0 && $daysJt <= 7)
                                                <span class="badge badge-partial"><i class="bi bi-clock me-1"></i>{{ $daysJt }} hari</span>
                                            @else
                                                <span class="badge badge-unpaid">{{ $inv->payment_status }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">Rp {{ number_format($inv->grand_total, 0, ',', '.') }}</td>
                                        <td class="text-end fw-semibold text-danger">Rp {{ number_format($sisa, 0, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-top-0 pt-0">
                    <div class="d-flex justify-content-between align-items-center py-2 px-1">
                        <span class="text-muted small">Batas pembayaran: <strong>{{ now()->addDays(7)->format('d M Y') }}</strong> (otomatis 7 hari)</span>
                        <span class="fw-bold text-primary">Total: <span id="total-outstanding">Rp 0</span></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 3: Catatan + Submit --}}
        <div class="col-12" id="submit-section" style="{{ $selectedPartner ? '' : 'display:none' }}">
            <div class="card card-clean">
                <div class="card-header">3 — Catatan & Simpan</div>
                <div class="card-body">
                    <div class="col-lg-6">
                        <label class="form-label">Catatan Internal (opsional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Catatan internal, tidak muncul di PDF...">{{ old('notes') }}</textarea>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary" id="btn-submit">
                            <i class="bi bi-file-earmark-arrow-down me-1"></i> Buat Memo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const outstandingBaseUrl = '{{ url('/api/partners') }}';
    const partnerSelect  = document.getElementById('partner-select');
    const invoiceSection = document.getElementById('invoice-section');
    const submitSection  = document.getElementById('submit-section');
    const tbody          = document.getElementById('invoice-tbody');
    const loadingDiv     = document.getElementById('loading-invoices');
    const noInvoicesDiv  = document.getElementById('no-invoices');
    const tableWrap      = document.getElementById('invoice-table-wrap');
    const totalEl        = document.getElementById('total-outstanding');
    const btnSelectAll   = document.getElementById('btn-select-all');
    const btnSubmit      = document.getElementById('btn-submit');

    function fmt(n) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n));
    }

    function recalcTotal() {
        let total = 0;
        document.querySelectorAll('.invoice-check:checked').forEach(function(cb) {
            total += parseFloat(cb.dataset.sisa) || 0;
        });
        totalEl.textContent = fmt(total);

        const anyChecked = document.querySelectorAll('.invoice-check:checked').length > 0;
        btnSubmit.disabled = !anyChecked;
    }

    function renderInvoices(invoices) {
        tbody.innerHTML = '';

        if (!invoices || invoices.length === 0) {
            loadingDiv.classList.add('d-none');
            noInvoicesDiv.classList.remove('d-none');
            tableWrap.classList.add('d-none');
            recalcTotal();
            return;
        }

        noInvoicesDiv.classList.add('d-none');
        tableWrap.classList.remove('d-none');
        loadingDiv.classList.add('d-none');

        invoices.forEach(function(inv) {
            const daysJt = inv.days_to_jt;
            let statusBadge = '';
            if (inv.status === 'OVERDUE') {
                statusBadge = '<span class="badge badge-overdue"><i class="bi bi-exclamation-triangle-fill me-1"></i>OVERDUE</span>';
            } else if (daysJt >= 0 && daysJt <= 7) {
                statusBadge = '<span class="badge badge-partial"><i class="bi bi-clock me-1"></i>' + daysJt + ' hari</span>';
            } else {
                statusBadge = '<span class="badge badge-unpaid">' + inv.status + '</span>';
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><input type="checkbox" name="invoice_ids[]" value="${inv.id}" class="form-check-input invoice-check" checked data-sisa="${inv.sisa}"></td>
                <td class="fw-semibold">${inv.invoice_no}</td>
                <td>${inv.invoice_date}</td>
                <td>${inv.due_date || '—'}</td>
                <td>${statusBadge}</td>
                <td class="text-end">${fmt(inv.grand_total)}</td>
                <td class="text-end fw-semibold text-danger">${fmt(inv.sisa)}</td>
            `;
            tbody.appendChild(tr);
        });

        document.querySelectorAll('.invoice-check').forEach(function(cb) {
            cb.addEventListener('change', recalcTotal);
        });

        recalcTotal();
    }

    function loadInvoices(partnerId) {
        invoiceSection.style.display = '';
        submitSection.style.display  = '';
        loadingDiv.classList.remove('d-none');
        noInvoicesDiv.classList.add('d-none');
        tableWrap.classList.add('d-none');
        tbody.innerHTML = '';

        fetch(outstandingBaseUrl + '/' + partnerId + '/outstanding-invoices', {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) { renderInvoices(data); })
        .catch(function() {
            loadingDiv.classList.add('d-none');
            noInvoicesDiv.classList.remove('d-none');
        });
    }

    partnerSelect.addEventListener('change', function () {
        if (this.value) {
            loadInvoices(this.value);
        } else {
            invoiceSection.style.display = 'none';
            submitSection.style.display  = 'none';
        }
    });

    btnSelectAll.addEventListener('click', function () {
        const checks = document.querySelectorAll('.invoice-check');
        const allChecked = Array.from(checks).every(function(c) { return c.checked; });
        checks.forEach(function(c) { c.checked = !allChecked; });
        recalcTotal();
        btnSelectAll.innerHTML = allChecked
            ? '<i class="bi bi-check-all me-1"></i> Pilih Semua'
            : '<i class="bi bi-x-square me-1"></i> Batal Pilih';
    });

    // Init if server-side rendered
    document.querySelectorAll('.invoice-check').forEach(function(cb) {
        cb.addEventListener('change', recalcTotal);
    });
    recalcTotal();
})();
</script>
@endpush
