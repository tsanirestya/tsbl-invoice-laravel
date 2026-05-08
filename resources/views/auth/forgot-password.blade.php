<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password — TSBL Invoice</title>
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
            max-width: 420px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
        }
        .card-header-custom {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
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
                <i class="bi bi-key text-white" style="font-size: 2.5rem;"></i>
            </div>
            <h5 class="text-white mb-0 fw-bold">Lupa Password</h5>
            <p class="text-white-50 small mb-0">TSBL Invoice — Finance System</p>
        </div>

        <div class="p-4">
            @if(session('success'))
                <div class="alert alert-success py-2 small">
                    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                </div>
            @endif

            @if(session('info'))
                <div class="alert alert-info py-2 small">
                    <i class="bi bi-info-circle me-1"></i>{{ session('info') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger py-2 small">
                    <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}
                </div>
            @endif

            <p class="text-muted small mb-3">
                Masukkan email Anda. Admin akan menerima notifikasi dan menyiapkan password sementara untuk Anda.
            </p>

            <form method="POST" action="{{ route('password.request.submit') }}">
                @csrf
                <div class="mb-4">
                    <label class="form-label small fw-semibold">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="email@tsbl.co.id"
                               value="{{ old('email') }}" required autofocus>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-submit">
                    <i class="bi bi-send me-2"></i>Kirim Permintaan
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="{{ route('login') }}" class="text-muted small">
                    <i class="bi bi-arrow-left me-1"></i>Kembali ke Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>
