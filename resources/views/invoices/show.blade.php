@extends('layouts.app')
@section('title', $invoice->invoice_no)
@section('page-title', $invoice->invoice_no)

@push('styles')
<style>
    .info-field { margin-bottom: .85rem; }
    .info-field .lbl { font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: #94a3b8; margin-bottom: 2px; }
    .info-field .val { font-size: .88rem; color: #1e293b; font-weight: 500; }

    .item-card-row {
        display: flex; align-items: center; gap: .6rem;
        padding: .65rem 1rem; border-bottom: 1px solid #f8fafc;
        font-size: .84rem;
    }
    .item-card-row:last-child { border-bottom: none; }
    .item-idx { width: 22px; height: 22px; border-radius: 50%; background: #e8edf5;
        display: flex; align-items: center; justify-content: center;
        font-size: .68rem; font-weight: 700; color: #64748b; flex-shrink: 0; }
    .item-name { flex: 1; font-weight: 500; min-width: 0; overflow: hidden; text-overflow: ellipsis; }
    .item-meta { font-size: .78rem; color: #64748b; white-space: nowrap; }
    .item-amount { font-weight: 700; white-space: nowrap; }

    .pay-item { padding: .7rem 1rem; border-bottom: 1px solid #f8fafc; }
    .pay-item:last-child { border-bottom: none; }

    .log-item { padding: .6rem 1rem; border-bottom: 1px solid #f8fafc; }
    .log-item:last-child { border-bottom: none; }
</style>
@endpush

@section('content')

{{-- ── Header ── --}}
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;padding:.25rem .55rem;">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <span class="fw-bold" style="font-size:.95rem;">{{ $invoice->invoice_no }}</span>
            @if($invoice->is_finalized)
                <span class="badge bg-secondary ms-1" style="font-size:.68rem;"><i class="bi bi-lock-fill me-1"></i>Final</span>
            @else
                <span class="badge bg-warning text-dark ms-1" style="font-size:.68rem;">Draft</span>
            @endif
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank"
           class="btn btn-sm btn-outline-danger" style="border-radius:8px;">
            <i class="bi bi-file-pdf me-1"></i> PDF
        </a>
        @if(!$invoice->is_finalized)
            <a href="{{ route('invoices.edit', $invoice) }}"
               class="btn btn-sm btn-outline-primary" style="border-radius:8px;">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <form action="{{ route('invoices.finalize', $invoice) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Finalisasi invoice ini? Tidak bisa diedit setelah ini.')">
                @csrf
                <button class="btn btn-sm btn-success" type="submit" style="border-radius:8px;">
                    <i class="bi bi-check-circle me-1"></i> Finalisasi
                </button>
            </form>
        @endif
    </div>
</div>

<div class="row g-3">
    {{-- ── Left: Invoice Info + Items ── --}}
    <div class="col-12 col-lg-8">

        {{-- Status summary (mobile only) --}}
        <div class="d-lg-none mb-3">
            @php
                $status = $invoice->payment_status;
                $cls = match($status) { 'PAID'=>'badge-paid','PARTIAL'=>'badge-partial','OVERDUE'=>'badge-overdue',default=>'badge-unpaid' };
            @endphp
            <div class="card card-clean">
                <div class="card-body py-3 d-flex align-items-center justify-content-between gap-3">
                    <div>
                        <span class="badge {{ $cls }} mb-1" style="font-size:.78rem;padding:.3em .65em;">{{ $status }}</span>
                        <div class="fw-bold" style="font-size:1.05rem;">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</div>
                        @if($invoice->payments->isNotEmpty())
                            <div class="text-success" style="font-size:.78rem;">Terbayar: Rp {{ number_format($invoice->totalPaid(), 0, ',', '.') }}</div>
                        @endif
                    </div>
                    @if($invoice->isOverdue() && $status !== 'PAID')
                        <div class="text-center">
                            <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size:1.5rem;"></i>
                            <div style="font-size:.7rem;color:#dc2626;font-weight:700;">Jatuh Tempo</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Info Invoice --}}
        <div class="card card-clean mb-3">
            <div class="card-header">Info Invoice</div>
            <div class="card-body">
                <div class="row g-0">
                    <div class="col-6 col-md-4">
                        <div class="info-field">
                            <div class="lbl">Partner</div>
                            <div class="val">{{ $invoice->partner?->nama_partner ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-field">
                            <div class="lbl">Tamu</div>
                            <div class="val">{{ $invoice->guest_name ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-field">
                            <div class="lbl">Booking Pass</div>
                            <div class="val">{{ $invoice->booking_pass_no ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-field">
                            <div class="lbl">Tgl Kunjungan</div>
                            <div class="val">{{ $invoice->visit_date?->format('d/m/Y') ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-field">
                            <div class="lbl">Tgl Invoice</div>
                            <div class="val">{{ $invoice->invoice_date?->format('d/m/Y') }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-field">
                            <div class="lbl">Jatuh Tempo</div>
                            <div class="val {{ $invoice->isOverdue() ? 'text-danger fw-bold' : '' }}">
                                {{ $invoice->due_date?->format('d/m/Y') ?? '-' }}
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-field mb-0">
                            <div class="lbl">No. Transaksi DSI</div>
                            <div class="val">{{ $invoice->dsi_transaction_no ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="info-field mb-0">
                            <div class="lbl">Dibuat oleh</div>
                            <div class="val">{{ $invoice->creator?->full_name ?? '-' }}</div>
                        </div>
                    </div>
                </div>
                @if($invoice->notes)
                    <hr class="my-2">
                    <div class="lbl mb-1" style="font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;">Catatan</div>
                    <div style="font-size:.86rem;">{{ $invoice->notes }}</div>
                @endif
            </div>
        </div>

        {{-- Items --}}
        <div class="card card-clean mb-3">
            <div class="card-header">Item Invoice</div>
            <div class="p-0">
                @foreach($invoice->items as $i => $item)
                <div class="item-card-row">
                    <div class="item-idx">{{ $i + 1 }}</div>
                    <div class="item-name">{{ $item->product_name }}</div>
                    <div class="item-meta">{{ number_format($item->pax) }}× Rp {{ number_format($item->price_per_pax, 0, ',', '.') }}</div>
                    <div class="item-amount">Rp {{ number_format($item->amount, 0, ',', '.') }}</div>
                </div>
                @endforeach
                <div class="px-3 py-2" style="background:#f8fafc;border-top:1px solid #f1f5f9;">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:.82rem;color:#64748b;">Subtotal</span>
                        <span style="font-size:.82rem;font-weight:600;">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span style="font-size:.9rem;font-weight:700;">Grand Total</span>
                        <span style="font-size:.95rem;font-weight:800;color:#2563eb;">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</span>
                    </div>
                    @if($invoice->deposit > 0)
                    <hr class="my-2">
                    <div class="d-flex justify-content-between">
                        <span style="font-size:.8rem;color:#0891b2;"><i class="bi bi-wallet2 me-1"></i>Deposit</span>
                        <span style="font-size:.8rem;color:#0891b2;font-weight:600;">(Rp {{ number_format($invoice->deposit, 0, ',', '.') }})</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span style="font-size:.8rem;color:#64748b;">Sisa Tagihan</span>
                        <span style="font-size:.8rem;color:#64748b;">Rp {{ number_format(max(0, $invoice->grand_total - $invoice->totalPaid()), 0, ',', '.') }}</span>
                    </div>
                    @endif
                </div>
                <div class="px-3 py-2" style="border-top:1px solid #f1f5f9;">
                    <span class="fst-italic" style="font-size:.76rem;color:#94a3b8;">{{ $invoice->terbilang }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Right: Status + Payment + Log ── --}}
    <div class="col-12 col-lg-4">

        {{-- Status Card (desktop only) --}}
        <div class="d-none d-lg-block">
            <div class="card card-clean mb-3">
                <div class="card-body text-center py-3">
                    @php
                        $status = $invoice->payment_status;
                        $cls = match($status) { 'PAID'=>'badge-paid','PARTIAL'=>'badge-partial','OVERDUE'=>'badge-overdue',default=>'badge-unpaid' };
                    @endphp
                    <div class="badge {{ $cls }} mb-2" style="font-size:.82rem;padding:.35em .75em;">{{ $status }}</div>
                    <div class="fw-bold" style="font-size:1.2rem;">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</div>
                    @if($invoice->payments->isNotEmpty())
                        <div class="text-success" style="font-size:.8rem;">Terbayar: Rp {{ number_format($invoice->totalPaid(), 0, ',', '.') }}</div>
                        <div class="text-danger" style="font-size:.8rem;">Sisa: Rp {{ number_format($invoice->grand_total - $invoice->totalPaid(), 0, ',', '.') }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Add Payment Form --}}
        @if($invoice->is_finalized && $invoice->payment_status !== 'PAID')
        <div class="card card-clean mb-3">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-plus-circle text-success"></i> Tambah Pembayaran
            </div>
            <div class="card-body">
                <form action="{{ route('payments.store', $invoice) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">Jumlah <span class="text-danger">*</span></label>
                        <input type="text" inputmode="numeric" name="amount" class="form-control form-control-sm currency-input @error('amount') is-invalid @enderror"
                               value="{{ old('amount') }}" placeholder="0">
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="text-muted" style="font-size:.7rem;margin-top:2px;">
                            Sisa: Rp {{ number_format($invoice->grand_total - $invoice->totalPaid(), 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label form-label-sm fw-semibold mb-1">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control form-control-sm @error('payment_date') is-invalid @enderror"
                                   value="{{ old('payment_date', now()->toDateString()) }}">
                            @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm fw-semibold mb-1">Metode</label>
                            <select name="payment_method" class="form-select form-select-sm">
                                <option value="">— pilih —</option>
                                @foreach(['Transfer Bank','Cash','QRIS','Cek/Giro'] as $m)
                                    <option value="{{ $m }}" {{ old('payment_method') === $m ? 'selected' : '' }}>{{ $m }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">No. Referensi</label>
                        <input type="text" name="reference_no" class="form-control form-control-sm"
                               value="{{ old('reference_no') }}" placeholder="No. transaksi / cek">
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">Bukti Bayar <span class="text-muted" style="font-weight:400;">(jpg/png/pdf, max 5MB)</span></label>
                        <input type="file" name="proof_file" class="form-control form-control-sm @error('proof_file') is-invalid @enderror"
                               accept=".jpg,.jpeg,.png,.pdf">
                        @error('proof_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold mb-1">Catatan</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2">{{ old('notes') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100" style="border-radius:8px;">
                        <i class="bi bi-check-circle me-1"></i>Simpan Pembayaran
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Payments List --}}
        @if($invoice->payments->isNotEmpty())
        <div class="card card-clean mb-3">
            <div class="card-header">Riwayat Pembayaran</div>
            <div class="p-0">
                @foreach($invoice->payments->sortByDesc('payment_date') as $pay)
                @php $isDeposit = $pay->payment_method === 'Deposit'; @endphp
                <div class="pay-item {{ $isDeposit ? 'bg-info-subtle' : '' }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="fw-semibold" style="font-size:.85rem;">Rp {{ number_format($pay->amount, 0, ',', '.') }}</span>
                            <span class="text-muted ms-2" style="font-size:.76rem;">{{ $pay->payment_date?->format('d/m/Y') }}</span>
                            @if($isDeposit)
                                <span class="badge bg-info text-dark ms-1" style="font-size:.65rem;"><i class="bi bi-wallet-fill me-1"></i>Deposit</span>
                            @elseif($pay->payment_method)
                                <div class="text-muted" style="font-size:.75rem;">{{ $pay->payment_method }}{{ $pay->reference_no ? ' — '.$pay->reference_no : '' }}</div>
                            @endif
                            @if($pay->notes && !$isDeposit)
                                <div class="text-muted fst-italic" style="font-size:.75rem;">{{ $pay->notes }}</div>
                            @endif
                            @if($pay->proof_file)
                                <a href="{{ Storage::url($pay->proof_file) }}" target="_blank"
                                   class="text-primary text-decoration-none" style="font-size:.76rem;">
                                    <i class="bi bi-paperclip me-1"></i>Lihat Bukti
                                </a>
                            @endif
                        </div>
                        @if(!$isDeposit)
                        <form action="{{ route('payments.destroy', [$invoice, $pay]) }}" method="POST"
                              onsubmit="return confirm('Hapus pembayaran ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" style="font-size:.7rem;padding:.2rem .5rem;border-radius:6px;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Activity Log --}}
        @if($invoice->logs->isNotEmpty())
        <div class="card card-clean">
            <div class="card-header">Log Aktivitas</div>
            <div class="p-0">
                @foreach($invoice->logs->sortByDesc('created_at') as $log)
                <div class="log-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size:.65rem;">{{ $log->action }}</span>
                        <span class="text-muted" style="font-size:.68rem;">{{ $log->created_at?->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($log->description)
                    <div class="text-muted mt-1" style="font-size:.76rem;">{{ $log->description }}</div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

@endsection
