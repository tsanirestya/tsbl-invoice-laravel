@extends('layouts.app')

@section('title', 'QR Self-Service Harian')
@section('page-title', 'QR Self-Service Harian')

@section('content')
<div class="page-hdr d-flex align-items-center justify-content-between mb-3">
    <div>
        <div class="page-title">QR Self-Service Harian</div>
        <div class="page-sub">Generate QR Code untuk check-in mandiri tamu</div>
    </div>
    <form method="POST" action="{{ route('self-service.generate-qr') }}">
        @csrf
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-qr-code me-1"></i> Generate QR Hari Ini
        </button>
    </form>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        @php
            // Show current day QR link
            $todayQr = \App\Models\DailyQrCode::where('date', now()->toDateString())->first();
        @endphp
        @if($todayQr)
            <br>
            <strong>Link QR:</strong>
            <a href="{{ route('self-service.scan', $todayQr->token) }}" target="_blank">
                {{ route('self-service.scan', $todayQr->token) }}
            </a>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('info'))
    <div class="alert alert-info alert-dismissible fade show">{{ session('info') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="card table-card">
    <div class="card-body p-0">
        @if($qrs->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-qr-code fs-1 d-block mb-2"></i>
                Belum ada QR yang digenerate. Klik "Generate QR Hari Ini".
            </div>
        @else
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Token</th>
                        <th class="text-center">Status</th>
                        <th>Dibuat oleh</th>
                        <th>Link</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($qrs as $qr)
                    <tr>
                        <td class="fw-semibold">{{ $qr->date->format('d/m/Y') }}</td>
                        <td><code class="small">{{ substr($qr->token, 0, 16) }}...</code></td>
                        <td class="text-center">
                            @if($qr->isValidToday())
                                <span class="badge bg-success">Aktif (Hari ini)</span>
                            @elseif($qr->is_active)
                                <span class="badge bg-warning text-dark">Aktif (Lama)</span>
                            @else
                                <span class="badge bg-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td>{{ $qr->generatedBy?->name ?? '—' }}</td>
                        <td>
                            @if($qr->is_active)
                                <a href="{{ route('self-service.scan', $qr->token) }}" target="_blank"
                                   class="btn btn-xs btn-outline-primary">
                                    <i class="bi bi-link-45deg"></i> Buka Link
                                </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $qrs->links() }}</div>
        @endif
    </div>
</div>
@endsection
