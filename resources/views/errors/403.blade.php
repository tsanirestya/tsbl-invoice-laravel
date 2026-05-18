<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Akses Ditolak</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: #f0f4fb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        .error-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(15,23,41,.1);
            padding: 2.5rem 2rem;
            text-align: center;
            max-width: 420px;
            width: 100%;
        }
        .error-icon {
            width: 72px; height: 72px;
            border-radius: 50%;
            background: #fef2f2;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.25rem;
            font-size: 2rem;
            color: #dc2626;
        }
        .error-code { font-size: 3rem; font-weight: 800; color: #1e293b; line-height: 1; }
        .error-title { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin: .5rem 0 .4rem; }
        .error-sub { font-size: .85rem; color: #64748b; margin-bottom: 1.5rem; }
        .role-badge {
            display: inline-flex; align-items: center; gap: .35rem;
            background: #f1f5f9; border: 1px solid #e2e8f0;
            border-radius: 50px; padding: .25rem .75rem;
            font-size: .75rem; font-weight: 600; color: #475569;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="error-card mx-3">
        <div class="error-icon">
            <i class="bi bi-shield-x"></i>
        </div>

        <div class="error-code">403</div>
        <div class="error-title">Akses Ditolak</div>
        <div class="error-sub">
            Role Anda tidak memiliki izin untuk mengakses halaman ini.
        </div>

        @auth
        <div class="role-badge">
            <i class="bi bi-person-fill"></i>
            {{ auth()->user()->full_name }}
            <span class="text-muted">&middot;</span>
            {{ auth()->user()->user_status }}
        </div>
        @endauth

        <div class="d-flex flex-column gap-2">
            @auth
                @if(auth()->user()->isAdmission())
                    <a href="{{ route('admission.dashboard') }}" class="btn btn-primary">
                        <i class="bi bi-house-fill me-1"></i> Kembali ke Dashboard
                    </a>
                @elseif(auth()->user()->isIT())
                    <a href="{{ route('users.index') }}" class="btn btn-primary">
                        <i class="bi bi-house-fill me-1"></i> Kembali ke Halaman Saya
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">
                        <i class="bi bi-house-fill me-1"></i> Kembali ke Dashboard
                    </a>
                @endif

                <a href="{{ route('logout.get') }}" class="btn btn-outline-danger">
                    <i class="bi bi-box-arrow-left me-1"></i> Logout
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Login
                </a>
            @endauth
        </div>
    </div>
</body>
</html>
