@extends('layouts.app')
@section('title', 'Saldo Kredit Partner')
@section('page-title', 'Partner Credit Balances')

@push('styles')
<style>
    .bal-wrap { background:#fff; border-radius:11px; box-shadow:0 1px 3px rgba(15,23,41,.06); overflow:hidden; }
    .bal-table-hdr { padding:.8rem 1.1rem; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; }
    .bal-wrap table thead th { background:#f8fafc; font-size:.65rem; font-weight:700; letter-spacing:.55px; text-transform:uppercase; color:#64748b; border-bottom:1px solid #f1f5f9; padding:.62rem 1rem; white-space:nowrap; }
    .bal-wrap table tbody td { padding:.65rem 1rem; font-size:.83rem; border-bottom:1px solid #f8fafc; vertical-align:middle; }
    .bal-wrap table tbody tr:last-child td { border-bottom:none; }
    .bal-wrap table tbody tr:hover { background:#fafbff; }
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 page-hdr">
    <div>
        <div class="page-title">Saldo Kredit Partner</div>
        <div class="page-sub">Kelola overpayment yang dapat digunakan untuk tagihan berikutnya</div>
    </div>
    <div class="ms-auto">
        <button class="btn btn-primary btn-sm d-flex align-items-center gap-1" style="border-radius:9px;" data-bs-toggle="modal" data-bs-target="#applyCreditModal">
            <i class="bi bi-arrow-down-left-circle"></i>
            <span>Gunakan Saldo Kredit</span>
        </button>
    </div>
</div>

<div class="filter-panel mb-3" style="background:#fff; border-radius:11px; padding:.9rem 1.1rem; box-shadow:0 1px 3px rgba(15,23,41,.06);">
    <form method="GET" action="{{ route('billing-payments.credit-balance') }}" id="bal-filter-form">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="filter-label small fw-bold text-muted text-uppercase mb-1 d-block">Filter Partner</label>
                <select name="partner_id" class="form-select form-select-sm" style="border-radius:8px;">
                    <option value="">Semua Partner</option>
                    @foreach($partners as $p)
                        <option value="{{ $p->id }}" @selected(request('partner_id') == $p->id)>{{ $p->nama_partner }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm" style="border-radius:8px;"><i class="bi bi-search"></i></button>
            </div>
        </div>
    </form>
</div>

<div class="bal-wrap">
    <div class="bal-table-hdr">
        <span class="fw-semibold" style="font-size:.86rem;">Daftar Saldo Kredit</span>
        <span style="font-size:.73rem;color:#94a3b8;">{{ $balances->total() }} data</span>
    </div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Partner</th>
                    <th class="text-end">Saldo Kredit</th>
                    <th>Last Update</th>
                    <th class="text-center pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($balances as $bal)
                <tr>
                    <td class="ps-4 fw-bold text-primary">{{ $bal->partner?->name ?? 'N/A' }}</td>
                    <td class="text-end fw-800 fs-6 text-success">Rp {{ number_format($bal->balance, 0, ',', '.') }}</td>
                    <td>{{ $bal->updated_at->format('d/m/Y H:i') }}</td>
                    <td class="text-center pe-3">
                        <button class="btn btn-sm btn-outline-primary" style="border-radius:7px;" onclick="prepareApply('{{ $bal->partner_id }}', '{{ $bal->balance }}')">
                            Gunakan
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-5 text-muted">Belum ada partner dengan saldo kredit</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($balances->hasPages())
    <div class="px-4 py-3 border-top">
        {{ $balances->links() }}
    </div>
    @endif
</div>

{{-- Apply Credit Modal --}}
<div class="modal fade" id="applyCreditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0" style="border-radius:12px;">
            <div class="modal-header border-0"><h6 class="modal-title fw-bold">Gunakan Saldo Kredit ke Tagihan</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('billing-payments.apply-credit') }}">
                @csrf
                <div class="modal-body p-4 pt-0">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pilih Partner</label>
                        <select name="partner_id" id="apply_partner_id" class="form-select ts-select" required onchange="loadOutstandingInvoices(this.value)">
                            <option value="">-- Pilih Partner --</option>
                            @foreach($balances->where('balance', '>', 0) as $b)
                                <option value="{{ $b->partner_id }}" data-balance="{{ $b->balance }}">{{ $b->partner?->name }} (Saldo: Rp {{ number_format($b->balance, 0, ',', '.') }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pilih Invoice Tujuan</label>
                        <select name="invoice_id" id="apply_invoice_id" class="form-select" required disabled>
                            <option value="">-- Pilih Invoice --</option>
                        </select>
                        <div id="loading_invoices" class="small text-muted mt-1 d-none"><span class="spinner-border spinner-border-sm me-1"></span>Memuat tagihan...</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Jumlah Kredit yang Digunakan</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted small fw-bold">Rp</span>
                            <input type="text" name="amount" id="apply_amount" class="form-control currency-input" required placeholder="0">
                        </div>
                        <div class="small text-muted mt-1">Maksimal: <span id="max_apply" class="fw-bold">Rp 0</span></div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Gunakan Kredit</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function prepareApply(partnerId, balance) {
        const modal = new bootstrap.Modal(document.getElementById('applyCreditModal'));
        document.getElementById('apply_partner_id').value = partnerId;
        // Trigger TomSelect sync if exists
        const ts = document.getElementById('apply_partner_id').tomselect;
        if(ts) ts.setValue(partnerId);
        
        loadOutstandingInvoices(partnerId);
        modal.show();
    }

    async function loadOutstandingInvoices(partnerId) {
        const select = document.getElementById('apply_invoice_id');
        const loading = document.getElementById('loading_invoices');
        const maxSpan = document.getElementById('max_apply');
        
        if(!partnerId) {
            select.disabled = true;
            select.innerHTML = '<option value="">-- Pilih Invoice --</option>';
            maxSpan.textContent = 'Rp 0';
            return;
        }

        // Get balance from selected option
        const partnerOption = document.querySelector(`#apply_partner_id option[value="${partnerId}"]`);
        const balance = partnerOption ? parseFloat(partnerOption.dataset.balance) : 0;
        maxSpan.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(balance);

        loading.classList.remove('d-none');
        select.disabled = true;
        
        try {
            // Using the existing API route from Web.php if available, else we might need to add one.
            // Based on web.php: Route::get('/api/partners/{partner}/outstanding-invoices', [PaymentMemoController::class, 'outstandingInvoices'])
            const response = await fetch(`{{ url('/api/partners') }}/${partnerId}/outstanding-invoices`);
            const data = await response.json();
            
            select.innerHTML = '<option value="">-- Pilih Invoice --</option>';
            if(data.length === 0) {
                select.innerHTML = '<option value="">Tidak ada tagihan tertunggak</option>';
            } else {
                data.forEach(inv => {
                    const remaining = inv.grand_total - (inv.paid_amount || 0);
                    const opt = document.createElement('option');
                    opt.value = inv.id;
                    opt.textContent = `${inv.invoice_no} (Sisa: Rp ${new Intl.NumberFormat('id-ID').format(remaining)})`;
                    opt.dataset.remaining = remaining;
                    select.appendChild(opt);
                });
                select.disabled = false;
            }
        } catch (e) {
            console.error(e);
            select.innerHTML = '<option value="">Gagal memuat data</option>';
        } finally {
            loading.classList.add('d-none');
        }
    }

    document.getElementById('apply_invoice_id').addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if(opt && opt.dataset.remaining) {
            // Pre-fill amount with the smaller of balance or remaining debt
            const partnerOption = document.querySelector(`#apply_partner_id option[value="${document.getElementById('apply_partner_id').value}"]`);
            const balance = partnerOption ? parseFloat(partnerOption.dataset.balance) : 0;
            const remaining = parseFloat(opt.dataset.remaining);
            const useAmt = Math.min(balance, remaining);
            
            const amountInput = document.getElementById('apply_amount');
            amountInput.value = new Intl.NumberFormat('id-ID').format(useAmt);
            // Trigger input event for formatter
            amountInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        if(window.TomSelect) {
            document.querySelectorAll('.ts-select').forEach(el => new TomSelect(el));
        }
    });
</script>
@endpush
