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
        /* ── Design Tokens ─────────────────────────────────── */
        :root {
            --sidebar-w:     220px;
            --topbar-h:      52px;
            --accent:        #2563eb;
            --accent-light:  #eff6ff;
            --accent-dim:    #dbeafe;
            --success:       #059669;
            --success-light: #f0fdf4;
            --warning:       #d97706;
            --warning-light: #fffbeb;
            --danger:        #dc2626;
            --danger-light:  #fef2f2;
            --purple:        #7c3aed;
            --purple-light:  #f5f3ff;
            --text:          #0f172a;
            --text-2:        #334155;
            --text-3:        #64748b;
            --text-4:        #94a3b8;
            --card-bg:       #ffffff;
            --page-bg:       #f8fafc;
            --border:        #f1f5f9;
            --border-2:      #e2e8f0;
            --radius:        8px;
            --radius-sm:     5px;
            --radius-lg:     12px;
            --shadow:        0 1px 3px rgba(0,0,0,.04), 0 1px 2px rgba(0,0,0,.02);
            --shadow-md:     0 4px 12px rgba(0,0,0,.06);
        }

        *, *::before, *::after { box-sizing: border-box; }
        html, body { overflow-x: hidden; max-width: 100%; }
        body {
            background: var(--page-bg);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: var(--text-2);
        }

        /* ── Sidebar ─────────────────────────────────────────── */
        #sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: var(--card-bg);
            border-right: 1px solid var(--border);
            position: fixed; top: 0; left: 0;
            z-index: 1030;
            transition: transform .26s cubic-bezier(.4,0,.2,1);
            display: flex; flex-direction: column; overflow: hidden;
        }
        .sidebar-brand {
            height: var(--topbar-h);
            display: flex; align-items: center;
            padding: 0 1.1rem;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }
        .sidebar-brand-icon {
            width: 24px; height: 24px; border-radius: 6px;
            background: var(--accent);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; margin-right: .6rem;
            font-size: .62rem; color: #fff;
        }
        .sidebar-brand-title { color: var(--text); font-weight: 800; font-size: .85rem; letter-spacing: -.1px; }
        .sidebar-brand-sub   { color: var(--text-4); font-size: .57rem; text-transform: uppercase; letter-spacing: .6px; }

        .sidebar-scroll {
            flex: 1; overflow-y: auto; padding: .5rem 0 1rem;
        }
        .sidebar-scroll::-webkit-scrollbar { width: 3px; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 4px; }

        /* ── Sidebar sections (accordion kept, styled flat) ── */
        .nav-section {
            display: flex; align-items: center; justify-content: space-between;
            font-size: .55rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 1.1px; color: var(--text-4);
            padding: .7rem 1.1rem .2rem;
            margin: 4px 0 0;
            cursor: pointer; user-select: none;
            transition: color .12s;
        }
        .nav-section:hover { color: var(--text-3); }
        .nav-section .nav-section-arrow {
            font-size: .6rem; transition: transform .22s cubic-bezier(.4,0,.2,1);
            flex-shrink: 0;
        }
        .nav-section.open .nav-section-arrow { transform: rotate(180deg); }
        .nav-section-body {
            overflow: hidden;
            max-height: 0;
            transition: max-height .26s cubic-bezier(.4,0,.2,1);
        }
        .nav-section-body.open { max-height: 600px; }

        #sidebar .nav-link {
            color: var(--text-3);
            padding: .38rem .9rem;
            margin: 1px .5rem;
            border-radius: var(--radius-sm);
            font-size: .78rem; font-weight: 500;
            transition: background .12s, color .12s;
            display: flex; align-items: center;
            text-decoration: none;
            position: relative;
        }
        #sidebar .nav-link i { width: 16px; text-align: center; margin-right: .6rem; font-size: .8rem; flex-shrink: 0; }
        #sidebar .nav-link:hover { background: #f8fafc; color: var(--text); }
        #sidebar .nav-link.active { background: var(--accent-light); color: var(--accent); font-weight: 700; }

        .sb-badge {
            margin-left: auto;
            background: var(--danger); color: #fff;
            font-size: .57rem; font-weight: 700; border-radius: 10px;
            padding: .1rem .38rem; line-height: 1.4; flex-shrink: 0;
        }
        .sb-badge.warn { background: var(--warning); color: #fff; }

        .sidebar-footer {
            border-top: 1px solid var(--border);
            padding: .6rem .5rem;
        }
        .sidebar-footer .nav-link { color: #ef4444 !important; }
        .sidebar-footer .nav-link:hover { background: #fff5f5 !important; color: #dc2626 !important; }

        /* ── Main ────────────────────────────────────────────── */
        #main-content {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex; flex-direction: column;
            overflow-x: hidden;
        }

        /* ── Topbar ──────────────────────────────────────────── */
        .topbar {
            height: var(--topbar-h);
            background: var(--card-bg);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center;
            padding: 0 1.5rem;
            position: sticky; top: 0; z-index: 1020;
            gap: 1rem;
        }
        .topbar-title { font-size: .88rem; font-weight: 700; color: var(--text); letter-spacing: -.1px; }
        .topbar-user-pill {
            display: flex; align-items: center; gap: .45rem;
            background: #f8fafc; border: 1px solid var(--border-2);
            border-radius: 50px;
            padding: .22rem .65rem .22rem .22rem;
        }
        .topbar-avatar {
            width: 26px; height: 26px; border-radius: 50%;
            background: var(--accent);
            display: flex; align-items: center; justify-content: center;
            font-size: .65rem; font-weight: 800; color: #fff; flex-shrink: 0;
        }
        .topbar-uname { font-size: .75rem; font-weight: 600; color: var(--text-2); line-height: 1.1; }
        .topbar-urole { font-size: .62rem; color: var(--text-4); }
        .topbar-clock { font-size: .72rem; color: var(--text-4); font-weight: 500; }

        /* ── Content ─────────────────────────────────────────── */
        .content-area { padding: 1.5rem; flex: 1; }

        /* ── Bootstrap overrides ──────────────────────────────── */
        .card {
            background: var(--card-bg) !important;
            border: 1px solid var(--border) !important;
            border-radius: var(--radius) !important;
            box-shadow: var(--shadow) !important;
        }
        .card-header {
            background: var(--card-bg) !important;
            border-bottom: 1px solid var(--border) !important;
            padding: .7rem 1rem !important;
            font-weight: 600 !important;
            font-size: .85rem !important;
        }
        .card-body { padding: 1rem !important; }

        .btn { border-radius: var(--radius) !important; font-size: .78rem !important; font-weight: 600 !important; }
        .btn-sm { font-size: .72rem !important; }

        .btn-primary { background: var(--accent) !important; border-color: var(--accent) !important; }
        .btn-primary:hover, .btn-primary:focus { background: #1d4ed8 !important; border-color: #1d4ed8 !important; }
        .btn-primary:focus { box-shadow: 0 0 0 .2rem rgba(37,99,235,.25) !important; }

        .btn-outline-secondary { border-color: var(--border-2) !important; color: var(--text-3) !important; }
        .btn-outline-secondary:hover { border-color: #cbd5e1 !important; color: var(--text) !important; background: #f8fafc !important; }

        .badge {
            font-size: .63rem !important; font-weight: 700 !important;
            border-radius: var(--radius-sm) !important;
            padding: .18rem .45rem !important;
        }
        .badge-paid    { background: var(--success-light) !important; color: #166534 !important; border: 1px solid #d1fae5 !important; }
        .badge-unpaid  { background: #f1f5f9 !important; color: #475569 !important; border: 1px solid var(--border-2) !important; }
        .badge-partial { background: var(--warning-light) !important; color: #92400e !important; border: 1px solid #fde68a !important; }
        .badge-overdue { background: var(--danger-light) !important; color: #991b1b !important; border: 1px solid #fecaca !important; }
        .badge-low     { background: var(--warning-light) !important; color: #92400e !important; border: 1px solid #fde68a !important; }
        .badge-red     { background: var(--danger-light) !important; color: #991b1b !important; border: 1px solid #fecaca !important; }
        .badge-blue    { background: var(--accent-light) !important; color: #1d4ed8 !important; border: 1px solid var(--accent-dim) !important; }
        .badge-purple  { background: var(--purple-light) !important; color: #6d28d9 !important; border: 1px solid #ddd6fe !important; }

        /* ── Page header ──────────────────────────────────────── */
        .page-hdr { margin-bottom: 1.25rem; display: flex; align-items: flex-start; justify-content: space-between; }
        .page-hdr h5, .page-hdr .page-title, .page-hdr-greeting { font-size: 1rem; font-weight: 800; color: var(--text); margin: 0; }
        .page-hdr .page-sub, .page-hdr-sub { font-size: .72rem; color: var(--text-4); margin-top: 2px; }

        /* ── Section label ────────────────────────────────────── */
        .sect-lbl {
            font-size: .6rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 1px; color: var(--text-4);
            margin: 1.25rem 0 .6rem; display: flex; align-items: center; gap: .4rem;
        }
        .sect-lbl::after { content: ''; flex: 1; height: 1px; background: var(--border); }
        /* backward compat */
        .sect-label { font-size: .6rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--text-4); margin-bottom: .5rem; }

        /* ── Card header (design system) ──────────────────────── */
        .card-hdr {
            padding: .7rem 1rem;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between; gap: .5rem;
        }
        .card-hdr-title { font-size: .8rem; font-weight: 700; color: var(--text); display: flex; align-items: center; gap: .45rem; }

        /* ── KPI card ─────────────────────────────────────────── */
        .kpi {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: .85rem 1rem;
            box-shadow: var(--shadow);
            height: 100%;
        }
        .kpi-label { font-size: .58rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--text-4); margin-bottom: 4px; }
        .kpi-val   { font-size: 1.5rem; font-weight: 800; line-height: 1; }
        .kpi-bar   { height: 2px; background: var(--border); border-radius: 2px; margin-top: .5rem; overflow: hidden; }
        .kpi-bar-fill { height: 100%; border-radius: 2px; }
        .kpi-trend { font-size: .63rem; color: var(--text-4); margin-top: 4px; }

        /* ── Revenue card ─────────────────────────────────────── */
        .rev {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-left-width: 3px;
            border-radius: var(--radius);
            padding: .9rem 1rem;
            box-shadow: var(--shadow);
            position: relative; overflow: hidden;
            height: 100%;
        }
        .rev-label { font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 3px; }
        .rev-val   { font-size: 1.05rem; font-weight: 800; line-height: 1.15; }
        .rev-sub   { font-size: .65rem; color: var(--text-4); margin-top: 3px; }
        .rev-icon  { position: absolute; right: .75rem; top: 50%; transform: translateY(-50%); font-size: 1.5rem; opacity: .07; }

        /* ── Alert (DS pattern, Bootstrap dismiss compatible) ─── */
        .alert-ds {
            border-radius: var(--radius) !important;
            padding: .8rem 1rem !important;
            display: flex !important;
            align-items: flex-start !important;
            gap: .75rem !important;
            border: 1px solid transparent !important;
        }
        .alert-ds.alert-warn   { background: var(--warning-light) !important; border-left: 3px solid var(--warning) !important; border-color: #fde68a !important; }
        .alert-ds.alert-danger { background: var(--danger-light)  !important; border-left: 3px solid var(--danger)  !important; border-color: #fecaca !important; }
        .alert-ds-icon  { flex-shrink: 0; margin-top: 1px; font-size: .9rem; }
        .alert-ds-title { font-weight: 700; font-size: .8rem; margin-bottom: 3px; display: block; }
        .alert-ds-body  { font-size: .75rem; color: var(--text-2); flex: 1; min-width: 0; }
        .alert-tag {
            display: inline-flex; align-items: center; gap: .3rem;
            font-size: .72rem; font-weight: 600; padding: .18rem .5rem;
            border-radius: var(--radius-sm); text-decoration: none; border: 1px solid;
        }

        /* Flash alerts from session */
        .alert-flash {
            border: none !important; border-radius: var(--radius) !important;
            padding: .75rem 1rem !important; margin-bottom: .75rem;
            display: flex !important; align-items: center !important; gap: .6rem !important;
        }
        .alert-flash.success { background: var(--success-light) !important; border-left: 3px solid var(--success) !important; color: #166534 !important; }
        .alert-flash.error   { background: var(--danger-light)  !important; border-left: 3px solid var(--danger)  !important; color: #991b1b !important; }
        .alert-flash.warning { background: var(--warning-light) !important; border-left: 3px solid var(--warning) !important; color: #92400e !important; }

        /* ── List items ───────────────────────────────────────── */
        .list-item {
            display: flex; align-items: center; gap: .75rem;
            padding: .6rem 1rem;
            border-bottom: 1px solid var(--border);
            font-size: .78rem; color: var(--text-2);
            transition: background .1s;
        }
        .list-item:last-child { border-bottom: none; }
        .list-item:hover { background: #fafbff; }
        .dot-mark  { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
        .line-mark { width: 3px; height: 22px; border-radius: 2px; flex-shrink: 0; }

        /* ── Progress ─────────────────────────────────────────── */
        .progress-ds  { height: 4px; background: var(--border); border-radius: 4px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 4px; }
        /* backward compat */
        .deposit-progress { height: 5px; border-radius: 10px; background: var(--border-2); overflow: hidden; }
        .deposit-progress .bar { height: 100%; border-radius: 10px; transition: width .4s ease; }

        /* ── Data table ───────────────────────────────────────── */
        .data-table { width: 100%; border-collapse: collapse; font-size: .77rem; }
        .data-table thead th {
            background: #f8fafc; padding: .5rem .9rem;
            font-size: .62rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .5px; color: var(--text-3);
            border-bottom: 1px solid var(--border-2);
            text-align: left; white-space: nowrap;
        }
        .data-table tbody td {
            padding: .55rem .9rem; border-bottom: 1px solid var(--border);
            color: var(--text-2); vertical-align: middle;
        }
        .data-table tbody tr:last-child td { border-bottom: none; }
        .data-table tbody tr:hover td { background: #fafbff; }

        /* Table card (backward compat) */
        .table-card { border: 1px solid var(--border) !important; border-radius: var(--radius) !important; box-shadow: var(--shadow) !important; overflow: hidden; }
        .table-card .card-header { background: var(--card-bg) !important; border-bottom: 1px solid var(--border) !important; padding: .7rem 1rem !important; }
        .table-card thead th { background: #f8fafc; font-size: .62rem; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: var(--text-3); border-bottom: 1px solid var(--border) !important; padding: .55rem 1rem; }
        .table-card tbody td { padding: .55rem 1rem; font-size: .78rem; border-bottom: 1px solid var(--border) !important; vertical-align: middle; }
        .table-card tbody tr:last-child td { border-bottom: none !important; }
        .table-card tbody tr:hover { background: #fafbff; }

        /* Card clean (backward compat) */
        .card-clean { background: var(--card-bg) !important; border: 1px solid var(--border) !important; border-radius: var(--radius) !important; box-shadow: var(--shadow) !important; }
        .card-clean .card-header { background: var(--card-bg) !important; border-bottom: 1px solid var(--border) !important; }

        /* ── Quick action buttons ─────────────────────────────── */
        .qa-btn {
            display: flex; align-items: center; gap: .6rem;
            padding: .55rem .85rem; border-radius: var(--radius);
            background: #f8fafc; border: 1px solid var(--border-2);
            color: var(--text-2); font-size: .78rem; font-weight: 600;
            text-decoration: none; cursor: pointer; transition: all .12s; width: 100%;
        }
        .qa-btn:hover { background: var(--accent-light); border-color: var(--accent-dim); color: var(--accent); }
        .qa-btn.primary { background: var(--accent); border-color: var(--accent); color: #fff; }
        .qa-btn.primary:hover { background: #1d4ed8; color: #fff; }
        .qa-btn i { font-size: .88rem; flex-shrink: 0; }
        .qa-badge { margin-left: auto; background: var(--danger); color: #fff; font-size: .58rem; font-weight: 700; border-radius: 10px; padding: .1rem .38rem; }

        /* ── Stat mini ────────────────────────────────────────── */
        .stat-mini { background: var(--card-bg); border: 1px solid var(--border); border-radius: var(--radius); padding: .75rem .9rem; }
        .stat-mini-val { font-size: 1.4rem; font-weight: 800; line-height: 1; }
        .stat-mini-lbl { font-size: .63rem; color: var(--text-4); margin-top: 3px; }

        /* ── Due badge ────────────────────────────────────────── */
        .due-badge   { font-size: .65rem; font-weight: 700; border-radius: 5px; padding: .15rem .42rem; white-space: nowrap; }
        .due-normal  { background: #f1f5f9; color: #475569; }
        .due-warn3   { background: #fef3c7; color: #92400e; }
        .due-warn1   { background: #ffedd5; color: #9a3412; }
        .due-today   { background: var(--danger-light); color: #991b1b; }
        .due-overdue { background: #991b1b; color: #fff; }

        /* ── Reservation grid ────────────────────────────────── */
        .res-grid { display: grid; grid-template-columns: repeat(4, 1fr); }
        .res-cell { padding: .9rem .75rem; text-align: center; border-right: 1px solid var(--border); }
        .res-cell:last-child { border-right: none; }
        .res-val { font-size: 1.4rem; font-weight: 800; line-height: 1; }
        .res-lbl { font-size: .63rem; color: var(--text-4); margin-top: 3px; }

        /* ── Mobile ──────────────────────────────────────────── */
        @media (max-width: 767.98px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.show { transform: translateX(0); }
            #main-content { margin-left: 0; }
            .content-area { padding: .9rem; }
            #bottom-nav {
                display: flex !important; position: fixed;
                bottom: 0; left: 0; right: 0;
                background: var(--card-bg);
                border-top: 1px solid var(--border);
                z-index: 1025; height: 58px;
                box-shadow: 0 -2px 8px rgba(0,0,0,.05);
            }
            #main-content { padding-bottom: 68px; }
            .res-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (min-width: 768px) {
            #bottom-nav { display: none !important; }
            .topbar .btn-sidebar-toggle { display: none; }
        }

        /* ── Scrollbar ────────────────────────────────────────── */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    </style>
    @stack('styles')
</head>
<body>

<nav id="sidebar">
    <div class="sidebar-brand">
        @php $navbarLogo = \App\Models\Setting::get('navbar_logo_path'); @endphp
        @if($navbarLogo)
            <img src="{{ asset($navbarLogo) }}" alt="Logo" style="max-height:28px;max-width:130px;object-fit:contain;">
        @else
            <div class="sidebar-brand-icon"><i class="bi bi-receipt-cutoff"></i></div>
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

        {{-- ── ADMISSION ──────────────────────────────────────── --}}
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

        {{-- ── IT ────────────────────────────────────────────── --}}
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
                    <span class="sb-badge warn">{{ $pendingResets }}</span>
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

        {{-- ── Non-admission, Non-IT ──────────────────────────── --}}
        @if(!$user->isAdmission() && !$user->isIT())

        {{-- ── Operasional ──────────────────────────────────── --}}
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
                    <span class="sb-badge">{{ $pendingCount }}</span>
                @endif
            </a>
            <a href="{{ route('deposit-invoices.index') }}" class="nav-link {{ request()->routeIs('deposit-invoices.*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i> Invoice Deposit
            </a>
            <a href="{{ route('imports.index') }}" class="nav-link {{ request()->routeIs('imports.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-spreadsheet-fill"></i> Rekonsiliasi DSI
            </a>
            @endif
            <a href="{{ route('partners.index') }}" class="nav-link {{ request()->routeIs('partners.*') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i> Partner
            </a>
        </div>

        {{-- ── Reservasi ────────────────────────────────────── --}}
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
                    <span class="sb-badge">{{ $pendingAnomalies }}</span>
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

        {{-- ── Keuangan ────────────────────────────────────── --}}
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

        {{-- ── Pengaturan ──────────────────────────────────── --}}
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
                    <span class="sb-badge warn">{{ $pendingResets }}</span>
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
     style="background:rgba(0,0,0,.3);z-index:1025;" onclick="toggleSidebar()"></div>

<div id="main-content">
    <div class="topbar">
        <button class="btn btn-sm btn-sidebar-toggle me-2" onclick="toggleSidebar()"
                style="border:none;background:none;color:var(--text-3);padding:.2rem .4rem;">
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
            <div class="alert alert-flash success alert-dismissible fade show mb-3" role="alert">
                <i class="bi bi-check-circle-fill flex-shrink-0" style="font-size:.95rem"></i>
                <span>{{ session('success') }}</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-flash error alert-dismissible fade show mb-3" role="alert">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0" style="font-size:.95rem"></i>
                <span>{{ session('error') }}</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('warning'))
            <div class="alert alert-flash warning alert-dismissible fade show mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0" style="font-size:.95rem"></i>
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
        <a href="{{ route('admission.dashboard') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none {{ request()->routeIs('admission.dashboard') ? '' : '' }}"
           style="color:{{ request()->routeIs('admission.dashboard') ? 'var(--accent)' : 'var(--text-4)' }}">
            <i class="bi bi-door-open-fill" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Dashboard</span>
        </a>
        <a href="{{ route('admission.scan') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none"
           style="color:{{ request()->routeIs('admission.scan') ? 'var(--accent)' : 'var(--text-4)' }}">
            <i class="bi bi-upc-scan" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Scan</span>
        </a>
        <a href="{{ route('admission.qr') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none text-white"
           style="background:var(--accent);border-radius:50%;width:46px;height:46px;margin-top:-12px;box-shadow:0 4px 14px rgba(37,99,235,.35);flex-shrink:0;">
            <i class="bi bi-qr-code" style="font-size:1.2rem"></i>
        </a>
        <a href="{{ route('admission.history') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none"
           style="color:{{ request()->routeIs('admission.history') ? 'var(--accent)' : 'var(--text-4)' }}">
            <i class="bi bi-clock-history" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Riwayat</span>
        </a>
        <a href="#" onclick="toggleSidebar()" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none" style="color:var(--text-4)">
            <i class="bi bi-list" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Menu</span>
        </a>
    @elseif(auth()->user()->isIT())
        <a href="{{ route('users.index') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none"
           style="color:{{ request()->routeIs('users.*') ? 'var(--accent)' : 'var(--text-4)' }}">
            <i class="bi bi-person-gear" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Pengguna</span>
        </a>
        <a href="{{ route('admin.password-requests.index') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none"
           style="color:{{ request()->routeIs('admin.password-requests.*') ? 'var(--accent)' : 'var(--text-4)' }}">
            <i class="bi bi-key-fill" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Reset</span>
        </a>
        <a href="{{ route('users.create') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none text-white"
           style="background:var(--accent);border-radius:50%;width:46px;height:46px;margin-top:-12px;box-shadow:0 4px 14px rgba(37,99,235,.35);flex-shrink:0;">
            <i class="bi bi-plus-lg" style="font-size:1.2rem"></i>
        </a>
        <a href="{{ route('admin.audit-logs.index') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none"
           style="color:{{ request()->routeIs('admin.audit-logs.*') ? 'var(--accent)' : 'var(--text-4)' }}">
            <i class="bi bi-journal-text" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Audit</span>
        </a>
        <a href="#" onclick="toggleSidebar()" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none" style="color:var(--text-4)">
            <i class="bi bi-list" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Menu</span>
        </a>
    @elseif(auth()->user()->isBPM() || auth()->user()->isReservationStaff())
        <a href="{{ route('dashboard') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none"
           style="color:{{ request()->routeIs('dashboard') ? 'var(--accent)' : 'var(--text-4)' }}">
            <i class="bi bi-grid-1x2-fill" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Dashboard</span>
        </a>
        <a href="{{ route('reservations.index') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none"
           style="color:{{ request()->routeIs('reservations.*') ? 'var(--accent)' : 'var(--text-4)' }}">
            <i class="bi bi-ticket-detailed-fill" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Reservasi</span>
        </a>
        <a href="{{ route('reservations.create') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none text-white"
           style="background:var(--accent);border-radius:50%;width:46px;height:46px;margin-top:-12px;box-shadow:0 4px 14px rgba(37,99,235,.35);flex-shrink:0;">
            <i class="bi bi-plus-lg" style="font-size:1.2rem"></i>
        </a>
        <a href="{{ route('partners.index') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none"
           style="color:{{ request()->routeIs('partners.*') ? 'var(--accent)' : 'var(--text-4)' }}">
            <i class="bi bi-people-fill" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Partner</span>
        </a>
        <a href="#" onclick="toggleSidebar()" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none" style="color:var(--text-4)">
            <i class="bi bi-list" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Menu</span>
        </a>
    @elseif(auth()->user()->isBusdevHO())
        <a href="{{ route('dashboard') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none"
           style="color:{{ request()->routeIs('dashboard') ? 'var(--accent)' : 'var(--text-4)' }}">
            <i class="bi bi-grid-1x2-fill" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Dashboard</span>
        </a>
        <a href="{{ route('invoices.index') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none"
           style="color:{{ request()->routeIs('invoices.*') ? 'var(--accent)' : 'var(--text-4)' }}">
            <i class="bi bi-file-earmark-text-fill" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Invoice</span>
        </a>
        <a href="{{ route('reports.index') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none"
           style="color:{{ request()->routeIs('reports.*') ? 'var(--accent)' : 'var(--text-4)' }}">
            <i class="bi bi-graph-up" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Laporan</span>
        </a>
        <a href="{{ route('partners.index') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none"
           style="color:{{ request()->routeIs('partners.*') ? 'var(--accent)' : 'var(--text-4)' }}">
            <i class="bi bi-people-fill" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Partner</span>
        </a>
        <a href="#" onclick="toggleSidebar()" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none" style="color:var(--text-4)">
            <i class="bi bi-list" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Menu</span>
        </a>
    @else
        <a href="{{ route('dashboard') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none"
           style="color:{{ request()->routeIs('dashboard') ? 'var(--accent)' : 'var(--text-4)' }}">
            <i class="bi bi-grid-1x2-fill" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Dashboard</span>
        </a>
        <a href="{{ route('invoices.index') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none"
           style="color:{{ request()->routeIs('invoices.*') ? 'var(--accent)' : 'var(--text-4)' }}">
            <i class="bi bi-file-earmark-text-fill" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Invoice</span>
        </a>
        @if(auth()->user()->isAdmin() || auth()->user()->canAccessFinance())
        <a href="{{ route('invoices.create') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none text-white"
           style="background:var(--accent);border-radius:50%;width:46px;height:46px;margin-top:-12px;box-shadow:0 4px 14px rgba(37,99,235,.35);flex-shrink:0;">
            <i class="bi bi-plus-lg" style="font-size:1.2rem"></i>
        </a>
        @else
        <a href="{{ route('reservations.index') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none"
           style="color:{{ request()->routeIs('reservations.*') ? 'var(--accent)' : 'var(--text-4)' }}">
            <i class="bi bi-ticket-detailed-fill" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Reservasi</span>
        </a>
        @endif
        <a href="{{ route('partners.index') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none"
           style="color:{{ request()->routeIs('partners.*') ? 'var(--accent)' : 'var(--text-4)' }}">
            <i class="bi bi-people-fill" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Partner</span>
        </a>
        <a href="#" onclick="toggleSidebar()" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none" style="color:var(--text-4)">
            <i class="bi bi-list" style="font-size:1.2rem"></i>
            <span style="font-size:.58rem;font-weight:600;margin-top:2px">Menu</span>
        </a>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
// ── Currency Input Formatter — Global ────────────────────────────────────
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
