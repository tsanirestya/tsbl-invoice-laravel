@extends('layouts.app')

@section('title', $partner->nama_partner)
@section('page-title', 'Detail Partner')

@section('content')
<div class="d-flex gap-2 mb-3">
    <a href="{{ route('partners.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
    <a href="{{ route('partners.edit', $partner) }}" class="btn btn-sm btn-primary">
        <i class="bi bi-pencil me-1"></i> Edit
    </a>
</div>

<div class="row g-3">
    {{-- Info Utama --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Informasi Umum</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Nama Partner</dt>
                    <dd class="col-7 fw-semibold">{{ $partner->nama_partner }}</dd>

                    <dt class="col-5 text-muted">Nama PT</dt>
                    <dd class="col-7">{{ $partner->nama_pt ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Tipe</dt>
                    <dd class="col-7">
                        <span class="badge bg-{{ match($partner->partner_type) {
                            'HOTEL'=>'info','TRAVEL'=>'warning','TOURDESK'=>'success',default=>'secondary'
                        } }} text-dark">{{ $partner->partner_type }}</span>
                    </dd>

                    <dt class="col-5 text-muted">Kategori</dt>
                    <dd class="col-7">{{ $partner->category ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Channel</dt>
                    <dd class="col-7">{{ $partner->channel ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Status</dt>
                    <dd class="col-7">
                        @if($partner->is_active)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-secondary">Nonaktif</span>
                        @endif
                    </dd>

                    <dt class="col-5 text-muted">Alamat</dt>
                    <dd class="col-7">{{ $partner->address ?? '—' }}</dd>

                    <dt class="col-5 text-muted">NPWP</dt>
                    <dd class="col-7">{{ $partner->npwp ?? '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    {{-- PIC & Kontrak --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">PIC & Kontrak</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">PIC TSBL</dt>
                    <dd class="col-7">{{ $partner->pic_tsbl ?? '—' }}</dd>

                    <dt class="col-5 text-muted">PIC Partner</dt>
                    <dd class="col-7">{{ $partner->pic_partner ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Telepon PIC</dt>
                    <dd class="col-7">{{ $partner->pic_partner_phone ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Email PIC</dt>
                    <dd class="col-7">{{ $partner->pic_partner_email ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Kontrak Mulai</dt>
                    <dd class="col-7">{{ $partner->contract_start?->format('d/m/Y') ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Kontrak Selesai</dt>
                    <dd class="col-7">
                        {{ $partner->contract_end?->format('d/m/Y') ?? '—' }}
                        @if($partner->isContractExpiringSoon())
                            <span class="badge bg-warning text-dark ms-1">Segera berakhir</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    {{-- Bank & Pembayaran --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header fw-semibold">Bank & Pembayaran</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Bank</dt>
                    <dd class="col-7">{{ $partner->bank_name ?? '—' }}</dd>

                    <dt class="col-5 text-muted">No. Rekening</dt>
                    <dd class="col-7">{{ $partner->bank_account_no ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Atas Nama</dt>
                    <dd class="col-7">{{ $partner->bank_account_name ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Tipe Pembayaran</dt>
                    <dd class="col-7">{{ $partner->payment_type ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Jatuh Tempo</dt>
                    <dd class="col-7">{{ $partner->payment_due_days }} hari</dd>

                    <dt class="col-5 text-muted">Limit Kredit</dt>
                    <dd class="col-7">Rp {{ number_format($partner->limit_credit, 0, ',', '.') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    {{-- Dokumen --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header fw-semibold">Dokumen Legal</div>
            <div class="card-body">
                @php
                    $docs = [
                        'doc_akta_pendirian' => 'Akta Pendirian',
                        'doc_akta_perubahan' => 'Akta Perubahan',
                        'doc_surat_kuasa'    => 'Surat Kuasa',
                        'doc_ktp'            => 'KTP',
                        'doc_nib'            => 'NIB',
                        'doc_npwp'           => 'NPWP',
                    ];
                @endphp
                <ul class="list-group list-group-flush">
                    @foreach($docs as $field => $label)
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center small">
                        <span class="text-muted">{{ $label }}</span>
                        @if($partner->$field)
                            <a href="{{ Storage::url($partner->$field) }}" target="_blank"
                               class="btn btn-sm btn-outline-primary py-0 px-2">
                                <i class="bi bi-eye me-1"></i>Lihat
                            </a>
                        @else
                            <span class="badge bg-light text-secondary">Belum ada</span>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    @if($partner->notes)
    <div class="col-12">
        <div class="card">
            <div class="card-header fw-semibold">Catatan</div>
            <div class="card-body small">{{ $partner->notes }}</div>
        </div>
    </div>
    @endif
</div>
@endsection
