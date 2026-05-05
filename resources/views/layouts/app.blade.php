<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TSBL Invoice') — TSBL</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --topbar-height: 56px;
            --color-paid: #198754;
            --color-overdue: #dc3545;
            --color-partial: #fd7e14;
            --color-unpaid: #6c757d;
        }

        body { background: #f0f2f5; font-size: 14px; }

        /* Sidebar */
        #sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: #1a2235;
            position: fixed;
            top: 0; left: 0;
            z-index: 1030;
            transition: transform .25s ease;
        }
        #sidebar .sidebar-brand {
            height: var(--topbar-height);
            display: flex;
            align-items: center;
            padding: 0 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        #sidebar .sidebar-brand span {
            color: #fff;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: .5px;
        }
        #sidebar .nav-link {
            color: rgba(255,255,255,.7);
            padding: .6rem 1.25rem;
            border-radius: 6px;
            margin: 2px 8px;
            font-size: .875rem;
            transition: background .15s, color .15s;
        }
        #sidebar .nav-link:hover,
        #sidebar .nav-link.active {
            background: rgba(255,255,255,.12);
            color: #fff;
        }
        #sidebar .nav-link i { width: 22px; text-align: center; margin-right: 8px; }
        #sidebar .nav-section {
            color: rgba(255,255,255,.35);
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 1rem 1.5rem .25rem;
        }

        /* Main content */
        #main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }
        .topbar {
            height: var(--topbar-height);
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            padding: 0 1.25rem;
            position: sticky;
            top: 0;
            z-index: 1020;
        }
        .content-area { padding: 1.5rem; }

        /* Cards */
        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
            transition: transform .15s;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-card .stat-icon {
            width: 48px; height: 48px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
        }

        /* Status badges */
        .badge-paid    { background: var(--color-paid) !important; }
        .badge-overdue { background: var(--color-overdue) !important; }
        .badge-partial { background: var(--color-partial) !important; }
        .badge-unpaid  { background: var(--color-unpaid) !important; }

        /* Mobile */
        @media (max-width: 767.98px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.show { transform: translateX(0); }
            #main-content { margin-left: 0; }
            .content-area { padding: 1rem; }
            /* Bottom nav */
            #bottom-nav {
                display: flex !important;
                position: fixed;
                bottom: 0; left: 0; right: 0;
                background: #fff;
                border-top: 1px solid #e5e7eb;
                z-index: 1025;
                height: 60px;
            }
            #main-content { padding-bottom: 70px; }
        }
        @media (min-width: 768px) {
            #bottom-nav { display: none !important; }
            .topbar .btn-sidebar-toggle { display: none; }
        }
    </style>
    @stack('styles')
</head>
<body>

<!-- Sidebar -->
<nav id="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-receipt-cutoff text-primary me-2 fs-5"></i>
        <span>TSBL Invoice</span>
    </div>
    <div class="pt-2 pb-4">
        <div class="nav-section">Menu Utama</div>
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="#" class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-text"></i> Invoice
        </a>
        <a href="{{ route('partners.index') }}" class="nav-link {{ request()->routeIs('partners.*') ? 'active' : '' }}">
            <i class="bi bi-people"></i> Partner
        </a>
        <div class="nav-section">Keuangan</div>
        <a href="#" class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}">
            <i class="bi bi-cash-stack"></i> Pembayaran
        </a>
        <a href="#" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <i class="bi bi-bar-chart-line"></i> Laporan
        </a>
        <div class="nav-section">Pengaturan</div>
        <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
            <i class="bi bi-box-seam"></i> Produk
        </a>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
            <i class="bi bi-person-gear"></i> Pengguna
        </a>
        <a href="#" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
            <i class="bi bi-gear"></i> Pengaturan
        </a>
        @endif
        <div class="mt-3 mx-2">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-link btn btn-link text-start w-100 text-danger">
                    <i class="bi bi-box-arrow-left"></i> Keluar
                </button>
            </form>
        </div>
    </div>
</nav>

<!-- Sidebar backdrop (mobile) -->
<div id="sidebar-backdrop" class="d-none position-fixed top-0 start-0 w-100 h-100"
     style="background:rgba(0,0,0,.4);z-index:1025;" onclick="toggleSidebar()"></div>

<!-- Main -->
<div id="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <button class="btn btn-sm btn-outline-secondary btn-sidebar-toggle me-3" onclick="toggleSidebar()">
            <i class="bi bi-list fs-5"></i>
        </button>
        <span class="fw-semibold text-dark">@yield('page-title', 'Dashboard')</span>
        <div class="ms-auto d-flex align-items-center gap-2">
            <span class="d-none d-sm-inline text-muted small">{{ auth()->user()->full_name }}</span>
            <span class="badge bg-primary">{{ auth()->user()->user_status }}</span>
        </div>
    </div>

    <!-- Page content -->
    <div class="content-area">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>
</div>

<!-- Bottom Nav (mobile only) -->
<div id="bottom-nav" style="display:none">
    <a href="{{ route('dashboard') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none {{ request()->routeIs('dashboard') ? 'text-primary' : 'text-secondary' }}">
        <i class="bi bi-speedometer2 fs-5"></i>
        <span style="font-size:.65rem">Dashboard</span>
    </a>
    <a href="#" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none {{ request()->routeIs('invoices.*') ? 'text-primary' : 'text-secondary' }}">
        <i class="bi bi-file-earmark-text fs-5"></i>
        <span style="font-size:.65rem">Invoice</span>
    </a>
    <a href="#" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none text-white"
       style="background:#0d6efd;border-radius:50%;width:50px;height:50px;margin-top:-15px;box-shadow:0 4px 12px rgba(13,110,253,.4);">
        <i class="bi bi-plus-lg fs-4"></i>
    </a>
    <a href="{{ route('partners.index') }}" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none {{ request()->routeIs('partners.*') ? 'text-primary' : 'text-secondary' }}">
        <i class="bi bi-people fs-5"></i>
        <span style="font-size:.65rem">Partner</span>
    </a>
    <a href="#" onclick="toggleSidebar()" class="flex-fill d-flex flex-column align-items-center justify-content-center text-decoration-none text-secondary">
        <i class="bi bi-list fs-5"></i>
        <span style="font-size:.65rem">Menu</span>
    </a>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    sidebar.classList.toggle('show');
    backdrop.classList.toggle('d-none');
}
</script>
@stack('scripts')
</body>
</html>
