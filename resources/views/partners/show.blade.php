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

    {{-- Payment Scorecard --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center fw-semibold">
                <span>Scorecard Pembayaran</span>
                @if($scorecard['risk_grade'] !== 'N/A')
                    <span class="badge bg-{{ $scorecard['risk_color'] }} fs-6 px-3">Grade {{ $scorecard['risk_grade'] }}</span>
                @else
                    <span class="badge bg-secondary">Belum Ada Data</span>
                @endif
            </div>
            <div class="card-body">
                {{-- Key metrics --}}
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="p-2 bg-light rounded text-center">
                            <div class="fs-4 fw-bold text-success">{{ $scorecard['paid_on_time'] }}</div>
                            <div class="small text-muted">Tepat Waktu</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 bg-light rounded text-center">
                            <div class="fs-4 fw-bold text-warning">{{ $scorecard['paid_late'] }}</div>
                            <div class="small text-muted">Terlambat</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 bg-light rounded text-center">
                            <div class="fs-4 fw-bold text-danger">{{ $scorecard['overdue_count'] + $scorecard['unpaid_count'] }}</div>
                            <div class="small text-muted">Belum Dibayar</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 bg-light rounded text-center">
                            <div class="fs-4 fw-bold">
                                {{ $scorecard['on_time_rate'] !== null ? $scorecard['on_time_rate'].'%' : '—' }}
                            </div>
                            <div class="small text-muted">On-Time Rate</div>
                        </div>
                    </div>
                </div>

                {{-- Detail metrics --}}
                <dl class="row mb-3 small">
                    <dt class="col-5 col-md-3 text-muted">Total Invoice</dt>
                    <dd class="col-7 col-md-9">{{ $scorecard['total_invoices'] }}</dd>

                    <dt class="col-5 col-md-3 text-muted">Total Tagihan</dt>
                    <dd class="col-7 col-md-9">Rp {{ number_format($scorecard['total_billed'], 0, ',', '.') }}</dd>

                    <dt class="col-5 col-md-3 text-muted">Total Dibayar</dt>
                    <dd class="col-7 col-md-9">Rp {{ number_format($scorecard['total_paid'], 0, ',', '.') }}</dd>

                    <dt class="col-5 col-md-3 text-muted">Outstanding</dt>
                    <dd class="col-7 col-md-9">
                        <span class="{{ $scorecard['outstanding'] > 0 ? 'text-danger fw-semibold' : 'text-success' }}">
                            Rp {{ number_format($scorecard['outstanding'], 0, ',', '.') }}
                        </span>
                    </dd>

                    @if($scorecard['partial_count'] > 0)
                    <dt class="col-5 col-md-3 text-muted">Partial</dt>
                    <dd class="col-7 col-md-9">{{ $scorecard['partial_count'] }} invoice</dd>
                    @endif

                    @if($scorecard['avg_days_late'] > 0)
                    <dt class="col-5 col-md-3 text-muted">Avg Hari Terlambat</dt>
                    <dd class="col-7 col-md-9 text-warning">+{{ $scorecard['avg_days_late'] }} hari</dd>
                    @endif

                    @if($scorecard['credit_utilization'] !== null)
                    <dt class="col-5 col-md-3 text-muted">Credit Utilization</dt>
                    <dd class="col-7 col-md-9">
                        @php $cu = $scorecard['credit_utilization']; @endphp
                        <span class="{{ $cu > 100 ? 'text-danger fw-semibold' : ($cu > 50 ? 'text-warning' : 'text-success') }}">
                            {{ $cu }}%
                        </span>
                        <small class="text-muted">(limit Rp {{ number_format($partner->limit_credit, 0, ',', '.') }})</small>
                    </dd>
                    @endif

                    @if($scorecard['last_payment_date'])
                    <dt class="col-5 col-md-3 text-muted">Terakhir Bayar</dt>
                    <dd class="col-7 col-md-9">
                        {{ \Carbon\Carbon::parse($scorecard['last_payment_date'])->format('d/m/Y') }}
                    </dd>
                    @endif
                </dl>

                {{-- Recent invoices table --}}
                @if($recentInvoices->count() > 0)
                <h6 class="small text-uppercase text-muted fw-semibold mb-2">10 Invoice Terakhir</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>No Invoice</th>
                                <th>Tgl Invoice</th>
                                <th>Jatuh Tempo</th>
                                <th class="text-end">Grand Total</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Ketepatan Bayar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentInvoices as $inv)
                            @php
                                $lastPay = $inv->payments->sortByDesc('payment_date')->first();
                                $timeliness = null;
                                $daysLate   = 0;
                                if ($inv->payment_status === 'PAID' && $lastPay && $inv->due_date) {
                                    $due  = \Carbon\Carbon::parse($inv->due_date);
                                    $paid = \Carbon\Carbon::parse($lastPay->payment_date);
                                    if ($paid->lte($due)) {
                                        $timeliness = 'ontime';
                                    } else {
                                        $timeliness = 'late';
                                        $daysLate   = $paid->diffInDays($due);
                                    }
                                }
                                $statusColors = ['PAID'=>'success','UNPAID'=>'secondary','PARTIAL'=>'info','OVERDUE'=>'danger'];
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('invoices.show', $inv) }}" class="text-decoration-none">
                                        {{ $inv->invoice_no }}
                                    </a>
                                </td>
                                <td>{{ $inv->invoice_date->format('d/m/Y') }}</td>
                                <td>{{ $inv->due_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="text-end">Rp {{ number_format($inv->grand_total, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $statusColors[$inv->payment_status] ?? 'secondary' }}">
                                        {{ $inv->payment_status }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($timeliness === 'ontime')
                                        <span class="text-success">
                                            <i class="bi bi-check-circle-fill me-1"></i>Tepat Waktu
                                        </span>
                                    @elseif($timeliness === 'late')
                                        <span class="text-warning">
                                            <i class="bi bi-clock-fill me-1"></i>+{{ $daysLate }} hari
                                        </span>
                                    @elseif($inv->payment_status === 'OVERDUE')
                                        <span class="text-danger">
                                            <i class="bi bi-x-circle-fill me-1"></i>Overdue
                                        </span>
                                    @elseif($inv->payment_status === 'PARTIAL')
                                        <span class="text-info">
                                            <i class="bi bi-hourglass-split me-1"></i>Sebagian
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                    <p class="text-muted small mb-0">Belum ada invoice untuk partner ini.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
