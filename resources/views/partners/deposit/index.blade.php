@extends('layouts.app')

@section('title', 'Riwayat Deposit — ' . $partner->nama_partner)
@section('page-title', 'Riwayat Deposit Partner')

@section('content')
<div class="d-flex gap-2 mb-3">
    <a href="{{ route('partners.show', $partner) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Partner
    </a>
    <a href="{{ route('deposit-invoices.create', ['partner_id' => $partner->id]) }}" class="btn btn-sm btn-success">
        <i class="bi bi-plus-circle me-1"></i> Top-up Deposit
    </a>
    @if(in_array(auth()->user()->user_status, ['ADMIN', 'FINANCE']))
    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalAdjustment">
        <i class="bi bi-sliders me-1"></i> Adjustment
    </button>
    @endif
</div>

{{-- Balance Card --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm
            @if($info['is_empty']) border-start border-danger border-4
            @elseif($info['is_low']) border-start border-warning border-4
            @else border-start border-success border-4 @endif">
            <div class="card-body py-3 px-4">
                <div class="text-muted small fw-semibold text-uppercase mb-1">Saldo Deposit</div>
                <div class="fw-bold fs-4
                    @if($info['is_empty']) text-danger
                    @elseif($info['is_low']) text-warning
                    @else text-success @endif">
                    {{ $info['balance_formatted'] }}
                </div>
                @if($info['is_empty'])
                    <span class="badge bg-danger mt-1">Saldo Habis</span>
                @elseif($info['is_low'])
                    <span class="badge bg-warning text-dark mt-1">Deposit Rendah</span>
                    <div class="text-muted small mt-1">Threshold: Rp {{ number_format($info['threshold'], 0, ',', '.') }}</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 px-4">
                <div class="text-muted small fw-semibold text-uppercase mb-1">Partner</div>
                <div class="fw-bold fs-5">{{ $partner->nama_partner }}</div>
                @if($partner->nama_pt)<div class="text-muted small">{{ $partner->nama_pt }}</div>@endif
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2">
        {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Transaction History --}}
<div class="card shadow-sm">
    <div class="card-header bg-white py-3 fw-semibold">
        <i class="bi bi-clock-history me-2 text-primary"></i>Riwayat Transaksi
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Tanggal</th>
                        <th>Tipe</th>
                        <th>Nominal</th>
                        <th>Referensi</th>
                        <th>Invoice</th>
                        <th>Catatan</th>
                        <th>Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deposits as $d)
                    <tr>
                        <td class="ps-3 text-muted small">{{ $d->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            @if($d->type === 'TOPUP')
                                <span class="badge bg-success">TOPUP</span>
                            @elseif($d->type === 'DEDUCTION')
                                <span class="badge bg-danger">DEDUCTION</span>
                            @else
                                <span class="badge bg-warning text-dark">ADJUSTMENT</span>
                            @endif
                        </td>
                        <td class="fw-semibold
                            @if($d->type === 'TOPUP') text-success
                            @elseif($d->type === 'DEDUCTION') text-danger
                            @else text-warning @endif">
                            @if($d->type === 'DEDUCTION')-(Rp {{ number_format($d->amount, 0, ',', '.') }})
                            @elseif($d->type === 'ADJUSTMENT' && $d->amount < 0)(Rp {{ number_format(abs($d->amount), 0, ',', '.') }})
                            @else+Rp {{ number_format($d->amount, 0, ',', '.') }}
                            @endif
                        </td>
                        <td class="small text-muted">{{ $d->reference_no ?? '—' }}</td>
                        <td class="small">
                            @if($d->invoice)
                                <a href="{{ route('invoices.show', $d->invoice) }}" class="text-decoration-none">
                                    {{ $d->invoice->invoice_no }}
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="small text-muted" style="max-width:180px">{{ $d->notes ?? '—' }}</td>
                        <td class="small text-muted">{{ $d->creator->full_name ?? $d->creator->name ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Belum ada transaksi deposit
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($deposits->hasPages())
    <div class="card-footer bg-white">
        {{ $deposits->links() }}
    </div>
    @endif
</div>

{{-- Adjustment Modal (ADMIN & FINANCE) --}}
@if(in_array(auth()->user()->user_status, ['ADMIN', 'FINANCE']))
<div class="modal fade" id="modalAdjustment" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('deposits.adjustment', $partner) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Koreksi / Adjustment Deposit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nominal Adjustment</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="amount" class="form-control" required
                                   placeholder="Positif = tambah saldo, Negatif = kurangi" step="1000">
                        </div>
                        <div class="form-text">Contoh: 500000 (tambah) atau -200000 (kurang)</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alasan / Catatan <span class="text-danger">*</span></label>
                        <textarea name="notes" class="form-control" rows="3" required placeholder="Wajib isi alasan adjustment..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Simpan Adjustment</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

@endsection
