@extends('layouts.app')

@section('title', 'Terima Pembayaran Credit')
@section('page-title', 'Terima Pembayaran Credit')

@section('content')
<div class="d-flex gap-2 mb-3">
    <a href="{{ route('credit-payments.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger py-2">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('credit-payments.store') }}"
      id="cp-form" enctype="multipart/form-data">
    @csrf

    <div class="row g-3">

        {{-- Step 1: Partner --}}
        <div class="col-12">
            <div class="card card-clean">
                <div class="card-header">1 — Pilih Partner</div>
                <div class="card-body">
                    <div class="col-lg-5">
                        <label class="form-label fw-semibold">Partner Credit <span class="text-danger">*</span></label>
                        <select name="partner_id" id="partner-select"
                                class="form-select @error('partner_id') is-invalid @enderror" required>
                            <option value="">— Pilih Partner —</option>
                            @foreach($partners as $p)
                                <option value="{{ $p->id }}"
                                    data-limit="{{ $p->limit_credit }}"
                                    {{ old('partner_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->nama_partner }}
                                </option>
                            @endforeach
                        </select>
                        @error('partner_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Credit info bar --}}
                    <div id="credit-info-panel" class="mt-3 d-none">
                        <div class="row g-2">
                            <div class="col-auto">
                                <small class="text-muted d-block">Limit Credit</small>
                                <span class="fw-semibold" id="ci-limit">—</span>
                            </div>
                            <div class="col-auto">
                                <small class="text-muted d-block">Terpakai</small>
                                <span class="fw-semibold text-danger" id="ci-used">—</span>
                            </div>
                            <div class="col-auto">
                                <small class="text-muted d-block">Tersedia</small>
                                <span class="fw-semibold text-success" id="ci-available">—</span>
                            </div>
                        </div>
                        <div class="progress mt-2" style="height:6px">
                            <div id="ci-bar" class="progress-bar" role="progressbar" style="width:0%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 2: Total Diterima --}}
        <div class="col-12" id="received-section" style="display:none">
            <div class="card card-clean">
                <div class="card-header">2 — Total Nominal Diterima</div>
                <div class="card-body">
                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Total Diterima (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="total_received" id="total-received"
                               class="form-control @error('total_received') is-invalid @enderror"
                               value="{{ old('total_received') }}"
                               step="0.01" min="0.01" required placeholder="0">
                        @error('total_received')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 3: Invoice Outstanding Table --}}
        <div class="col-12" id="invoice-section" style="display:none">
            <div class="card card-clean">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span>3 — Alokasi ke Invoice Outstanding</span>
                    <button type="button" id="btn-fifo" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-lightning-fill me-1"></i> FIFO Auto-Fill
                    </button>
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
                        <table class="table mb-0 align-middle" id="invoice-table">
                            <thead>
                                <tr>
                                    <th style="width:36px"></th>
                                    <th>No. Invoice</th>
                                    <th>Tgl Invoice</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Status</th>
                                    <th class="text-end">Grand Total</th>
                                    <th class="text-end">Sudah Bayar</th>
                                    <th class="text-end">Sisa Tagihan</th>
                                    <th style="width:160px" class="text-end">Alokasi (Rp)</th>
                                </tr>
                            </thead>
                            <tbody id="invoice-tbody">
                                {{-- filled by JS --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 4: Summary Bar --}}
        <div class="col-12" id="summary-section" style="display:none">
            <div class="card card-clean border-primary">
                <div class="card-body">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm-4">
                            <small class="text-muted d-block">Total Diterima</small>
                            <span class="fs-5 fw-bold text-primary" id="sum-received">Rp 0</span>
                        </div>
                        <div class="col-sm-4">
                            <small class="text-muted d-block">Total Dialokasikan</small>
                            <span class="fs-5 fw-bold text-success" id="sum-allocated">Rp 0</span>
                        </div>
                        <div class="col-sm-4">
                            <small class="text-muted d-block">Sisa → Deposit Partner</small>
                            <span class="fs-5 fw-bold" id="sum-excess">Rp 0</span>
                        </div>
                    </div>
                    <div id="alloc-warning" class="alert alert-danger py-1 mt-2 mb-0 d-none small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Total alokasi melebihi total diterima.
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 5: Payment Info --}}
        <div class="col-12" id="payment-info-section" style="display:none">
            <div class="card card-clean">
                <div class="card-header">4 — Info Pembayaran</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label fw-semibold">Tanggal Bayar <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date"
                                   class="form-control @error('payment_date') is-invalid @enderror"
                                   value="{{ old('payment_date', date('Y-m-d')) }}" required>
                            @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label fw-semibold">Metode <span class="text-danger">*</span></label>
                            <select name="payment_method"
                                    class="form-select @error('payment_method') is-invalid @enderror" required>
                                @foreach(['Transfer Bank','Cash','QRIS','Cek / Giro','Lainnya'] as $m)
                                    <option value="{{ $m }}" {{ old('payment_method') == $m ? 'selected' : '' }}>{{ $m }}</option>
                                @endforeach
                            </select>
                            @error('payment_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label fw-semibold">No. Referensi</label>
                            <input type="text" name="reference_no"
                                   class="form-control @error('reference_no') is-invalid @enderror"
                                   value="{{ old('reference_no') }}" maxlength="100">
                            @error('reference_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label fw-semibold">Bukti Transfer</label>
                            <input type="file" name="proof_file"
                                   class="form-control @error('proof_file') is-invalid @enderror"
                                   accept=".jpg,.jpeg,.png,.pdf">
                            @error('proof_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2"
                                      maxlength="1000">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="col-12" id="submit-section" style="display:none">
            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('credit-payments.index') }}" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" id="btn-submit" class="btn btn-primary" disabled>
                    <i class="bi bi-check-lg me-1"></i> Simpan Pembayaran
                </button>
            </div>
        </div>

    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const partnerSelect   = document.getElementById('partner-select');
    const totalReceivedEl = document.getElementById('total-received');
    const invoiceTbody    = document.getElementById('invoice-tbody');
    const btnFifo         = document.getElementById('btn-fifo');
    const btnSubmit       = document.getElementById('btn-submit');

    const sections = {
        received:     document.getElementById('received-section'),
        invoice:      document.getElementById('invoice-section'),
        summary:      document.getElementById('summary-section'),
        paymentInfo:  document.getElementById('payment-info-section'),
        submit:       document.getElementById('submit-section'),
        loading:      document.getElementById('loading-invoices'),
        noInvoices:   document.getElementById('no-invoices'),
        tableWrap:    document.getElementById('invoice-table-wrap'),
        creditPanel:  document.getElementById('credit-info-panel'),
    };

    let invoiceData = [];

    function fmt(n) {
        return 'Rp ' + Number(n).toLocaleString('id-ID');
    }

    function statusBadge(inv) {
        if (inv.status === 'OVERDUE') return '<span class="badge badge-overdue"><i class="bi bi-exclamation-triangle-fill me-1"></i>OVERDUE</span>';
        if (inv.status === 'PARTIAL') return '<span class="badge badge-partial">PARTIAL</span>';
        return '<span class="badge badge-unpaid">UNPAID</span>';
    }

    function renderInvoices(invoices) {
        invoiceTbody.innerHTML = '';
        invoices.forEach(inv => {
            const tr = document.createElement('tr');
            tr.dataset.invoiceId = inv.id;
            tr.dataset.sisa      = inv.sisa;
            tr.innerHTML = `
                <td>
                    <input type="checkbox" class="form-check-input inv-check" data-id="${inv.id}" checked>
                </td>
                <td class="fw-semibold">${inv.invoice_no}</td>
                <td class="small">${inv.invoice_date ?? '—'}</td>
                <td class="small">${inv.due_date ?? '—'}</td>
                <td>${statusBadge(inv)}</td>
                <td class="text-end small">${fmt(inv.grand_total)}</td>
                <td class="text-end small">${fmt(inv.total_paid)}</td>
                <td class="text-end fw-semibold">${fmt(inv.sisa)}</td>
                <td>
                    <input type="number"
                           name="allocations[${inv.id}]"
                           class="form-control form-control-sm text-end alloc-input"
                           data-id="${inv.id}"
                           data-sisa="${inv.sisa}"
                           value="${inv.sisa}"
                           min="0"
                           step="0.01"
                           max="${inv.sisa}">
                </td>`;
            invoiceTbody.appendChild(tr);
        });
        attachAllocListeners();
        updateSummary();
    }

    function attachAllocListeners() {
        document.querySelectorAll('.alloc-input').forEach(inp => {
            inp.addEventListener('input', updateSummary);
        });
        document.querySelectorAll('.inv-check').forEach(chk => {
            chk.addEventListener('change', function () {
                const inp = document.querySelector(`.alloc-input[data-id="${this.dataset.id}"]`);
                if (!this.checked) {
                    inp.value = 0;
                    inp.disabled = true;
                } else {
                    inp.disabled = false;
                    inp.value = inp.dataset.sisa;
                }
                updateSummary();
            });
        });
    }

    function updateSummary() {
        const received   = parseFloat(totalReceivedEl.value) || 0;
        let   allocated  = 0;

        document.querySelectorAll('.alloc-input').forEach(inp => {
            if (!inp.disabled) allocated += parseFloat(inp.value) || 0;
        });

        const excess = Math.max(0, received - allocated);

        document.getElementById('sum-received').textContent  = fmt(received);
        document.getElementById('sum-allocated').textContent = fmt(allocated);

        const excessEl = document.getElementById('sum-excess');
        excessEl.textContent = fmt(excess);
        excessEl.className = 'fs-5 fw-bold ' + (excess > 0 ? 'text-warning' : 'text-muted');

        const warning = document.getElementById('alloc-warning');
        if (allocated > received + 0.001) {
            warning.classList.remove('d-none');
            btnSubmit.disabled = true;
        } else {
            warning.classList.add('d-none');
            btnSubmit.disabled = allocated === 0;
        }
    }

    function loadCreditInfo(partnerId) {
        fetch(`{{ url('/api/partners') }}/${partnerId}/credit-info`)
            .then(r => r.json())
            .then(d => {
                document.getElementById('ci-limit').textContent     = d.limit_formatted;
                document.getElementById('ci-used').textContent      = d.used_formatted;
                document.getElementById('ci-available').textContent = d.available_formatted;
                const pct = Math.min(100, d.utilization_percent);
                const bar = document.getElementById('ci-bar');
                bar.style.width = pct + '%';
                bar.className = 'progress-bar ' + (pct > 100 ? 'bg-danger' : pct >= 80 ? 'bg-warning' : 'bg-success');
                sections.creditPanel.classList.remove('d-none');
            })
            .catch(() => sections.creditPanel.classList.add('d-none'));
    }

    function loadInvoices(partnerId) {
        sections.loading.classList.remove('d-none');
        sections.noInvoices.classList.add('d-none');
        sections.tableWrap.classList.add('d-none');
        invoiceData = [];
        invoiceTbody.innerHTML = '';

        fetch(`{{ url('/api/partners') }}/${partnerId}/outstanding-invoices-cp`)
            .then(r => r.json())
            .then(data => {
                sections.loading.classList.add('d-none');
                invoiceData = data;
                if (data.length === 0) {
                    sections.noInvoices.classList.remove('d-none');
                    btnSubmit.disabled = true;
                } else {
                    sections.tableWrap.classList.remove('d-none');
                    renderInvoices(data);
                }
            })
            .catch(() => {
                sections.loading.classList.add('d-none');
                sections.noInvoices.classList.remove('d-none');
            });
    }

    partnerSelect.addEventListener('change', function () {
        const pid = this.value;
        if (!pid) {
            Object.values(sections).forEach(el => {
                if (el && el !== sections.creditPanel) el.classList?.add('d-none');
            });
            sections.received.style.display = 'none';
            sections.invoice.style.display  = 'none';
            sections.summary.style.display  = 'none';
            sections.paymentInfo.style.display = 'none';
            sections.submit.style.display   = 'none';
            sections.creditPanel.classList.add('d-none');
            return;
        }
        sections.received.style.display    = '';
        sections.invoice.style.display     = '';
        sections.summary.style.display     = '';
        sections.paymentInfo.style.display = '';
        sections.submit.style.display      = '';
        loadCreditInfo(pid);
        loadInvoices(pid);
    });

    totalReceivedEl.addEventListener('input', updateSummary);

    // FIFO auto-fill
    btnFifo.addEventListener('click', function () {
        let remaining = parseFloat(totalReceivedEl.value) || 0;

        document.querySelectorAll('#invoice-tbody tr').forEach(tr => {
            const chk  = tr.querySelector('.inv-check');
            const inp  = tr.querySelector('.alloc-input');
            const sisa = parseFloat(inp.dataset.sisa) || 0;

            if (remaining <= 0) {
                inp.value = 0;
                chk.checked  = false;
                inp.disabled = true;
            } else if (remaining >= sisa) {
                chk.checked  = true;
                inp.disabled = false;
                inp.value    = sisa;
                remaining   -= sisa;
            } else {
                chk.checked  = true;
                inp.disabled = false;
                inp.value    = remaining.toFixed(2);
                remaining    = 0;
            }
        });
        updateSummary();
    });

    // Form submit guard
    document.getElementById('cp-form').addEventListener('submit', function (e) {
        const received  = parseFloat(totalReceivedEl.value) || 0;
        let   allocated = 0;
        document.querySelectorAll('.alloc-input').forEach(inp => {
            if (!inp.disabled) allocated += parseFloat(inp.value) || 0;
        });
        if (allocated > received + 0.001) {
            e.preventDefault();
            alert('Total alokasi melebihi total diterima. Periksa kembali.');
        }
        if (allocated === 0) {
            e.preventDefault();
            alert('Minimal 1 invoice harus memiliki alokasi > 0.');
        }
    });
})();
</script>
@endpush
