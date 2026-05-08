@extends('layouts.app')

@section('title', 'Partner')
@section('page-title', 'Partner')

@push('styles')
<style>
    /* ── Page header ── */
    .page-hdr .page-title { font-size: 1.15rem; font-weight: 700; color: #1e293b; }

    /* ── Filter bar ── */
    .filter-bar {
        background: #fff;
        border: 1px solid #e8edf5;
        border-radius: 12px;
        padding: .85rem 1rem;
        margin-bottom: 1.1rem;
        box-shadow: 0 1px 4px rgba(15,23,41,.05);
    }
    .filter-bar .form-control,
    .filter-bar .form-select {
        border-radius: 8px; border-color: #e2e8f0;
        font-size: .82rem; padding: .38rem .7rem;
        background: #f8fafc;
        transition: background .12s, border-color .12s;
    }
    .filter-bar .form-control:focus,
    .filter-bar .form-select:focus {
        background: #fff; border-color: #818cf8; box-shadow: 0 0 0 3px rgba(129,140,248,.15);
    }

    /* ── Card ── */
    .partners-card {
        border-radius: 14px;
        border: 1px solid #e8edf5;
        box-shadow: 0 2px 8px rgba(15,23,41,.06);
        overflow: hidden;
        background: #fff;
    }

    /* ── Table ── */
    .partners-table thead th {
        background: #f1f5fd;
        font-size: .65rem;
        font-weight: 700;
        letter-spacing: .6px;
        text-transform: uppercase;
        color: #6b7a99;
        padding: .7rem 1rem;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }
    .partners-table tbody tr {
        transition: background .1s;
    }
    .partners-table tbody tr:hover { background: #f7f8ff; }
    .partners-table tbody td {
        padding: .7rem 1rem;
        font-size: .83rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .partners-table tbody tr:last-child td { border-bottom: none; }

    /* ── Row accent bar ── */
    .partners-table tbody tr td:first-child {
        border-left: 3px solid transparent;
    }
    .partners-table tbody tr.type-hotel td:first-child  { border-left-color: #06b6d4; }
    .partners-table tbody tr.type-travel td:first-child  { border-left-color: #f59e0b; }
    .partners-table tbody tr.type-tourdesk td:first-child{ border-left-color: #10b981; }

    /* ── Type badges ── */
    .badge-type {
        font-size: .67rem; font-weight: 600; padding: .25em .65em; border-radius: 6px;
        letter-spacing: .3px;
    }
    .badge-hotel   { background: #e0f9ff; color: #0891b2; }
    .badge-travel  { background: #fef3c7; color: #b45309; }
    .badge-tourdesk{ background: #d1fae5; color: #065f46; }
    .badge-other   { background: #f1f5f9; color: #64748b; }

    /* ── Credit badges ── */
    .badge-credit {
        font-size: .67rem; font-weight: 600; padding: .25em .65em; border-radius: 6px;
        display: inline-flex; align-items: center; gap: 3px;
    }
    .credit-safe    { background: #d1fae5; color: #065f46; }
    .credit-warn    { background: #fef3c7; color: #92400e; }
    .credit-high    { background: #ffe8d6; color: #9a3412; }
    .credit-over    { background: #fee2e2; color: #991b1b; }
    .credit-none    { background: #f1f5f9; color: #64748b; }
    .credit-paid    { background: #ede9fe; color: #5b21b6; }

    /* ── Active badge ── */
    .badge-active   { background: #d1fae5; color: #065f46; font-size:.67rem; border-radius:6px; padding:.25em .65em; font-weight:600; }
    .badge-inactive { background: #f1f5f9; color: #94a3b8; font-size:.67rem; border-radius:6px; padding:.25em .65em; font-weight:600; }

    /* ── Action buttons ── */
    .btn-act {
        width: 28px; height: 28px; border-radius: 7px;
        border: 1px solid #e2e8f0; background: #f8fafc; color: #64748b;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .78rem; text-decoration: none;
        transition: background .12s, color .12s, border-color .12s;
        cursor: pointer;
    }
    .btn-act.edit:hover  { background: #eff6ff; color: #4f46e5; border-color: #c7d2fe; }
    .btn-act.del:hover   { background: #fef2f2; color: #dc2626; border-color: #fca5a5; }

    /* ── Mobile card ── */
    .partner-card-item {
        padding: .9rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        border-left: 3px solid transparent;
    }
    .partner-card-item:last-child { border-bottom: none; }
    .partner-card-item:hover { background: #f7f8ff; }
    .partner-card-item.type-hotel   { border-left-color: #06b6d4; }
    .partner-card-item.type-travel  { border-left-color: #f59e0b; }
    .partner-card-item.type-tourdesk{ border-left-color: #10b981; }
    .partner-name { font-weight: 700; font-size: .88rem; color: #1e293b; }
    .partner-pt   { font-size: .76rem; color: #94a3b8; margin-top: 1px; }
    .partner-meta { font-size: .75rem; color: #64748b; margin-top: 4px; }

    /* ── Empty state ── */
    .empty-state { padding: 3rem 1rem; text-align: center; color: #94a3b8; }
    .empty-state .bi { font-size: 2rem; opacity: .4; }

    /* ── Pagination ── */
    .pagination-wrap { padding: .75rem 1rem; border-top: 1px solid #f1f5f9; }
</style>
@endpush

@section('content')

{{-- ── Header ── --}}
<div class="d-flex justify-content-between align-items-center mb-3 page-hdr">
    <div>
        <div class="page-title">Daftar Partner</div>
        <div style="font-size:.78rem;color:#94a3b8;margin-top:1px;">{{ $partners->total() }} partner terdaftar</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('partners.performance') }}" class="btn btn-sm d-flex align-items-center gap-1"
           style="border-radius:8px;background:#ede9fe;color:#5b21b6;border:1px solid #ddd6fe;font-size:.8rem;">
            <i class="bi bi-bar-chart-line"></i>
            <span class="d-none d-sm-inline">Scorecard</span>
        </a>
        <a href="{{ route('partners.create') }}" class="btn btn-sm d-flex align-items-center gap-1"
           style="border-radius:8px;background:#4f46e5;color:#fff;border:none;font-size:.8rem;">
            <i class="bi bi-plus-lg"></i>
            <span class="d-none d-sm-inline">Tambah Partner</span>
        </a>
    </div>
</div>

{{-- ── Filter ── --}}
<form method="GET" class="filter-bar">
    <div class="row g-2 align-items-center">
        <div class="col-12 col-sm-5">
            <input type="text" name="search" class="form-control form-control-sm"
                   placeholder="&#128269; Cari nama / PT / PIC..." value="{{ request('search') }}">
        </div>
        <div class="col-6 col-sm-3">
            <select name="type" class="form-select form-select-sm">
                <option value="">Semua Tipe</option>
                @foreach(['HOTEL','TRAVEL','TOURDESK'] as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-sm-2">
            <select name="active" class="form-select form-select-sm">
                <option value="">Semua Status</option>
                <option value="1" @selected(request('active') === '1')>Aktif</option>
                <option value="0" @selected(request('active') === '0')>Nonaktif</option>
            </select>
        </div>
        <div class="col-auto d-flex gap-2">
            <button class="btn btn-sm" type="submit"
                    style="border-radius:8px;padding:.35rem .8rem;background:#4f46e5;color:#fff;border:none;">
                <i class="bi bi-search"></i>
            </button>
            @if(request()->hasAny(['search','type','active']))
            <a href="{{ route('partners.index') }}" class="btn btn-sm btn-outline-secondary"
               style="border-radius:8px;padding:.35rem .8rem;">
                <i class="bi bi-x-lg"></i>
            </a>
            @endif
        </div>
    </div>
</form>

{{-- ── List ── --}}
<div class="partners-card">

    {{-- Desktop table --}}
    <div class="d-none d-lg-block">
        <table class="table partners-table mb-0">
            <thead>
                <tr>
                    <th style="width:42px;">#</th>
                    <th>Nama Partner</th>
                    <th>Nama PT</th>
                    <th>Tipe</th>
                    <th>PIC</th>
                    <th>Class</th>
                    <th>Kredit</th>
                    <th>Kontrak</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($partners as $i => $partner)
                @php
                    $typeClass = strtolower($partner->partner_type);
                    $cr = $creditData[$partner->id];
                @endphp
                <tr class="type-{{ $typeClass }}">
                    <td style="color:#94a3b8;">{{ $partners->firstItem() + $i }}</td>
                    <td style="font-weight:600;">
                        <a href="{{ route('partners.show', $partner) }}" class="text-decoration-none"
                           style="color:#1e293b;">{{ $partner->nama_partner }}</a>
                    </td>
                    <td style="color:#94a3b8;">{{ $partner->nama_pt ?? '—' }}</td>
                    <td>
                        @php
                            $typeBadgeClass = match($partner->partner_type) {
                                'HOTEL'    => 'badge-hotel',
                                'TRAVEL'   => 'badge-travel',
                                'TOURDESK' => 'badge-tourdesk',
                                default    => 'badge-other',
                            };
                        @endphp
                        <span class="badge-type {{ $typeBadgeClass }}">{{ $partner->partner_type }}</span>
                    </td>
                    <td style="color:#64748b;">{{ $partner->pic_partner ?? '—' }}</td>
                    <td>
                        @if($partner->creditClass)
                            <span class="badge bg-{{ $partner->creditClass->color }} text-dark" style="font-size:.67rem;">
                                {{ $partner->creditClass->name }}
                            </span>
                        @else
                            <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $creditBadgeClass = match($cr['color']) {
                                'success'   => ($cr['label'] === 'Lunas') ? 'credit-paid' : 'credit-safe',
                                'warning'   => 'credit-warn',
                                'orange'    => 'credit-high',
                                'danger'    => 'credit-over',
                                default     => 'credit-none',
                            };
                        @endphp
                        <span class="badge-credit {{ $creditBadgeClass }}" title="Outstanding: Rp {{ number_format($cr['outstanding'],0,',','.') }} / Limit: Rp {{ number_format($cr['limit'],0,',','.') }}">
                            @if($cr['color'] === 'danger') <i class="bi bi-exclamation-triangle-fill"></i> @endif
                            {{ $cr['label'] }}
                        </span>
                    </td>
                    <td>
                        @if($partner->contract_end)
                            <span class="{{ $partner->isContractExpiringSoon() ? 'fw-semibold' : '' }}"
                                  style="color:{{ $partner->isContractExpiringSoon() ? '#f59e0b' : '#64748b' }}">
                                {{ $partner->contract_end->format('d/m/Y') }}
                                @if($partner->isContractExpiringSoon())
                                    <i class="bi bi-exclamation-triangle-fill ms-1" style="color:#f59e0b;" title="Segera berakhir"></i>
                                @endif
                            </span>
                        @else
                            <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    <td>
                        @if($partner->is_active)
                            <span class="badge-active">Aktif</span>
                        @else
                            <span class="badge-inactive">Nonaktif</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('partners.edit', $partner) }}" class="btn-act edit" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('partners.destroy', $partner) }}" class="d-inline"
                                  onsubmit="return confirm('Hapus partner {{ $partner->nama_partner }}?')">
                                @csrf @method('DELETE')
                                <button class="btn-act del" title="Hapus"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="empty-state">
                        <i class="bi bi-inbox d-block mb-2"></i>
                        Tidak ada partner ditemukan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile card list --}}
    <div class="d-lg-none">
        @forelse($partners as $partner)
        @php
            $typeClass = strtolower($partner->partner_type);
            $cr = $creditData[$partner->id];
            $typeBadgeClass = match($partner->partner_type) {
                'HOTEL'    => 'badge-hotel',
                'TRAVEL'   => 'badge-travel',
                'TOURDESK' => 'badge-tourdesk',
                default    => 'badge-other',
            };
            $creditBadgeClass = match($cr['color']) {
                'success'   => ($cr['label'] === 'Lunas') ? 'credit-paid' : 'credit-safe',
                'warning'   => 'credit-warn',
                'orange'    => 'credit-high',
                'danger'    => 'credit-over',
                default     => 'credit-none',
            };
        @endphp
        <div class="partner-card-item type-{{ $typeClass }}">
            <div class="d-flex align-items-start justify-content-between gap-2">
                <div class="min-w-0 flex-grow-1">
                    <a href="{{ route('partners.show', $partner) }}" class="partner-name text-decoration-none">
                        {{ $partner->nama_partner }}
                    </a>
                    @if($partner->nama_pt)
                        <div class="partner-pt">{{ $partner->nama_pt }}</div>
                    @endif
                    <div class="partner-meta d-flex align-items-center gap-2 mt-1 flex-wrap">
                        <span class="badge-type {{ $typeBadgeClass }}">{{ $partner->partner_type }}</span>
                        @if($partner->creditClass)
                            <span class="badge bg-{{ $partner->creditClass->color }} text-dark" style="font-size:.67rem;">{{ $partner->creditClass->name }}</span>
                        @endif
                        <span class="badge-credit {{ $creditBadgeClass }}">
                            @if($cr['color'] === 'danger') <i class="bi bi-exclamation-triangle-fill"></i> @endif
                            {{ $cr['label'] }}
                        </span>
                        @if($partner->pic_partner)
                            <span><i class="bi bi-person me-1"></i>{{ $partner->pic_partner }}</span>
                        @endif
                        @if($partner->contract_end)
                            <span style="color:{{ $partner->isContractExpiringSoon() ? '#f59e0b' : 'inherit' }}">
                                <i class="bi bi-calendar me-1"></i>{{ $partner->contract_end->format('d/m/Y') }}
                                @if($partner->isContractExpiringSoon()) ⚠️ @endif
                            </span>
                        @endif
                    </div>
                </div>
                <div class="d-flex flex-column align-items-end gap-2 flex-shrink-0">
                    @if($partner->is_active)
                        <span class="badge-active">Aktif</span>
                    @else
                        <span class="badge-inactive">Nonaktif</span>
                    @endif
                    <div class="d-flex gap-1">
                        <a href="{{ route('partners.edit', $partner) }}" class="btn-act edit" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('partners.destroy', $partner) }}"
                              onsubmit="return confirm('Hapus partner {{ $partner->nama_partner }}?')">
                            @csrf @method('DELETE')
                            <button class="btn-act del" title="Hapus"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="empty-state">
            <i class="bi bi-inbox d-block mb-2"></i>
            Tidak ada partner ditemukan.
        </div>
        @endforelse
    </div>

    @if($partners->hasPages())
    <div class="pagination-wrap">
        {{ $partners->links() }}
    </div>
    @endif
</div>

@endsection
