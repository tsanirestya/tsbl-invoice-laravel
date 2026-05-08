@extends('layouts.app')

@section('title', 'Permintaan Reset Password')
@section('page-title', 'Permintaan Reset Password')

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold">
            <i class="bi bi-key me-1 text-warning"></i>
            Daftar Permintaan Reset Password
        </span>
        <span class="badge bg-warning text-dark">{{ $requests->count() }} pending</span>
    </div>

    <div class="card-body p-0">
        @if($requests->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-check-circle display-6 text-success"></i>
                <p class="mt-2 mb-0">Tidak ada permintaan reset password.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Waktu Request</th>
                            <th class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $user)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $user->full_name }}</td>
                            <td class="text-muted small">{{ $user->email }}</td>
                            <td>
                                <span class="badge bg-secondary">{{ $user->user_status }}</span>
                            </td>
                            <td class="small text-muted">
                                {{ $user->reset_requested_at->format('d M Y, H:i') }}
                                <span class="text-muted">({{ $user->reset_requested_at->diffForHumans() }})</span>
                            </td>
                            <td class="text-end pe-3">
                                <button type="button" class="btn btn-warning btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modal-reset-{{ $user->id }}">
                                    <i class="bi bi-key me-1"></i>Set Password Sementara
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Modals --}}
@foreach($requests as $user)
<div class="modal fade" id="modal-reset-{{ $user->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.password-requests.resolve', $user) }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">
                        <i class="bi bi-key me-1 text-warning"></i>
                        Set Password Sementara — {{ $user->full_name }}
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        Password sementara akan dikirim ke user secara manual (WhatsApp/lisan).
                        User <strong>wajib ganti password</strong> saat login berikutnya.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Password Sementara <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="temp_password" id="temp-pwd-{{ $user->id }}"
                                   class="form-control" placeholder="Min. 8 karakter" required minlength="8">
                            <button type="button" class="btn btn-outline-secondary"
                                onclick="generatePwd({{ $user->id }})">
                                <i class="bi bi-arrow-repeat"></i>
                            </button>
                        </div>
                        <div class="form-text">Klik <i class="bi bi-arrow-repeat"></i> untuk generate otomatis.</div>
                    </div>
                    <div class="alert alert-info py-2 small">
                        <i class="bi bi-info-circle me-1"></i>
                        Email: <strong>{{ $user->email }}</strong> — sampaikan password ini secara aman ke user.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="bi bi-check-lg me-1"></i>Simpan & Notifikasi User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@endsection

@push('scripts')
<script>
function generatePwd(userId) {
    const chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#';
    let pwd = '';
    for (let i = 0; i < 12; i++) {
        pwd += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('temp-pwd-' + userId).value = pwd;
}
</script>
@endpush
