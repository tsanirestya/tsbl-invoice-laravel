@extends('layouts.app')
@section('title', 'Reservasi')
@section('page-title', 'Reservasi')

@push('styles')
<style>
    .res-stat { border:none; border-radius:11px; padding:.9rem 1rem; display:flex; align-items:center; gap:.85rem;
        box-shadow:0 1px 3px rgba(15,23,41,.07),0 3px 12px rgba(15,23,41,.04); transition:transform .16s; position:relative; overflow:hidden; }
    .res-stat:hover { transform:translateY(-2px); }
    .res-stat-icon { width:42px; height:42px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:1.15rem; flex-shrink:0; }
    .res-stat-label { font-size:.64rem; font-weight:700; text-transform:uppercase; letter-spacing:.6px; opacity:.75; margin-bottom:1px; }
    .res-stat-value { font-size:1.35rem; font-weight:800; line-height:1.1; }
    .res-stat.blue   { background:linear-gradient(135deg,#3b82f6,#2563eb); color:#fff; }
    .res-stat.amber  { background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; }
    .res-stat.green  { background:linear-gradient(135deg,#10b981,#059669); color:#fff; }
    .res-stat.red    { background:linear-gradient(135deg,#ef4444,#dc2626); color:#fff; }
    .res-stat.purple { background:linear-gradient(135deg,#8b5cf6,#7c3aed); color:#fff; }
    .res-stat .res-stat-icon { background:rgba(255,255,255,.2); color:#fff; }
    .res-stat .bg-icon { position:absolute; right:-8px; bottom:-10px; font-size:4.5rem; opacity:.07; pointer-events:none; }

    .filter-panel { background:#fff; border-radius:11px; padding:.9rem 1.1rem; box-shadow:0 1px 3px rgba(15,23,41,.06); margin-bottom:1rem; }
    .filter-label { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#94a3b8; margin-bottom:.3rem; display:block; }
    .filter-panel .form-control, .filter-panel .form-select { border-radius:8px; border-color:#e2e8f0; font-size:.82rem; padding:.38rem .7rem; }
    .filter-panel .form-control:focus, .filter-panel .form-select:focus { border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.1); }

    .res-wrap { background:#fff; border-radius:11px; box-shadow:0 1px 3px rgba(15,23,41,.06); overflow:hidden; }
    .res-table-hdr { padding:.8rem 1.1rem; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; }
    .res-wrap table thead th { background:#f8fafc; font-size:.65rem; font-weight:700; letter-spacing:.55px; text-transform:uppercase; color:#64748b; border-bottom:1px solid #f1f5f9; padding:.62rem 1rem; white-space:nowrap; }
    .res-wrap table tbody td { padding:.65rem 1rem; font-size:.83rem; border-bottom:1px solid #f8fafc; vertical-align:middle; }
    .res-wrap table tbody tr:last-child td { border-bottom:none; }
    .res-wrap table tbody tr:hover { background:#fafbff; }

    .res-no-link { font-weight:700; color:#1e40af; text-decoration:none; }
    .res-no-link:hover { color:#3b82f6; text-decoration:underline; }

    .status-badge { display:inline-flex; align-items:center; gap:.25rem; font-size:.7rem; font-weight:700; border-radius:20px; padding:.25em .7em; }
    .sb-pending   { background:#eff6ff; color:#1d4ed8; }
    .sb-confirmed { background:#f0fdf4; color:#166534; }
    .sb-cancelled { background:#fef2f2; color:#991b1b; }
    .sb-no_show   { background:#fff7ed; color:#c2410c; }
    .sb-completed { background:#f0fdf4; color:#15803d; }

    .act-btn { width:28px; height:28px; border-radius:7px; border:1px solid #e2e8f0; background:#fff; color:#64748b; display:inline-flex; align-items:center; justify-content:center; font-size:.78rem; text-decoration:none; transition:background .13s,color .13s,border-color .13s; }
    .act-btn.view:hover  { background:#eff6ff; color:#3b82f6; border-color:#bfdbfe; }

    .res-empty { padding:3rem 1rem; text-align:center; color:#94a3b8; }
    .res-empty i { font-size:3rem; opacity:.3; display:block; margin-bottom:.6rem; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-3 page-hdr">
    <div>
        <div class="page-title">Daftar Reservasi</div>
        <div class="page-sub">Kelola reservasi tamu & alur proforma invoice</div>
    </div>
    @if(auth()->user()->isAdmin() || auth()->user()->user_status === 'FINANCE')
    <a href="{{ route('reservations.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1" style="border-radius:9px;">
        <i class="bi bi-plus-lg"></i>
        <span class="d-none d-sm-inline">Reservasi Baru</span>
    </a>
    @endif
</div>

{{-- Stats --}}
@php
    use App\Models\Reservation;
    $counts = [
        'PENDING'   => Reservation::where('status','PENDING')->count(),
        'CONFIRMED' => Reservation::where('status','CONFIRMED')->count(),
        'COMPLETED' => Reservation::where('status','COMPLETED')->count(),
        'CANCELLED' => Reservation::where('status','CANCELLED')->count(),
        'NO_SHOW'   => Reservation::where('status','NO_SHOW')->count(),
    ];
@endphp
<div class="row g-2 mb-3">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="res-stat blue">
            <div class="res-stat-icon"><i class="bi bi-hourglass-split"></i></div>
            <div><div class="res-stat-label">Pending</div><div class="res-stat-value">{{ $counts['PENDING'] }}</div></div>
            <i class="bi bi-hourglass-split bg-icon"></i>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="res-stat green">
            <div class="res-stat-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div><div class="res-stat-label">Confirmed</div><div class="res-stat-value">{{ $counts['CONFIRMED'] }}</div></div>
            <i class="bi bi-check-circle-fill bg-icon"></i>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="res-stat purple">
            <div class="res-stat-icon"><i class="bi bi-award-fill"></i></div>
            <div><div class="res-stat-label">Completed</div><div class="res-stat-value">{{ $counts['COMPLETED'] }}</div></div>
            <i class="bi bi-award-fill bg-icon"></i>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="res-stat red">
            <div class="res-stat-icon"><i class="bi bi-x-circle-fill"></i></div>
            <div><div class="res-stat-label">Cancelled</div><div class="res-stat-value">{{ $counts['CANCELLED'] }}</div></div>
            <i class="bi bi-x-circle-fill bg-icon"></i>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="res-stat amber">
            <div class="res-stat-icon"><i class="bi bi-person-dash-fill"></i></div>
            <div><div class="res-stat-label">No Show</div><div class="res-stat-value">{{ $counts['NO_SHOW'] }}</div></div>
            <i class="bi bi-person-dash-fill bg-icon"></i>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="res-stat" style="background:linear-gradient(135deg,#64748b,#475569);color:#fff;">
            <div class="res-stat-icon"><i class="bi bi-collection-fill"></i></div>
            <div><div class="res-stat-label">Total</div><div class="res-stat-value">{{ $reservations->total() }}</div></div>
            <i class="bi bi-collection-fill bg-icon"></i>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="filter-panel">
    <form method="GET" action="{{ route('reservations.index') }}" id="res-filter-form">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-sm-6 col-lg-3">
                <label class="filter-label">Pencarian</label>
                <input type="text" name="search" class="form-control" placeholder="No reservasi / tamu / ref" value="{{ request('search') }}">
            </div>
            <div class="col-6 col-sm-3 col-lg-2">
                <label class="filter-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-3 col-lg-3">
                <label class="filter-label">Partner</label>
                <select name="partner_id" class="form-select">
                    <option value="">Semua</option>
                    @foreach($partners as $p)
                        <option value="{{ $p->id }}" @selected(request('partner_id') == $p->id)>{{ $p->nama_partner }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-3 col-lg-2">
                <label class="filter-label">Check-in Dari</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-6 col-sm-3 col-lg-2">
                <label class="filter-label">Check-in Sampai</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm" style="border-radius:8px;padding:.38rem .85rem;">
                    <i class="bi bi-search"></i>
                </button>
                @if(request()->hasAny(['search','status','partner_id','date_from','date_to']))
                <a href="{{ route('reservations.index') }}" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;padding:.38rem .85rem;" title="Reset">
                    <i class="bi bi-x-lg"></i>
                </a>
                @endif
            </div>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="res-wrap">
    <div class="res-table-hdr">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-calendar2-check" style="color:#8b5cf6"></i>
            <span class="fw-semibold" style="font-size:.86rem;">Reservasi</span>
            @if(request()->hasAny(['search','status','partner_id','date_from','date_to']))
                <span class="badge" style="background:#f5f3ff;color:#7c3aed;font-size:.65rem;">Filter Aktif</span>
            @endif
        </div>
        <span style="font-size:.73rem;color:#94a3b8;">{{ $reservations->total() }} data</span>
    </div>

    @if($reservations->isEmpty())
        <div class="res-empty">
            <i class="bi bi-calendar2-x"></i>
            <p class="fw-semibold mb-1" style="color:#64748b;">Tidak ada reservasi ditemukan</p>
            <p style="font-size:.85rem;">
                @if(request()->hasAny(['search','status','partner_id','date_from','date_to']))
                    Coba ubah filter pencarian.
                @else
                    Belum ada reservasi. <a href="{{ route('reservations.create') }}">Buat reservasi baru</a>.
                @endif
            </p>
        </div>
    @else
    {{-- Desktop --}}
    <div class="d-none d-md-block">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th class="ps-4">No Reservasi</th>
                    <th>Partner</th>
                    <th>Tamu</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th class="text-end">Proforma Amt</th>
                    <th class="text-center">Status</th>
                    <th class="text-center pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reservations as $res)
                @php
                    $sCls = 'sb-' . strtolower($res->status);
                    $sLabel = match($res->status) {
                        'PENDING'   => 'Pending',
                        'CONFIRMED' => 'Confirmed',
                        'CANCELLED' => 'Cancelled',
                        'NO_SHOW'   => 'No Show',
                        'COMPLETED' => 'Completed',
                        default     => $res->status,
                    };
                    $sIcon = match($res->status) {
                        'PENDING'   => 'hourglass-split',
                        'CONFIRMED' => 'check-circle-fill',
                        'CANCELLED' => 'x-circle-fill',
                        'NO_SHOW'   => 'person-dash-fill',
                        'COMPLETED' => 'award-fill',
                        default     => 'circle',
                    };
                @endphp
                <tr>
                    <td class="ps-4">
                        <a href="{{ route('reservations.show', $res) }}" class="res-no-link">{{ $res->reservation_no }}</a>
                        @if($res->booking_ref)
                            <div style="font-size:.7rem;color:#94a3b8;">ref: {{ $res->booking_ref }}</div>
                        @endif
                    </td>
                    <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#475569;">
                        {{ $res->partner?->name ?? '-' }}
                    </td>
                    <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#64748b;">
                        {{ $res->guest_name ?? '-' }}
                        @if($res->pax) <span style="font-size:.7rem;color:#94a3b8;">({{ $res->pax }} pax)</span>@endif
                    </td>
                    <td style="white-space:nowrap;color:#64748b;">{{ $res->check_in_date?->format('d/m/Y') ?? '-' }}</td>
                    <td style="white-space:nowrap;color:#94a3b8;">{{ $res->check_out_date?->format('d/m/Y') ?? '-' }}</td>
                    <td class="text-end" style="font-weight:700;color:#1e293b;white-space:nowrap;">
                        {{ $res->proforma_amount ? 'Rp '.number_format($res->proforma_amount,0,',','.') : '-' }}
                    </td>
                    <td class="text-center">
                        <span class="status-badge {{ $sCls }}">
                            <i class="bi bi-{{ $sIcon }}"></i> {{ $sLabel }}
                        </span>
                    </td>
                    <td class="text-center pe-3">
                        <a href="{{ route('reservations.show', $res) }}" class="act-btn view" title="Detail">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile --}}
    <div class="d-md-none">
        @foreach($reservations as $res)
        @php
            $sCls  = 'sb-'.strtolower($res->status);
            $sLabel = match($res->status){ 'PENDING'=>'Pending','CONFIRMED'=>'Confirmed','CANCELLED'=>'Cancelled','NO_SHOW'=>'No Show','COMPLETED'=>'Completed',default=>$res->status };
        @endphp
        <div class="mobile-list-item">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <a href="{{ route('reservations.show', $res) }}" class="res-no-link">{{ $res->reservation_no }}</a>
                <span class="status-badge {{ $sCls }}">{{ $sLabel }}</span>
            </div>
            <div style="font-size:.78rem;color:#475569;">{{ $res->partner?->name ?? '-' }}</div>
            <div class="d-flex justify-content-between align-items-center mt-1">
                <span style="font-size:.73rem;color:#94a3b8;">{{ $res->guest_name ?? '' }} · {{ $res->check_in_date?->format('d/m/Y') }}</span>
                <span style="font-weight:700;font-size:.82rem;">{{ $res->proforma_amount ? 'Rp '.number_format($res->proforma_amount,0,',','.') : '-' }}</span>
            </div>
        </div>
        @endforeach
    </div>

    @if($reservations->hasPages())
    <div class="px-4 py-3" style="border-top:1px solid #f1f5f9;">
        {{ $reservations->links() }}
    </div>
    @endif
    @endif
</div>

@endsection

@push('scripts')
<script>
document.querySelectorAll('#res-filter-form select').forEach(function(sel) {
    sel.addEventListener('change', function() { document.getElementById('res-filter-form').submit(); });
});
</script>
@endpush
