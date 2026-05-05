@extends('layouts.app')

@section('title', 'Partner')
@section('page-title', 'Manajemen Partner')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Daftar Partner</h5>
    <a href="{{ route('partners.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Tambah Partner
    </a>
</div>

<form method="GET" class="card card-body mb-3 py-2">
    <div class="row g-2">
        <div class="col-sm-6 col-md-5">
            <input type="text" name="search" class="form-control form-control-sm"
                   placeholder="Cari nama partner / PT / PIC..." value="{{ request('search') }}">
        </div>
        <div class="col-sm-4 col-md-3">
            <select name="type" class="form-select form-select-sm">
                <option value="">Semua Tipe</option>
                @foreach(['HOTEL','TRAVEL','TOURDESK'] as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-4 col-md-2">
            <select name="active" class="form-select form-select-sm">
                <option value="">Semua Status</option>
                <option value="1" @selected(request('active') === '1')>Aktif</option>
                <option value="0" @selected(request('active') === '0')>Nonaktif</option>
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
            <a href="{{ route('partners.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i></a>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Nama Partner</th>
                    <th>Nama PT</th>
                    <th>Tipe</th>
                    <th>PIC Partner</th>
                    <th>Kontrak Berakhir</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($partners as $i => $partner)
                <tr>
                    <td class="text-muted small">{{ $partners->firstItem() + $i }}</td>
                    <td class="fw-semibold">
                        <a href="{{ route('partners.show', $partner) }}" class="text-decoration-none">
                            {{ $partner->nama_partner }}
                        </a>
                    </td>
                    <td class="small text-muted">{{ $partner->nama_pt ?? '—' }}</td>
                    <td>
                        <span class="badge bg-{{ match($partner->partner_type) {
                            'HOTEL'    => 'info',
                            'TRAVEL'   => 'warning',
                            'TOURDESK' => 'success',
                            default    => 'secondary'
                        } }} text-dark">{{ $partner->partner_type }}</span>
                    </td>
                    <td class="small">{{ $partner->pic_partner ?? '—' }}</td>
                    <td class="small">
                        @if($partner->contract_end)
                            <span class="{{ $partner->isContractExpiringSoon() ? 'text-warning fw-semibold' : '' }}">
                                {{ $partner->contract_end->format('d/m/Y') }}
                            </span>
                            @if($partner->isContractExpiringSoon())
                                <i class="bi bi-exclamation-triangle-fill text-warning ms-1" title="Segera berakhir"></i>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if($partner->is_active)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-secondary">Nonaktif</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('partners.edit', $partner) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('partners.destroy', $partner) }}" class="d-inline"
                              onsubmit="return confirm('Hapus partner {{ $partner->nama_partner }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">Tidak ada partner ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($partners->hasPages())
    <div class="card-footer">{{ $partners->links() }}</div>
    @endif
</div>
@endsection
