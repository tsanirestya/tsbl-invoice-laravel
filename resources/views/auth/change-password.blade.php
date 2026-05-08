<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password — TSBL Invoice</title>
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
        .card-wrap {
            width: 100%;
            max-width: 460px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
        }
        .card-header-custom {
            background: linear-gradient(135deg, #198754, #146c43);
            border-radius: 16px 16px 0 0;
            padding: 2rem;
            text-align: center;
        }
        .form-control { height: 48px; font-size: .95rem; }
        .btn-submit { height: 50px; font-size: 1rem; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card-wrap mx-3">
        <div class="card-header-custom">
            <div class="mb-2">
                <i class="bi bi-shield-lock text-white" style="font-size: 2.5rem;"></i>
            </div>
            <h5 class="text-white mb-0 fw-bold">Ganti Password</h5>
            <p class="text-white-50 small mb-0">Anda harus mengganti password sebelum melanjutkan</p>
        </div>

        <div class="p-4">
            @if(session('warning'))
                <div class="alert alert-warning py-2 small">
                    <i class="bi bi-exclamation-triangle me-1"></i>{{ session('warning') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger py-2 small">
                    <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}
                </div>
            @endif

            <p class="text-muted small mb-3">
                Halo, <strong>{{ auth()->user()->full_name }}</strong>. Password Anda perlu diganti. Buat password baru minimal 8 karakter.
            </p>

            <form method="POST" action="{{ route('password.change') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Password Baru</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="pwd-new" class="form-control"
                               placeholder="Minimal 8 karakter" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="toggle('pwd-new','icon-new')">
                            <i class="bi bi-eye" id="icon-new"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold">Konfirmasi Password Baru</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="password_confirmation" id="pwd-confirm" class="form-control"
                               placeholder="Ulangi password baru" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="toggle('pwd-confirm','icon-confirm')">
                            <i class="bi bi-eye" id="icon-confirm"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-success w-100 btn-submit">
                    <i class="bi bi-check-lg me-2"></i>Simpan Password Baru
                </button>
            </form>

            <div class="text-center mt-3">
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-link text-muted small p-0">
                        <i class="bi bi-box-arrow-left me-1"></i>Logout
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
    function toggle(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon  = document.getElementById(iconId);
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
