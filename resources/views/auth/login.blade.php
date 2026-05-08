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
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
        }
        .login-header {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            border-radius: 16px 16px 0 0;
            padding: 2rem;
            text-align: center;
        }
        .form-control {
            height: 48px;
            font-size: .95rem;
        }
        .btn-login {
            height: 50px;
            font-size: 1rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-card mx-3">
        <div class="login-header">
            <div class="mb-2">
                <i class="bi bi-receipt-cutoff text-white" style="font-size: 2.5rem;"></i>
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
                        <input type="email" name="email" class="form-control" placeholder="email@tsbl.co.id"
                               value="{{ old('email') }}" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
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
    </div>

    <script>
    function togglePwd() {
        const input = document.getElementById('password');
        const icon = document.getElementById('pwd-icon');
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
