@extends('layouts.app')
@section('title', $depositInvoice->invoice_no)
@section('page-title', $depositInvoice->invoice_no)

@section('content')
@php
    $statusClass = match($depositInvoice->status) {
        'PAID'      => 'badge-paid',
        'SENT'      => 'bg-info text-dark',
        'CANCELLED' => 'bg-secondary',
        default     => 'bg-warning text-dark',
    };
@endphp

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <a href="{{ route('deposit-invoices.index') }}" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i>
        </a>
        <span class="fw-semibold">{{ $depositInvoice->invoice_no }}</span>
        <span class="badge {{ $statusClass }} ms-2">{{ $depositInvoice->status }}</span>
        @if(!$depositInvoice->is_finalized)
            <span class="badge bg-warning text-dark ms-1">Draft</span>
        @endif
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('deposit-invoices.pdf', $depositInvoice) }}" target="_blank" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-file-pdf me-1"></i> PDF
        </a>
        @if(!$depositInvoice->is_finalized)
            <a href="{{ route('deposit-invoices.edit', $depositInvoice) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <form action="{{ route('deposit-invoices.finalize', $depositInvoice) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Finalisasi invoice deposit ini? Tidak bisa diedit setelah ini.')">
                @csrf
                <button class="btn btn-sm btn-success" type="submit">
                    <i class="bi bi-check-circle me-1"></i> Finalisasi
                </button>
            </form>
        @endif
        @if($depositInvoice->is_finalized && $depositInvoice->status !== 'PAID' && $depositInvoice->status !== 'CANCELLED')
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalMarkPaid">
                <i class="bi bi-wallet-fill me-1"></i> Tandai Lunas
            </button>
            <form action="{{ route('deposit-invoices.cancel', $depositInvoice) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Batalkan invoice deposit ini?')">
                @csrf
                <button class="btn btn-sm btn-outline-danger" type="submit">
                    <i class="bi bi-x-circle me-1"></i> Batalkan
                </button>
            </form>
        @endif
        @if(!$depositInvoice->is_finalized)
        <form action="{{ route('deposit-invoices.destroy', $depositInvoice) }}" method="POST" class="d-inline"
              onsubmit="return confirm('Hapus invoice deposit ini?')">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger" type="submit">
                <i class="bi bi-trash me-1"></i> Hapus
            </button>
        </form>
        @endif
    </div>
</div>

<div class="row g-3">
    {{-- Main Info --}}
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom fw-semibold py-2">
                <i class="bi bi-info-circle me-2 text-primary"></i>Detail Invoice Deposit
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6 col-md-4">
                        <div class="text-muted small">Partner</div>
                        <div class="fw-semibold">{{ $depositInvoice->partner?->nama_partner ?? '-' }}</div>
                    </div>
                    @if($depositInvoice->partner?->nama_pt)
                    <div class="col-6 col-md-4">
                        <div class="text-muted small">Nama PT</div>
                        <div>{{ $depositInvoice->partner->nama_pt }}</div>
                    </div>
                    @endif
                    <div class="col-6 col-md-4">
                        <div class="text-muted small">Tgl Invoice</div>
                        <div>{{ $depositInvoice->invoice_date?->format('d/m/Y') }}</div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="text-muted small">Jatuh Tempo</div>
                        <div>{{ $depositInvoice->due_date?->format('d/m/Y') ?? '-' }}</div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="text-muted small">Dibuat oleh</div>
                        <div>{{ $depositInvoice->creator?->full_name ?? '-' }}</div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="text-muted small">Dibuat pada</div>
                        <div>{{ $depositInvoice->created_at?->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
                @if($depositInvoice->notes)
                    <hr class="my-2">
                    <div class="text-muted small">Catatan</div>
                    <div>{{ $depositInvoice->notes }}</div>
                @endif
            </div>
        </div>

        {{-- Jumlah --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom fw-semibold py-2">
                <i class="bi bi-cash-stack me-2 text-success"></i>Rincian Jumlah
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted">Jumlah Deposit Diminta</span>
                    <span class="fw-bold fs-5">Rp {{ number_format($depositInvoice->amount, 0, ',', '.') }}</span>
                </div>
                <div class="mt-2 p-2 bg-light rounded">
                    <span class="text-muted small fst-italic">{{ $depositInvoice->terbilang }}</span>
                </div>

                @if($depositInvoice->status === 'PAID' && $depositInvoice->depositRecord)
                    <div class="mt-3 alert alert-success py-2 mb-0">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Deposit diterima pada {{ $depositInvoice->depositRecord->created_at?->format('d/m/Y H:i') }}
                        @if($depositInvoice->depositRecord->reference_no)
                            — Ref: <strong>{{ $depositInvoice->depositRecord->reference_no }}</strong>
                        @endif
                        <div class="mt-1">
                            <a href="{{ route('deposits.index', $depositInvoice->partner) }}" class="alert-link small">
                                <i class="bi bi-arrow-right me-1"></i>Lihat Riwayat Deposit Partner
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-12 col-lg-4">
        {{-- Status Card --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body text-center py-4">
                <div class="badge {{ $statusClass }} fs-5 px-4 py-2 mb-3">{{ $depositInvoice->status }}</div>
                <div class="fw-bold fs-4">Rp {{ number_format($depositInvoice->amount, 0, ',', '.') }}</div>
                <div class="text-muted small mt-1">
                    @switch($depositInvoice->status)
                        @case('DRAFT') Belum dikirim ke partner @break
                        @case('SENT') Menunggu pembayaran dari partner @break
                        @case('PAID') Deposit telah diterima @break
                        @case('CANCELLED') Invoice telah dibatalkan @break
                    @endswitch
                </div>
            </div>
        </div>

        {{-- Partner Deposit Balance --}}
        @php $info = $depositInvoice->partner?->depositInfo(); @endphp
        @if($info)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom fw-semibold py-2">
                <i class="bi bi-wallet2 me-2"></i>Saldo Deposit Partner
            </div>
            <div class="card-body">
                <div class="fw-bold fs-5 {{ $info['is_low'] ? 'text-warning' : ($info['is_empty'] ? 'text-danger' : 'text-success') }}">
                    {{ $info['balance_formatted'] }}
                </div>
                @if($info['is_empty'])
                    <div class="text-danger small mt-1"><i class="bi bi-exclamation-triangle me-1"></i>Saldo kosong</div>
                @elseif($info['is_low'])
                    <div class="text-warning small mt-1"><i class="bi bi-exclamation-triangle me-1"></i>Saldo di bawah threshold</div>
                @endif
                <div class="mt-2">
                    <a href="{{ route('deposits.index', $depositInvoice->partner) }}" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-clock-history me-1"></i> Riwayat Deposit
                    </a>
                </div>
            </div>
        </div>
        @endif

        {{-- Quick Links --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom fw-semibold py-2">Link Cepat</div>
            <div class="card-body p-2">
                <a href="{{ route('partners.show', $depositInvoice->partner) }}" class="btn btn-outline-secondary btn-sm w-100 mb-1 text-start">
                    <i class="bi bi-person me-2"></i>Profil Partner
                </a>
                <a href="{{ route('deposit-invoices.create', ['partner_id' => $depositInvoice->partner_id]) }}" class="btn btn-outline-success btn-sm w-100 text-start">
                    <i class="bi bi-plus-circle me-2"></i>Buat Invoice Deposit Baru
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Mark as Paid --}}
@if($depositInvoice->is_finalized && $depositInvoice->status === 'SENT')
<div class="modal fade" id="modalMarkPaid" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold">
                    <i class="bi bi-wallet-fill me-2 text-success"></i>Tandai Deposit Diterima
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('deposit-invoices.mark-paid', $depositInvoice) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        Menandai invoice ini sebagai PAID akan <strong>menambah saldo deposit partner</strong>
                        sebesar <strong>Rp {{ number_format($depositInvoice->amount, 0, ',', '.') }}</strong>.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal Terima <span class="text-danger">*</span></label>
                        <input type="date" name="paid_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">No. Referensi / Transfer</label>
                        <input type="text" name="reference_no" class="form-control" placeholder="No. bukti transfer / referensi">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Catatan</label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Catatan opsional">Top-up via Invoice Deposit {{ $depositInvoice->invoice_no }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i> Konfirmasi Terima
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
