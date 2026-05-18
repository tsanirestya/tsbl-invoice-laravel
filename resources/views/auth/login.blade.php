<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — TSBL Invoice</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a2235 0%, #243047 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 0;
        }
        .login-wrap {
            width: 100%;
            max-width: 460px;
            padding: 0 1rem;
        }
        .login-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 24px 64px rgba(0,0,0,.35);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            padding: 2rem;
            text-align: center;
        }
        .form-control { height: 48px; font-size: .95rem; }
        .btn-login    { height: 50px; font-size: 1rem; font-weight: 600; }

        /* ── Dev mode ─────────────────────────────────────────── */
        .dev-banner {
            background: linear-gradient(90deg, #92400e, #b45309);
            color: #fef3c7;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            text-align: center;
            padding: .4rem 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
        }
        .dev-section {
            border-top: 1px solid #f1f5f9;
        }
        .dev-section-title {
            color: #b45309;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: .85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
        }
        .dev-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .5rem;
        }
        .dev-role-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .25rem;
            padding: .65rem .5rem;
            border-radius: 10px;
            text-decoration: none;
            border: 1.5px solid transparent;
            transition: transform .14s ease, box-shadow .14s ease;
            cursor: pointer;
        }
        .dev-role-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(0,0,0,.12);
        }
        .dev-role-card:active { transform: scale(.97); }
        .dev-role-icon {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .95rem;
            flex-shrink: 0;
        }
        .dev-role-name {
            font-size: .72rem;
            font-weight: 700;
            line-height: 1.2;
            text-align: center;
        }
        .dev-role-email {
            font-size: .58rem;
            opacity: .6;
            text-align: center;
            line-height: 1.2;
        }

        /* Role colour palette — light mode */
        .dr-admin     { background: #f5f3ff; border-color: #c4b5fd; color: #5b21b6; }
        .dr-admin     .dev-role-icon { background: #7c3aed; color: #fff; }
        .dr-it        { background: #f8fafc; border-color: #cbd5e1; color: #334155; }
        .dr-it        .dev-role-icon { background: #475569; color: #fff; }
        .dr-busdev    { background: #ecfeff; border-color: #a5f3fc; color: #0e7490; }
        .dr-busdev    .dev-role-icon { background: #0891b2; color: #fff; }
        .dr-finstaff  { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
        .dr-finstaff  .dev-role-icon { background: #2563eb; color: #fff; }
        .dr-finmgr    { background: #f0f9ff; border-color: #bae6fd; color: #0369a1; }
        .dr-finmgr    .dev-role-icon { background: #0284c7; color: #fff; }
        .dr-bpm       { background: #f0fdf4; border-color: #bbf7d0; color: #15803d; }
        .dr-bpm       .dev-role-icon { background: #16a34a; color: #fff; }
        .dr-resv      { background: #fffbeb; border-color: #fde68a; color: #b45309; }
        .dr-resv      .dev-role-icon { background: #d97706; color: #fff; }
        .dr-admission { background: #fdf2f8; border-color: #fbcfe8; color: #be185d; }
        .dr-admission .dev-role-icon { background: #db2777; color: #fff; }
    </style>
</head>
<body>
    <div class="login-wrap">
        <div class="login-card">

            @if($devMode)
            <div class="dev-banner">
                <i class="bi bi-bug-fill"></i>
                Dev Mode Aktif — Jangan gunakan di production
            </div>
            @endif

            <div class="login-header">
                <div class="mb-2">
                    <i class="bi bi-receipt-cutoff text-white" style="font-size:2.5rem"></i>
                </div>
                <h5 class="text-white mb-0 fw-bold">TSBL Invoice</h5>
                <p class="text-white-50 small mb-0">Finance Operational System</p>
            </div>

            <div class="p-4">
                <h6 class="fw-semibold mb-4 text-center text-muted">Masuk ke Akun Anda</h6>

                @if($errors->any())
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-exclamation-circle me-1"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control"
                                   placeholder="email@tsbl.co.id"
                                   value="{{ old('email') }}" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" id="password"
                                   class="form-control" placeholder="••••••••" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePwd()">
                                <i class="bi bi-eye" id="pwd-icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-4 d-flex align-items-center justify-content-between">
                        <div class="form-check mb-0">
                            <input type="checkbox" name="remember" class="form-check-input" id="remember">
                            <label class="form-check-label small" for="remember">Ingat saya</label>
                        </div>
                        <a href="{{ route('password.request') }}" class="small text-primary text-decoration-none">
                            Lupa password?
                        </a>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-login">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
                    </button>
                </form>

                <p class="text-center text-muted small mt-3 mb-0">
                    TSBL &copy; {{ date('Y') }} — Finance System
                </p>
            </div>

            @if($devMode)
            <div class="dev-section p-4 pt-3">
                <div class="dev-section-title">
                    <i class="bi bi-lightning-charge-fill"></i>
                    Quick Login — Dev Testing
                </div>
                <div class="dev-grid">
                    @foreach([
                        ['ADMIN',             'bi-shield-fill-check', 'dr-admin',    'Admin',           'dev.admin'],
                        ['IT',                'bi-pc-display',        'dr-it',       'IT',              'dev.it'],
                        ['BUSDEV_HO',         'bi-graph-up-arrow',    'dr-busdev',   'Busdev HO',       'dev.busdev'],
                        ['FINANCE_STAFF',     'bi-calculator-fill',   'dr-finstaff', 'Finance Staff',   'dev.finstaff'],
                        ['FINANCE_MANAGER',   'bi-person-badge-fill', 'dr-finmgr',  'Finance Manager', 'dev.finmanager'],
                        ['BPM',               'bi-people-fill',       'dr-bpm',      'BPM',             'dev.bpm'],
                        ['RESERVATION_STAFF', 'bi-calendar-check-fill','dr-resv',   'Reservasi Staff', 'dev.reservation'],
                        ['ADMISSION',         'bi-door-open-fill',    'dr-admission','Admission',       'dev.admission'],
                    ] as [$role, $icon, $colorClass, $label, $emailPrefix])
                    <a href="{{ route('dev.login', $role) }}" class="dev-role-card {{ $colorClass }}">
                        <div class="dev-role-icon">
                            <i class="bi {{ $icon }}"></i>
                        </div>
                        <div class="dev-role-name">{{ $label }}</div>
                        <div class="dev-role-email">{{ $emailPrefix }}@tsbl.dev</div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    </div>

    <script>
    function togglePwd() {
        const input = document.getElementById('password');
        const icon  = document.getElementById('pwd-icon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye';
        }
    }
    </script>
</body>
</html>
