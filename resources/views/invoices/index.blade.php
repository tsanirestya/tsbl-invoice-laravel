@extends('layouts.app')
@section('title', 'Invoice')
@section('page-title', 'Invoice')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-semibold">Daftar Invoice</h5>
    <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Buat Invoice
    </a>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('invoices.index') }}" class="row g-2 align-items-end">
            <div class="col-12 col-sm-4 col-md-3">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="No invoice / tamu / partner"
                       value="{{ request('search') }}">
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    @foreach(['UNPAID','PARTIAL','PAID','OVERDUE'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-3 col-md-3">
                <select name="partner_id" class="form-select form-select-sm">
                    <option value="">Semua Partner</option>
                    @foreach($partners as $p)
                        <option value="{{ $p->id }}" @selected(request('partner_id') == $p->id)>{{ $p->nama_partner }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="{{ request('date_from') }}" placeholder="Dari">
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="{{ request('date_to') }}" placeholder="Sampai">
            </div>
            <div class="col-auto d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
                <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($invoices->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                Belum ada invoice.
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">No Invoice</th>
                        <th>Partner</th>
                        <th class="d-none d-md-table-cell">Tamu</th>
                        <th class="d-none d-sm-table-cell">Tgl Invoice</th>
                        <th class="d-none d-md-table-cell">Jatuh Tempo</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Status</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $inv)
                    <tr>
                        <td class="ps-3">
                            <a href="{{ route('invoices.show', $inv) }}" class="fw-semibold text-decoration-none">
                                {{ $inv->invoice_no }}
                            </a>
                            @if($inv->is_finalized)
                                <i class="bi bi-lock-fill text-muted ms-1" style="font-size:.7rem" title="Final"></i>
                            @endif
                        </td>
                        <td>{{ $inv->partner?->nama_partner ?? '-' }}</td>
                        <td class="d-none d-md-table-cell">{{ $inv->guest_name ?? '-' }}</td>
                        <td class="d-none d-sm-table-cell">{{ $inv->invoice_date?->format('d/m/Y') }}</td>
                        <td class="d-none d-md-table-cell">
                            <span class="{{ $inv->isOverdue() ? 'text-danger fw-semibold' : '' }}">
                                {{ $inv->due_date?->format('d/m/Y') }}
                            </span>
                        </td>
                        <td class="text-end">Rp {{ number_format($inv->grand_total, 0, ',', '.') }}</td>
                        <td class="text-center">
                            @php
                                $status = $inv->payment_status;
                                $cls = match($status) {
                                    'PAID'    => 'badge-paid',
                                    'PARTIAL' => 'badge-partial',
                                    'OVERDUE' => 'badge-overdue',
                                    default   => 'badge-unpaid',
                                };
                            @endphp
                            <span class="badge {{ $cls }}">{{ $status }}</span>
                        </td>
                        <td class="text-center pe-3">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle py-0" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('invoices.show', $inv) }}">
                                        <i class="bi bi-eye me-2"></i>Detail</a></li>
                                    <li><a class="dropdown-item" href="{{ route('invoices.pdf', $inv) }}" target="_blank">
                                        <i class="bi bi-file-pdf me-2"></i>PDF</a></li>
                                    @if(!$inv->is_finalized)
                                    <li><a class="dropdown-item" href="{{ route('invoices.edit', $inv) }}">
                                        <i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    <li>
                                        <form action="{{ route('invoices.finalize', $inv) }}" method="POST" onsubmit="return confirm('Finalisasi invoice {{ $inv->invoice_no }}? Tidak bisa diedit setelah ini.')">
                                            @csrf
                                            <button class="dropdown-item text-success" type="submit">
                                                <i class="bi bi-check-circle me-2"></i>Finalisasi
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                    <li>
                                        <form action="{{ route('invoices.duplicate', $inv) }}" method="POST">
                                            @csrf
                                            <button class="dropdown-item" type="submit">
                                                <i class="bi bi-copy me-2"></i>Duplikat
                                            </button>
                                        </form>
                                    </li>
                                    @if(!$inv->is_finalized)
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('invoices.destroy', $inv) }}" method="POST" onsubmit="return confirm('Hapus invoice ini?')">
                                            @csrf @method('DELETE')
                                            <button class="dropdown-item text-danger" type="submit">
                                                <i class="bi bi-trash me-2"></i>Hapus
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-3 py-2">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.table-responsive .dropdown-toggle').forEach(function (el) {
    new bootstrap.Dropdown(el, {
        popperConfig: function (defaultConfig) {
            return Object.assign({}, defaultConfig, { strategy: 'fixed' });
        }
    });
});
</script>
@endpush
