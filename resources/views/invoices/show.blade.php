@extends('layouts.app')
@section('title', $invoice->invoice_no)
@section('page-title', $invoice->invoice_no)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i>
        </a>
        <span class="fw-semibold">{{ $invoice->invoice_no }}</span>
        @if($invoice->is_finalized)
            <span class="badge bg-secondary ms-2"><i class="bi bi-lock-fill me-1"></i>Final</span>
        @else
            <span class="badge bg-warning text-dark ms-2">Draft</span>
        @endif
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-file-pdf me-1"></i> PDF
        </a>
        <form action="{{ route('invoices.duplicate', $invoice) }}" method="POST" class="d-inline">
            @csrf
            <button class="btn btn-sm btn-outline-secondary" type="submit">
                <i class="bi bi-copy me-1"></i> Duplikat
            </button>
        </form>
        @if(!$invoice->is_finalized)
            <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <form action="{{ route('invoices.finalize', $invoice) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Finalisasi invoice ini? Tidak bisa diedit setelah ini.')">
                @csrf
                <button class="btn btn-sm btn-success" type="submit">
                    <i class="bi bi-check-circle me-1"></i> Finalisasi
                </button>
            </form>
        @endif
    </div>
</div>

<div class="row g-3">
    {{-- Invoice Info --}}
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom fw-semibold py-2">Info Invoice</div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6 col-md-4">
                        <div class="text-muted small">Partner</div>
                        <div class="fw-semibold">{{ $invoice->partner?->nama_partner ?? '-' }}</div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="text-muted small">Tamu</div>
                        <div>{{ $invoice->guest_name ?? '-' }}</div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="text-muted small">Booking Pass No</div>
                        <div>{{ $invoice->booking_pass_no ?? '-' }}</div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="text-muted small">Tanggal Kunjungan</div>
                        <div>{{ $invoice->visit_date?->format('d/m/Y') ?? '-' }}</div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="text-muted small">Tgl Invoice</div>
                        <div>{{ $invoice->invoice_date?->format('d/m/Y') }}</div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="text-muted small">Jatuh Tempo</div>
                        <div class="{{ $invoice->isOverdue() ? 'text-danger fw-semibold' : '' }}">
                            {{ $invoice->due_date?->format('d/m/Y') ?? '-' }}
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="text-muted small">No. Transaksi DSI</div>
                        <div>{{ $invoice->dsi_transaction_no ?? '-' }}</div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="text-muted small">Dibuat oleh</div>
                        <div>{{ $invoice->creator?->full_name ?? '-' }}</div>
                    </div>
                </div>
                @if($invoice->notes)
                    <hr class="my-2">
                    <div class="text-muted small">Catatan</div>
                    <div>{{ $invoice->notes }}</div>
                @endif
            </div>
        </div>

        {{-- Items --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom fw-semibold py-2">Item Invoice</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Produk / Layanan</th>
                                <th class="text-center">Pax</th>
                                <th class="text-end">Harga/Pax</th>
                                <th class="text-end pe-3">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $i => $item)
                            <tr>
                                <td class="ps-3 text-muted">{{ $i + 1 }}</td>
                                <td>{{ $item->product_name }}</td>
                                <td class="text-center">{{ number_format($item->pax) }}</td>
                                <td class="text-end">{{ number_format($item->price_per_pax, 0, ',', '.') }}</td>
                                <td class="text-end pe-3">{{ number_format($item->amount, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end fw-semibold ps-3">Subtotal</td>
                                <td class="text-end pe-3">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @if($invoice->deposit > 0)
                            <tr>
                                <td colspan="4" class="text-end ps-3">Deposit</td>
                                <td class="text-end pe-3 text-muted">(Rp {{ number_format($invoice->deposit, 0, ',', '.') }})</td>
                            </tr>
                            @endif
                            <tr>
                                <td colspan="4" class="text-end fw-bold ps-3">Grand Total</td>
                                <td class="text-end pe-3 fw-bold fs-6">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="px-3 py-2 text-muted small fst-italic border-top">
                    {{ $invoice->terbilang }}
                </div>
            </div>
        </div>
    </div>

    {{-- Sidebar: status + payments + logs --}}
    <div class="col-12 col-lg-4">
        {{-- Status Card --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body text-center py-3">
                @php
                    $status = $invoice->payment_status;
                    $cls = match($status) {
                        'PAID'    => 'badge-paid',
                        'PARTIAL' => 'badge-partial',
                        'OVERDUE' => 'badge-overdue',
                        default   => 'badge-unpaid',
                    };
                @endphp
                <div class="badge {{ $cls }} fs-6 px-3 py-2 mb-2">{{ $status }}</div>
                <div class="fw-bold fs-5">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</div>
                @if($invoice->payments->isNotEmpty())
                    <div class="text-success small">Terbayar: Rp {{ number_format($invoice->totalPaid(), 0, ',', '.') }}</div>
                    <div class="text-danger small">Sisa: Rp {{ number_format($invoice->grand_total - $invoice->totalPaid(), 0, ',', '.') }}</div>
                @endif
            </div>
        </div>

        {{-- Add Payment Form (finalized only, not fully paid) --}}
        @if($invoice->is_finalized && $invoice->payment_status !== 'PAID')
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom fw-semibold py-2">
                <i class="bi bi-plus-circle me-1 text-success"></i>Tambah Pembayaran
            </div>
            <div class="card-body">
                <form action="{{ route('payments.store', $invoice) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label form-label-sm mb-1">Jumlah <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control form-control-sm @error('amount') is-invalid @enderror"
                                   step="0.01" min="0.01"
                                   max="{{ $invoice->grand_total - $invoice->totalPaid() }}"
                                   value="{{ old('amount') }}" placeholder="0">
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="text-muted" style="font-size:.72rem">
                                Sisa: Rp {{ number_format($invoice->grand_total - $invoice->totalPaid(), 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm mb-1">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control form-control-sm @error('payment_date') is-invalid @enderror"
                                   value="{{ old('payment_date', now()->toDateString()) }}">
                            @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm mb-1">Metode</label>
                            <select name="payment_method" class="form-select form-select-sm">
                                <option value="">— pilih —</option>
                                @foreach(['Transfer Bank','Cash','QRIS','Cek/Giro'] as $m)
                                    <option value="{{ $m }}" {{ old('payment_method') === $m ? 'selected' : '' }}>{{ $m }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label form-label-sm mb-1">No. Referensi</label>
                            <input type="text" name="reference_no" class="form-control form-control-sm"
                                   value="{{ old('reference_no') }}" placeholder="No. transaksi / cek">
                        </div>
                        <div class="col-12">
                            <label class="form-label form-label-sm mb-1">Bukti Bayar <span class="text-muted small">(jpg/png/pdf, max 5MB)</span></label>
                            <input type="file" name="proof_file" class="form-control form-control-sm @error('proof_file') is-invalid @enderror"
                                   accept=".jpg,.jpeg,.png,.pdf">
                            @error('proof_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label form-label-sm mb-1">Catatan</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2"
                                      placeholder="Catatan opsional">{{ old('notes') }}</textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="bi bi-check-circle me-1"></i>Simpan Pembayaran
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endif

        {{-- Payments List --}}
        @if($invoice->payments->isNotEmpty())
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom fw-semibold py-2">Riwayat Pembayaran</div>
            <div class="card-body p-0">
                @foreach($invoice->payments->sortByDesc('payment_date') as $pay)
                <div class="px-3 py-2 border-bottom">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="fw-semibold">Rp {{ number_format($pay->amount, 0, ',', '.') }}</span>
                            <span class="text-muted small ms-2">{{ $pay->payment_date?->format('d/m/Y') }}</span>
                            @if($pay->payment_method)
                                <div class="text-muted small">{{ $pay->payment_method }}{{ $pay->reference_no ? ' — '.$pay->reference_no : '' }}</div>
                            @endif
                            @if($pay->notes)
                                <div class="text-muted small fst-italic">{{ $pay->notes }}</div>
                            @endif
                            @if($pay->proof_file)
                                <a href="{{ Storage::url($pay->proof_file) }}" target="_blank"
                                   class="small text-primary text-decoration-none">
                                    <i class="bi bi-paperclip me-1"></i>Lihat Bukti
                                </a>
                            @endif
                        </div>
                        <form action="{{ route('payments.destroy', [$invoice, $pay]) }}" method="POST" class="ms-2"
                              onsubmit="return confirm('Hapus pembayaran ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-xs btn-outline-danger px-2 py-1" style="font-size:.7rem" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Activity Log --}}
        @if($invoice->logs->isNotEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom fw-semibold py-2">Log Aktivitas</div>
            <div class="card-body p-0">
                @foreach($invoice->logs->sortByDesc('created_at') as $log)
                <div class="px-3 py-2 border-bottom">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="badge bg-secondary-subtle text-secondary-emphasis small">{{ $log->action }}</span>
                        <span class="text-muted" style="font-size:.7rem">{{ $log->created_at?->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($log->description)
                    <div class="text-muted small mt-1">{{ $log->description }}</div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
