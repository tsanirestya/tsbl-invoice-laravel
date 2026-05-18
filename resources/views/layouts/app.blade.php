<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Permissions-Policy" content="geolocation=(self)">
    <title>@yield('title', 'TSBL Invoice') — TSBL</title>
    @php $favicon = \App\Models\Setting::get('favicon_path'); @endphp
    @if($favicon)
        <link rel="icon" type="image/png" href="{{ asset($favicon) }}">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --topbar-height: 56px;
            --sidebar-bg: #0f1729;
            --sidebar-active-bg: rgba(59,130,246,.14);
            --sidebar-active-bar: #3b82f6;
            --sidebar-text: rgba(255,255,255,.6);
            --page-bg: #f0f4fb;
            --card-radius: 12px;
            --color-paid: #059669;
            --color-overdue: #dc2626;
            --color-partial: #d97706;
            --color-unpaid: #64748b;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html, body { overflow-x: hidden; max-width: 100%; }
        body {
            background: var(--page-bg);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: #1e293b;
        }

        /* ── Sidebar ─────────────────────────────────────────── */
        #sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: var(--sidebar-bg);
            position: fixed; top: 0; left: 0;
            z-index: 1030;
            transition: transform .26s cubic-bezier(.4,0,.2,1);
            display: flex; flex-direction: column; overflow: hidden;
        }
        #sidebar::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 180px;
            background: linear-gradient(180deg, rgba(59,130,246,.08) 0%, transparent 100%);
            pointer-events: none;
        }
        .sidebar-brand {
            height: var(--topbar-height);
            display: flex; align-items: center;
            padding: 0 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,.05);
            flex-shrink: 0; position: relative; z-index: 1;
        }
        .sidebar-brand-icon {
            width: 30px; height: 30px; border-radius: 8px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; margin-right: .6rem;
        }
        .sidebar-brand-title { color: #fff; font-weight: 800; font-size: .95rem; letter-spacing: -.1px; }
        .sidebar-brand-sub   { color: rgba(255,255,255,.35); font-size: .64rem; font-weight: 400; letter-spacing: .4px; text-transform: uppercase; }

        .sidebar-scroll {
            flex: 1; overflow-y: auto; padding: .5rem 0 1rem;
            position: relative; z-index: 1;
        }
        .sidebar-scroll::-webkit-scrollbar { width: 3px; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,.08); border-radius: 4px; }

        /* ── Sidebar accordion sections ──────────────────────── */
        .nav-section {
            display: flex; align-items: center; justify-content: space-between;
            color: rgba(255,255,255,.35); font-size: .6rem; font-weight: 700;
            letter-spacing: 1.5px; text-transform: uppercase;
            padding: .65rem 1.1rem .65rem 1.4rem;
            margin: 2px 0 0;
            cursor: pointer; user-select: none;
            border-radius: 6px;
            transition: background .15s, color .15s;
        }
        .nav-section:hover { background: rgba(255,255,255,.05); color: rgba(255,255,255,.6); }
        .nav-section.open { color: rgba(255,255,255,.6); }
        .nav-section .nav-section-arrow {
            font-size: .65rem; transition: transform .22s cubic-bezier(.4,0,.2,1);
            flex-shrink: 0;
        }
        .nav-section.open .nav-section-arrow { transform: rotate(180deg); }
        .nav-section-body {
            overflow: hidden;
            max-height: 0;
            transition: max-height .26s cubic-bezier(.4,0,.2,1);
        }
        .nav-section-body.open { max-height: 500px; }
        #sidebar .nav-link {
            color: var(--sidebar-text);
            padding: .5rem 1rem .5rem 1.2rem;
            border-radius: 9px; margin: 1px 8px;
            font-size: .82rem; font-weight: 500;
            transition: background .15s, color .15s;
            display: flex; align-items: center; position: relative;
        }
        #sidebar .nav-link i { width: 24px; text-align: center; margin-right: 8px; font-size: .88rem; flex-shrink: 0; }
        #sidebar .nav-link:hover { background: rgba(255,255,255,.07); color: #fff; }
        #sidebar .nav-link.active {
            background: var(--sidebar-active-bg); color: #60a5fa; font-weight: 600;
        }
        #sidebar .nav-link.active::before {
            content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%);
            width: 3px; height: 55%; background: var(--sidebar-active-bar); border-radius: 0 3px 3px 0;
        }
        .sidebar-footer {
            border-top: 1px solid rgba(255,255,255,.05);
            padding: .6rem 8px;
        }
        .sidebar-footer .nav-link { color: rgba(255, 100, 100, .7); }
        .sidebar-footer .nav-link:hover { background: rgba(220,38,38,.1); color: #f87171; }

        /* ── Main ────────────────────────────────────────────── */
        #main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex; flex-direction: column;
            overflow-x: hidden;
        }

        /* ── Topbar ──────────────────────────────────────────── */
        .topbar {
            height: var(--topbar-height);
            background: #fff;
            border-bottom: 1px solid #e8edf5;
            display: flex; align-items: center;
            padding: 0 1.25rem;
            position: sticky; top: 0; z-index: 1020;
            box-shadow: 0 1px 3px rgba(0,0,0,.04);
        }
        .topbar-title { font-size: .88rem; font-weight: 700; color: #0f1729; letter-spacing: -.1px; }
        .topbar-user-pill {
            display: flex; align-items: center; gap: .45rem;
            background: #f1f5fc; border-radius: 50px;
            padding: .25rem .65rem .25rem .3rem;
            border: 1px solid #e2e8f0;
        }
        .topbar-avatar {
            width: 28px; height: 28px; border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            display: flex; align-items: center; justify-content: center;
            font-size: .68rem; font-weight: 700; color: #fff; flex-shrink: 0;
        }
        .topbar-uname { font-size: .78rem; font-weight: 600; color: #334155; line-height: 1.1; }
        .topbar-urole { font-size: .65rem; color: #94a3b8; }
        .topbar-clock { font-size: .72rem; color: #94a3b8; font-weight: 500; }

        /* ── Content ─────────────────────────────────────────── */
        .content-area { padding: 1.25rem 1.5rem; flex: 1; }

        /* ── Stat cards ──────────────────────────────────────── */
        .stat-card {
            border: none; border-radius: var(--card-radius);
            box-shadow: 0 1px 3px rgba(15,23,41,.07), 0 3px 12px rgba(15,23,41,.04);
            transition: transform .18s ease, box-shadow .18s ease; overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(15,23,41,.12); }
        .stat-card .stat-icon {
            width: 42px; height: 42px; border-radius: 11px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem; flex-shrink: 0;
        }

        /* Gradient variants */
        .gc-blue   { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
        .gc-slate  { background: linear-gradient(135deg, #64748b 0%, #475569 100%); }
        .gc-amber  { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .gc-green  { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .gc-red    { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .gc-cyan   { background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); }
        .gc-purple { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }

        .stat-card .stat-label { font-size: .67rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: rgba(255,255,255,.78); margin-bottom: 3px; }
        .stat-card .stat-value { font-size: 1.45rem; font-weight: 800; color: #fff; line-height: 1; }
        .stat-card .stat-icon-wrap { background: rgba(255,255,255,.18); color: #fff; }
        .stat-card .stat-bg-icon { position: absolute; right: -6px; bottom: -8px; font-size: 4rem; opacity: .09; color: #fff; pointer-events: none; }

        /* ── Revenue cards ───────────────────────────────────── */
        .rev-card {
            border: none; border-radius: var(--card-radius);
            box-shadow: 0 1px 3px rgba(15,23,41,.06);
            transition: transform .18s; overflow: hidden;
        }
        .rev-card:hover { transform: translateY(-2px); }

        /* ── Table card ──────────────────────────────────────── */
        .table-card { border: none; border-radius: var(--card-radius); box-shadow: 0 1px 3px rgba(15,23,41,.07); overflow: hidden; }
        .table-card .card-header { background: #fff; border-bottom: 1px solid #f1f5f9; padding: .85rem 1.2rem; }
        .table-card thead th { background: #f8fafc; font-size: .67rem; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #f1f5f9; padding: .65rem 1rem; }
        .table-card tbody td { padding: .65rem 1rem; font-size: .83rem; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
        .table-card tbody tr:last-child td { border-bottom: none; }
        .table-card tbody tr:hover { background: #fafbff; }

        /* ── Badges ──────────────────────────────────────────── */
        .badge-paid    { background: #dcfce7 !important; color: #166534 !important; }
        .badge-overdue { background: #fee2e2 !important; color: #991b1b !important; }
        .badge-partial { background: #fef3c7 !important; color: #92400e !important; }
        .badge-unpaid  { background: #f1f5f9 !important; color: #475569 !important; }
        .badge { font-size: .7rem; font-weight: 600; padding: .26em .6em; border-radius: 6px; }

        /* ── Section label ───────────────────────────────────── */
        .sect-label { font-size: .7rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #94a3b8; margin-bottom: .5rem; }

        /* ── Alerts ──────────────────────────────────────────── */
        .alert-modern { border: none; border-radius: var(--card-radius); padding: .9rem 1.1rem; }

        /* ── Buttons ─────────────────────────────────────────── */
        .btn { border-radius: 8px; font-size: .82rem; font-weight: 600; }

        /* ── Page header utility ─────────────────────────────── */
        .page-hdr { margin-bottom: 1.25rem; }
        .page-hdr h5, .page-hdr .page-title { font-size: 1rem; font-weight: 700; margin: 0; letter-spacing: -.1px; }
        .page-hdr .page-sub { font-size: .78rem; color: #94a3b8; margin: 1px 0 0; }

        /* ── Card base ───────────────────────────────────────── */
        .card-clean {
            border: none;
            border-radius: var(--card-radius);
            box-shadow: 0 1px 3px rgba(15,23,41,.07);
        }
        .card-clean .card-header {
            background: #fff;
            border-bottom: 1px solid #f1f5f9;
            padding: .8rem 1.2rem;
            font-weight: 600;
            font-size: .88rem;
        }

        /* ── Mobile ──────────────────────────────────────────── */
        @media (max-width: 767.98px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.show { transform: translateX(0); }
            #main-content { margin-left: 0; }
            .content-area { padding: .9rem .9rem; }
            #bottom-nav {
                display: flex !important; position: fixed;
                bottom: 0; left: 0; right: 0;
                background: #fff; border-top: 1px solid #e8edf5;
                z-index: 1025; height: 58px;
                box-shadow: 0 -3px 10px rgba(0,0,0,.06);
            }
            #main-content { padding-bottom: 68px; }
        }
        @media (min-width: 768px) {
            #bottom-nav { display: none !important; }
            .topbar .btn-sidebar-toggle { display: none; }
        }

        /* ── Mobile list items (replaces tables) ─────────────── */
        .mobile-list-item {
            border-bottom: 1px solid #f1f5f9;
            padding: .8rem 1rem;
        }
        .mobile-list-item:last-child { border-bottom: none; }

        /* ── Deposit progress ─────────────────────────────────── */
        .deposit-progress { height: 5px; border-radius: 10px; background: #e8edf5; overflow: hidden; }
        .deposit-progress .bar { height: 100%; border-radius: 10px; transition: width .4s ease; }
    </style>
    @stack('styles')
</head>
<body>

<nav id="sidebar">
    <div class="sidebar-brand">
        @php $navbarLogo = \App\Models\Setting::get('navbar_logo_path'); @endphp
        @if($navbarLogo)
            <img src="{{ asset($navbarLogo) }}" alt="Logo" style="max-height:32px;max-width:140px;object-fit:contain;">
        @else
            <div class="sidebar-brand-icon"><i class="bi bi-receipt-cutoff text-white" style="font-size:.82rem"></i></div>
            <div>
                <div class="sidebar-brand-title">TSBL Invoice</div>
                <div class="sidebar-brand-sub">Management System</div>
            </div>
        @endif
    </div>

    <div class="sidebar-scroll">
        @php
            $user = auth()->user();
            $activeSection = '';
            if (request()->routeIs('admission.*')) $activeSection = 'admission';
            elseif (request()->routeIs('dashboard') || request()->routeIs('invoices.*') || request()->routeIs('pending-invoices.*') || request()->routeIs('deposit-invoices.*') || request()->routeIs('partners.*') || request()->routeIs('imports.*')) $activeSection = 'main';
            elseif (request()->routeIs('reservations.*') || request()->routeIs('anomalies.*') || request()->routeIs('commission-review.*') || request()->routeIs('self-service.*') || request()->routeIs('booking-pass-templates.*')) $activeSection = 'reservasi';
            elseif (request()->routeIs('payments.*') || request()->routeIs('payment-memos.*') || request()->routeIs('credit-payments.*') || request()->routeIs('reports.*')) $activeSection = 'keuangan';
            elseif (request()->routeIs('products.*') || request()->routeIs('users.*') || request()->routeIs('admin.password-requests.*') || request()->routeIs('credit-classes.*') || request()->routeIs('settings.*') || request()->routeIs('admin.audit-logs.*')) $activeSection = 'pengaturan';
        @endphp

        {{-- ── ADMISSION: hanya admission menu ──────────────── --}}
        @if($user->isAdmission() || $user->isAdmin())
        <div class="nav-section {{ $activeSection === 'admission' ? 'open' : '' }}"
             onclick="toggleSection('admission')" id="hdr-admission">
            <span>Admission</span>
            <i class="bi bi-chevron-down nav-section-arrow"></i>
        </div>
        <div class="nav-section-body {{ $activeSection === 'admission' ? 'open' : '' }}" id="sec-admission">
            <a href="{{ route('admission.dashboard') }}" class="nav-link {{ request()->routeIs('admission.dashboard') ? 'active' : '' }}">
                <i class="bi bi-door-open-fill"></i> Dashboard
            </a>
            <a href="{{ route('admission.scan') }}" class="nav-link {{ request()->routeIs('admission.scan') ? 'active' : '' }}">
                <i class="bi bi-upc-scan"></i> Scan & Redeem
            </a>
            <a href="{{ route('admission.history') }}" class="nav-link {{ request()->routeIs('admission.history') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> Riwayat Hari Ini
            </a>
            <a href="{{ route('self-service.qr-admin') }}" class="nav-link {{ request()->routeIs('self-service.*') ? 'active' : '' }}">
                <i class="bi bi-qr-code"></i> Kelola QR
            </a>
        </div>
        @endif

        {{-- ── IT: system management only ───────────────────── --}}
        @if($user->isIT())
        <div class="nav-section {{ $activeSection === 'pengaturan' ? 'open' : '' }}"
             onclick="toggleSection('pengaturan')" id="hdr-pengaturan">
            <span>Sistem</span>
            <i class="bi bi-chevron-down nav-section-arrow"></i>
        </div>
        <div class="nav-section-body {{ $activeSection === 'pengaturan' ? 'open' : '' }}" id="sec-pengaturan">
            <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="bi bi-person-gear"></i> Pengguna
            </a>
            @php $pendingResets = \App\Models\User::whereNotNull('reset_requested_at')->count(); @endphp
            <a href="{{ route('admin.password-requests.index') }}" class="nav-link {{ request()->routeIs('admin.password-requests.*') ? 'active' : '' }}">
                <i class="bi bi-key"></i> Reset Password
                @if($pendingResets > 0)
                    <span class="badge bg-warning text-dark ms-1">{{ $pendingResets }}</span>
                @endif
            </a>
            <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <i class="bi bi-gear-fill"></i> Konfigurasi
            </a>
            <a href="{{ route('admin.audit-logs.index') }}" class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i> Audit Trail
            </a>
            <a href="{{ route('imports.index') }}" class="nav-link {{ request()->routeIs('imports.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-spreadsheet-fill"></i> Rekonsiliasi DSI
            </a>
            <a href="{{ route('booking-pass-templates.index') }}" class="nav-link {{ request()->routeIs('booking-pass-templates.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-image"></i> Template Booking Pass
            </a>
        </div>
        @endif

        {{-- ── Non-admission, Non-IT sections ───────────────── --}}
        @if(!$user->isAdmission() && !$user->isIT())

        {{-- ── Operasional (Finance, BPM, ResStaff, BusdevHO, Admin) ── --}}
        <div class="nav-section {{ $activeSection === 'main' ? 'open' : '' }}"
             onclick="toggleSection('main')" id="hdr-main">
            <span>Operasional</span>
            <i class="bi bi-chevron-down nav-section-arrow"></i>
        </div>
        <div class="nav-section-body {{ $activeSection === 'main' ? 'open' : '' }}" id="sec-main">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
            @if($user->canAccessFinance() || $user->isAdmin())
            <a href="{{ route('invoices.index') }}" class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text-fill"></i> Invoice
            </a>
            @php
                $pendingCount = \App\Models\TransactionImportRow::whereIn('status', ['valid','anomaly'])
                    ->where('is_approved', true)
                    ->whereDoesntHave('invoice')
                    ->count();
            @endphp
            <a href="{{ route('pending-invoices.index') }}" class="nav-link {{ request()->routeIs('pending-invoices.*') ? 'active' : '' }}">
                <i class="bi bi-hourglass-split"></i> Antrian Invoice
                @if($pendingCount > 0)
                    <span class="badge ms-auto" style="background:#ef4444;font-size:.56rem;padding:.2em .48em;border-radius:5px;font-weight:700;">{{ $pendingCount }}</span>
                @endif
            </a>
            <a href="{{ route('deposit-invoices.index') }}" class="nav-link {{ request()->routeIs('deposit-invoices.*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i> Invoice Deposit
            </a>
            <a href="{{ route('imports.index') }}" class="nav-link {{ request()->routeIs('imports.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-spreadsheet-fill"></i> Rekonsiliasi DSI
            </a>
            @endif
            {{-- Partner: Finance (view), BPM (edit), BusdevHO (view), ResStaff (view), Admin --}}
            @if(!$user->isReservationStaff() || $user->isAdmin())
            <a href="{{ route('partners.index') }}" class="nav-link {{ request()->routeIs('partners.*') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i> Partner
            </a>
            @elseif($user->isReservationStaff())
            <a href="{{ route('partners.index') }}" class="nav-link {{ request()->routeIs('partners.*') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i> Partner
            </a>
            @endif
        </div>

        {{-- ── Reservasi (BPM, ResStaff, Finance, Admin) ──── --}}
        @if($user->isAdmin() || $user->isBPM() || $user->isReservationStaff() || $user->canAccessFinance())
        <div class="nav-section {{ $activeSection === 'reservasi' ? 'open' : '' }}"
             onclick="toggleSection('reservasi')" id="hdr-reservasi">
            <span>Reservasi</span>
            <i class="bi bi-chevron-down nav-section-arrow"></i>
        </div>
        <div class="nav-section-body {{ $activeSection === 'reservasi' ? 'open' : '' }}" id="sec-reservasi">
            @if($user->isAdmin() || $user->isBPM() || $user->isReservationStaff())
            <a href="{{ route('reservations.index') }}" class="nav-link {{ request()->routeIs('reservations.*') ? 'active' : '' }}">
                <i class="bi bi-calendar-check-fill"></i> Daftar Reservasi
            </a>
            @endif
            @if($user->isAdmin() || $user->canAccessFinance() || $user->isBusdevHO() || $user->isBPM())
            <a href="{{ route('anomalies.index') }}" class="nav-link {{ request()->routeIs('anomalies.*') || request()->routeIs('commission-review.*') ? 'active' : '' }}">
                <i class="bi bi-shield-exclamation"></i> Anomali & Fraud
                @php $pendingAnomalies = \App\Models\ReservationAnomaly::where('is_resolved', false)->count(); @endphp
                @if($pendingAnomalies > 0)
                    <span class="badge ms-auto" style="background:#ef4444;font-size:.56rem;padding:.2em .48em;border-radius:5px;font-weight:700;">{{ $pendingAnomalies }}</span>
                @endif
            </a>
            @endif
            @if($user->isAdmin() || $user->isIT())
            <a href="{{ route('booking-pass-templates.index') }}" class="nav-link {{ request()->routeIs('booking-pass-templates.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-image"></i> Template Booking Pass
            </a>
            @endif
        </div>
        @endif

        {{-- ── Keuangan (Finance, Admin, BusdevHO view) ──── --}}
        @if($user->isAdmin() || $user->canAccessFinance() || $user->isBusdevHO())
        <div class="nav-section {{ $activeSection === 'keuangan' ? 'open' : '' }}"
             onclick="toggleSection('keuangan')" id="hdr-keuangan">
            <span>Keuangan</span>
            <i class="bi bi-chevron-down nav-section-arrow"></i>
        </div>
        <div class="nav-section-body {{ $activeSection === 'keuangan' ? 'open' : '' }}" id="sec-keuangan">
            @if($user->isAdmin() || $user->canAccessFinance())
            <a href="{{ route('payments.index') }}" class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}">
                <i class="bi bi-cash-stack"></i> Pembayaran
            </a>
            <a href="{{ route('payment-memos.index') }}" class="nav-link {{ request()->routeIs('payment-memos.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-medical-fill"></i> Memo Tagihan
            </a>
            <a href="{{ route('credit-payments.index') }}" class="nav-link {{ request()->routeIs('credit-payments.*') ? 'active' : '' }}">
                <i class="bi bi-credit-card-2-front-fill"></i> Pembayaran Credit
            </a>
            @endif
            <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line-fill"></i> Laporan
            </a>
        </div>
        @endif

        {{-- ── Pengaturan (Admin, Finance for products) ──── --}}
        @if($user->isAdmin() || $user->canAccessFinance())
        <div class="nav-section {{ $activeSection === 'pengaturan' ? 'open' : '' }}"
             onclick="toggleSection('pengaturan')" id="hdr-pengaturan">
            <span>Pengaturan</span>
            <i class="bi bi-chevron-down nav-section-arrow"></i>
        </div>
        <div class="nav-section-body {{ $activeSection === 'pengaturan' ? 'open' : '' }}" id="sec-pengaturan">
            <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam-fill"></i> Produk
            </a>
            @if($user->isAdmin())
            <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="bi bi-person-gear"></i> Pengguna
            </a>
            @php $pendingResets = \App\Models\User::whereNotNull('reset_requested_at')->count(); @endphp
            <a href="{{ route('admin.password-requests.index') }}" class="nav-link {{ request()->routeIs('admin.password-requests.*') ? 'active' : '' }}">
                <i class="bi bi-key"></i> Reset Password
                @if($pendingResets > 0)
                    <span class="badge bg-warning text-dark ms-1">{{ $pendingResets }}</span>
                @endif
            </a>
            <a href="{{ route('credit-classes.index') }}" class="nav-link {{ request()->routeIs('credit-classes.*') ? 'active' : '' }}">
                <i class="bi bi-award-fill"></i> Credit Classes
            </a>
            <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <i class="bi bi-gear-fill"></i> Konfigurasi
            </a>
            <a href="{{ route('admin.audit-logs.index') }}" class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i> Audit Trail
            </a>
            @endif
        </div>
        @endif

        @endif {{-- end non-admission, non-IT --}}
    </div>

    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-link btn btn-link text-start w-100" style="border:none;background:none;">
                <i class="bi bi-box-arrow-left"></i> Keluar
            </button>
        </form>
    </div>
</nav>

<div id="sidebar-backdrop" class="d-none position-fixed top-0 start-0 w-100 h-100"
     style="background:rgba(0,0,0,.4);z-index:1025;" onclick="toggleSidebar()"></div>

<div id="main-content">
    <div class="topbar">
        <button class="btn btn-sm btn-sidebar-toggle me-2" onclick="toggleSidebar()"
                style="border:none;background:none;color:#64748b;padding:.2rem .4rem;">
            <i class="bi bi-list fs-5"></i>
        </button>
        <span class="topbar-title">@yield('page-title', 'Dashboard')</span>
        <div class="ms-auto d-flex align-items-center gap-3">
            <span class="topbar-clock d-none d-lg-block" id="topbar-clock"></span>
            <div class="topbar-user-pill">
                <div class="topbar-avatar">{{ strtoupper(substr(auth()->user()->full_name, 0, 1)) }}</div>
                <div class="d-none d-sm-block">
                    <div class="topbar-uname">{{ auth()->user()->full_name }}</div>
                    <div class="topbar-urole">{{ auth()->user()->user_status }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-area">
        @if(session('success'))
            <div class="alert alert-dismissible fade show alert-modern mb-3 d-flex align-items-center gap-2" role="alert"
                 style="background:#f0fdf4;border-left:4px solid #22c55e;color:#166534;">
                <i class="bi bi-check-circle-fill flex-shrink-0" style="color:#22c55e;font-size:1rem"></i>
                <span>{{ session('success') }}</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-dismissible fade show alert-modern mb-3 d-flex align-items-center gap-2" role="alert"
                 style="background:#fef2f2;border-left:4px solid #ef4444;color:#991b1b;">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0" style="color:#ef4444;font-size:1rem"></i>
                <span>{{ session('error') }}</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('warning'))
            <div class="alert alert-dismissible fade show alert-modern mb-3 d-flex align-items-center gap-2" role="alert"
                 style="background:#fffbeb;border-left:4px solid #f59e0b;color:#92400e;">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0" style="color:#f59e0b;font-size:1rem"></i>
                <span>{{ session('warning') }}</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>
</div>

{{-- Bottom Nav (mobile only) --}}
<div id="bottom-nav" style="display:none">
    @if(auth()->user()->isAdmission())
        {{-- Admission bottom nav --}}
        <a href="{{ route('admission.dashboard') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none {{ request()->routeIs('admission.dashboard') ? 'text-primary' : 'text-secondary' }}">
            <i class="bi bi-door-open-fill" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Dashboard</span>
        </a>
        <a href="{{ route('admission.scan') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none {{ request()->routeIs('admission.scan') ? 'text-primary' : 'text-secondary' }}">
            <i class="bi bi-upc-scan" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Scan</span>
        </a>
        <a href="{{ route('admission.qr') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none text-white"
           style="background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:50%;width:46px;height:46px;margin-top:-12px;box-shadow:0 4px 14px rgba(59,130,246,.4);flex-shrink:0;">
            <i class="bi bi-qr-code" style="font-size:1.2rem"></i>
        </a>
        <a href="{{ route('admission.history') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none {{ request()->routeIs('admission.history') ? 'text-primary' : 'text-secondary' }}">
            <i class="bi bi-clock-history" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Riwayat</span>
        </a>
        <a href="#" onclick="toggleSidebar()" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none text-secondary">
            <i class="bi bi-list" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Menu</span>
        </a>
    @else
        {{-- Default bottom nav for admin/finance/sales --}}
        <a href="{{ route('dashboard') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none {{ request()->routeIs('dashboard') ? 'text-primary' : 'text-secondary' }}">
            <i class="bi bi-grid-1x2-fill" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Dashboard</span>
        </a>
        <a href="{{ route('invoices.index') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none {{ request()->routeIs('invoices.*') ? 'text-primary' : 'text-secondary' }}">
            <i class="bi bi-file-earmark-text-fill" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Invoice</span>
        </a>
        <a href="{{ route('invoices.create') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none text-white"
           style="background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:50%;width:46px;height:46px;margin-top:-12px;box-shadow:0 4px 14px rgba(59,130,246,.4);flex-shrink:0;">
            <i class="bi bi-plus-lg" style="font-size:1.2rem"></i>
        </a>
        <a href="{{ route('partners.index') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none {{ request()->routeIs('partners.*') ? 'text-primary' : 'text-secondary' }}">
            <i class="bi bi-people-fill" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Partner</span>
        </a>
        <a href="#" onclick="toggleSidebar()" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none text-secondary">
            <i class="bi bi-list" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Menu</span>
        </a>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
// ── Currency Input Formatter — Global Utility ────────────────────────────
function parseRaw(str) {
    return parseInt(String(str || '').replace(/\D/g, '')) || 0;
}
function fmtCurrency(n) {
    if (!n || n <= 0) return '';
    return new Intl.NumberFormat('id-ID').format(Math.round(n));
}
function initCurrencyInputs(root) {
    (root || document).querySelectorAll('.currency-input').forEach(function(inp) {
        var raw = parseRaw(inp.value);
        if (raw > 0) inp.value = fmtCurrency(raw);
        inp.addEventListener('keydown', function(e) {
            if ([8,9,35,36,37,38,39,40,46].indexOf(e.keyCode) !== -1) return;
            if ((e.ctrlKey || e.metaKey) && [65,67,86,88].indexOf(e.keyCode) !== -1) return;
            if (!/^\d$/.test(e.key)) e.preventDefault();
        });
        inp.addEventListener('input', function() {
            var pos = this.selectionStart;
            var prevLen = this.value.length;
            var raw = parseRaw(this.value);
            this.value = raw > 0 ? fmtCurrency(raw) : '';
            var diff = this.value.length - prevLen;
            var newPos = Math.max(0, pos + diff);
            try { this.setSelectionRange(newPos, newPos); } catch(ex) {}
        });
        inp.addEventListener('paste', function(e) {
            e.preventDefault();
            var text = (e.clipboardData || window.clipboardData).getData('text');
            var digits = text.replace(/\D/g, '');
            if (digits) {
                this.value = fmtCurrency(parseInt(digits));
                this.dispatchEvent(new Event('input', {bubbles: true}));
            }
        });
    });
}
document.addEventListener('DOMContentLoaded', function() {
    initCurrencyInputs(document);
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            form.querySelectorAll('.currency-input').forEach(function(inp) {
                inp.value = String(parseRaw(inp.value) || 0);
            });
        });
    });
});
</script>
<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('sidebar-backdrop').classList.toggle('d-none');
}
function toggleSection(id) {
    var hdr = document.getElementById('hdr-' + id);
    var sec = document.getElementById('sec-' + id);
    if (!hdr || !sec) return;
    var isOpen = sec.classList.toggle('open');
    hdr.classList.toggle('open', isOpen);
    // persist
    try {
        var state = JSON.parse(localStorage.getItem('sidebarSections') || '{}');
        state[id] = isOpen;
        localStorage.setItem('sidebarSections', JSON.stringify(state));
    } catch(e) {}
}
(function tick() {
    var el = document.getElementById('topbar-clock');
    if (el) {
        var d = new Date();
        el.textContent = d.toLocaleDateString('id-ID',{weekday:'short',day:'numeric',month:'short'})
            + ' · ' + d.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'});
    }
    setTimeout(tick, 30000);
})();
</script>
@stack('modals')
@stack('scripts')
</body>
</html>
