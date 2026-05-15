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

                    <dt class="col-5 text-muted">Credit Class</dt>
                    <dd class="col-7">
                        @if($partner->creditClass)
                            <span class="badge bg-{{ $partner->creditClass->color }} text-dark">{{ $partner->creditClass->name }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </dd>

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

    {{-- Deposit Balance --}}
    <div class="col-lg-6">
        <div class="card
            @if($depositInfo['is_empty']) border-danger
            @elseif($depositInfo['is_low']) border-warning
            @else border-success @endif" style="border-width:2px!important">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-wallet2 me-1"></i> Saldo Deposit</span>
                @if($depositInfo['is_empty'])
                    <span class="badge bg-danger">Habis</span>
                @elseif($depositInfo['is_low'])
                    <span class="badge bg-warning text-dark">Rendah</span>
                @else
                    <span class="badge bg-success">OK</span>
                @endif
            </div>
            <div class="card-body">
                <div class="fs-4 fw-bold mb-2
                    @if($depositInfo['is_empty']) text-danger
                    @elseif($depositInfo['is_low']) text-warning
                    @else text-success @endif">
                    {{ $depositInfo['balance_formatted'] }}
                </div>
                @if($depositInfo['is_low'])
                    <div class="text-muted small mb-2">
                        Threshold minimum: Rp {{ number_format($depositInfo['threshold'], 0, ',', '.') }}
                    </div>
                @endif
                <div class="d-flex gap-2 mt-2">
                    <a href="{{ route('deposits.index', $partner) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-clock-history me-1"></i> Riwayat
                    </a>
                    <a href="{{ route('deposit-invoices.create', ['partner_id' => $partner->id]) }}" class="btn btn-sm btn-success">
                        <i class="bi bi-plus-circle me-1"></i> Top-up
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Credit Info --}}
    @if($creditInfo['limit'] > 0)
    <div class="col-12">
        <div class="card border-{{ match($creditInfo['status']) { 'OVER_LIMIT' => 'danger', 'WARNING' => 'warning', default => 'success' } }}" style="border-width:2px!important">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-shield-check me-1"></i> Kredit
                    @if($partner->creditClass)
                        &nbsp;<span class="badge bg-{{ $partner->creditClass->color }} text-dark">{{ $partner->creditClass->name }}</span>
                    @endif
                </span>
                @php
                    $statusBadge = match($creditInfo['status']) {
                        'OVER_LIMIT' => ['danger',  'Over Limit'],
                        'WARNING'    => ['warning text-dark', 'Warning'],
                        default      => ['success',  'Normal'],
                    };
                @endphp
                <span class="badge bg-{{ $statusBadge[0] }}">{{ $statusBadge[1] }}</span>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3 text-center">
                    <div class="col-4">
                        <div class="p-2 bg-light rounded">
                            <div class="small text-muted">Limit</div>
                            <div class="fw-bold">{{ $creditInfo['limit_formatted'] }}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-light rounded">
                            <div class="small text-muted">Terpakai</div>
                            <div class="fw-bold {{ $creditInfo['status'] === 'OVER_LIMIT' ? 'text-danger' : '' }}">
                                {{ $creditInfo['used_formatted'] }}
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-light rounded">
                            <div class="small text-muted">Tersedia</div>
                            <div class="fw-bold {{ $creditInfo['available'] < 0 ? 'text-danger' : 'text-success' }}">
                                {{ $creditInfo['available_formatted'] }}
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    $util       = $creditInfo['utilization_percent'];
                    $barColor   = $util > 100 ? 'danger' : ($util >= (float)\App\Models\Setting::get('credit_warning_threshold', 80) ? 'warning' : 'success');
                    $barWidth   = min($util, 100);
                @endphp
                <div class="mb-1 d-flex justify-content-between small">
                    <span class="text-muted">Utilisasi Kredit</span>
                    <span class="fw-semibold text-{{ $barColor }}">{{ number_format($util, 1) }}%</span>
                </div>
                <div class="progress" style="height:8px;">
                    <div class="progress-bar bg-{{ $barColor }}" style="width:{{ $barWidth }}%"></div>
                </div>

                {{-- Outstanding invoices --}}
                @php
                    $outstandingInvoices = $partner->invoices
                        ->whereIn('payment_status', ['UNPAID', 'PARTIAL', 'OVERDUE'])
                        ->sortBy('due_date');
                    $today = \Carbon\Carbon::today();
                @endphp
                @if($outstandingInvoices->count() > 0)
                <div class="mt-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="small text-uppercase text-muted fw-semibold">Invoice Outstanding ({{ $outstandingInvoices->count() }})</div>
                        <a href="{{ route('payment-memos.create', ['partner_id' => $partner->id]) }}" class="btn btn-sm btn-outline-warning" style="font-size:.72rem;padding:.25rem .7rem;">
                            <i class="bi bi-file-earmark-plus me-1"></i> Buat Memo Tagihan
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>No Invoice</th>
                                    <th>Tgl Invoice</th>
                                    <th class="text-end">Grand Total</th>
                                    <th>Jatuh Tempo</th>
                                    <th class="text-center">Hari</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($outstandingInvoices as $inv)
                                @php
                                    $dueDate   = $inv->due_date ? \Carbon\Carbon::parse($inv->due_date) : null;
                                    $daysLeft  = $dueDate ? $today->diffInDays($dueDate, false) : null;
                                    $statusColors = ['UNPAID'=>'secondary','PARTIAL'=>'info','OVERDUE'=>'danger'];
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('invoices.show', $inv) }}" class="text-decoration-none">
                                            {{ $inv->invoice_no }}
                                        </a>
                                    </td>
                                    <td>{{ $inv->invoice_date->format('d/m/Y') }}</td>
                                    <td class="text-end fw-semibold">Rp {{ number_format($inv->grand_total, 0, ',', '.') }}</td>
                                    <td>{{ $dueDate?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="text-center">
                                        @if($daysLeft !== null)
                                            @if($daysLeft > 0)
                                                <span class="text-success">+{{ $daysLeft }}h</span>
                                            @elseif($daysLeft === 0)
                                                <span class="text-warning fw-semibold">Hari ini</span>
                                            @else
                                                <span class="text-danger fw-semibold">{{ abs($daysLeft) }}h lewat</span>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $statusColors[$inv->payment_status] ?? 'secondary' }}">
                                            {{ $inv->payment_status }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

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
{{-- Phase 10: Reservation Panel --}}
<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center fw-semibold">
                <span><i class="bi bi-calendar-check me-2"></i>Reservasi Partner</span>
                <div class="d-flex gap-2">
                    @if(auth()->user()->isAdmin())
                        @if($partner->reservation_token)
                            <span class="badge bg-{{ $partner->isReservationTokenValid() ? 'success' : 'danger' }}">
                                {{ $partner->isReservationTokenValid() ? 'Token Aktif' : 'Token Expired/Suspended' }}
                            </span>
                            <form method="POST" action="{{ route('partners.reset-devices', $partner) }}">@csrf
                                <button type="submit" class="btn btn-xs btn-outline-secondary">Reset Devices</button>
                            </form>
                            <form method="POST" action="{{ route('partners.toggle-suspension', $partner) }}">@csrf
                                <button type="submit" class="btn btn-xs btn-outline-{{ $partner->reservation_suspended ? 'success' : 'danger' }}">
                                    {{ $partner->reservation_suspended ? 'Cabut Suspensi' : 'Suspend' }}
                                </button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('partners.generate-token', $partner) }}" onsubmit="return confirm('Generate/reset token reservasi untuk partner ini?')">@csrf
                            <button type="submit" class="btn btn-xs btn-primary">
                                <i class="bi bi-key me-1"></i> {{ $partner->reservation_token ? 'Reset Token' : 'Generate Token' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="card-body">
                {{-- Token info --}}
                @if($partner->reservation_token && auth()->user()->isAdmin())
                <div class="alert alert-info py-2 small mb-3">
                    <strong>Link Partner:</strong>
                    <a href="{{ route('partner.reserve.form', $partner->reservation_token) }}" target="_blank">
                        {{ route('partner.reserve.form', $partner->reservation_token) }}
                    </a>
                    @if($partner->reservation_token_expires_at)
                        <span class="ms-2 text-muted">Exp: {{ $partner->reservation_token_expires_at->format('d/m/Y') }}</span>
                    @endif
                </div>
                @endif

                {{-- Fraud score --}}
                <div class="row g-3 mb-3">
                    <div class="col-sm-3">
                        <div class="p-2 bg-light rounded text-center">
                            <div class="small text-muted">Fraud Score</div>
                            <div class="fw-bold fs-5 text-{{ $partner->fraudRiskBadge() }}">{{ $partner->fraud_score }}</div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="p-2 bg-light rounded text-center">
                            <div class="small text-muted">Risk Level</div>
                            <span class="badge bg-{{ $partner->fraudRiskBadge() }}">{{ $partner->fraudRiskLevel() }}</span>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="p-2 bg-light rounded text-center">
                            <div class="small text-muted">Total Reservasi</div>
                            <div class="fw-bold">{{ $partner->reservations()->count() }}</div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="p-2 bg-light rounded text-center">
                            <div class="small text-muted">Devices Terdaftar</div>
                            <div class="fw-bold">{{ count($partner->known_devices ?? []) }} / {{ $partner->max_devices }}</div>
                        </div>
                    </div>
                </div>

                @if($partner->reservation_suspended)
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-lock me-1"></i> <strong>SUSPENDED:</strong> {{ $partner->reservation_suspended_reason }}
                    </div>
                @endif

                {{-- Recent reservations --}}
                @php $recentRes = $partner->reservations()->with('items')->latest()->limit(5)->get(); @endphp
                @if($recentRes->isNotEmpty())
                    <div class="small text-muted fw-semibold mb-2">5 Reservasi Terakhir</div>
                    <table class="table table-sm mb-0">
                        <thead><tr><th>No. Reservasi</th><th>Tamu</th><th>Visit Date</th><th class="text-end">Total</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach($recentRes as $res)
                            <tr>
                                <td><a href="{{ route('reservations.show', $res) }}" class="text-decoration-none small fw-semibold">{{ $res->reservation_no }}</a></td>
                                <td class="small">{{ $res->guest_name }}</td>
                                <td class="small">{{ $res->visit_date->format('d/m/Y') }}</td>
                                <td class="text-end small">Rp {{ number_format($res->total_amount, 0, ',', '.') }}</td>
                                <td><span class="badge bg-{{ $res->statusBadge() }}">{{ $res->status }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-2">
                        <a href="{{ route('reservations.index', ['partner_id' => $partner->id]) }}" class="btn btn-sm btn-outline-secondary">
                            Lihat Semua Reservasi
                        </a>
                    </div>
                @else
                    <div class="text-muted small">Belum ada reservasi untuk partner ini.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
