@php $editing = isset($user); @endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
        <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
               value="{{ old('full_name', $user->full_name ?? '') }}" required>
        @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $user->email ?? '') }}" required>
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">No. Telepon</label>
        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
               value="{{ old('phone', $user->phone ?? '') }}">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Jabatan</label>
        <input type="text" name="position_name" class="form-control @error('position_name') is-invalid @enderror"
               value="{{ old('position_name', $user->position_name ?? '') }}">
        @error('position_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Password {{ $editing ? '(kosongkan jika tidak diubah)' : '' }} <span class="text-danger">{{ $editing ? '' : '*' }}</span></label>
        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
               {{ $editing ? '' : 'required' }} autocomplete="new-password">
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Konfirmasi Password</label>
        <input type="password" name="password_confirmation" class="form-control"
               {{ $editing ? '' : 'required' }} autocomplete="new-password">
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
        <select name="user_status" class="form-select @error('user_status') is-invalid @enderror" required>
            @foreach([
                'ADMIN'             => 'Admin — Full access',
                'IT'                => 'IT — System & user management',
                'BUSDEV_HO'         => 'Busdev HO — Monitor bisnis (read-only)',
                'FINANCE_STAFF'     => 'Finance Staff — Operasional invoice & import',
                'FINANCE_MANAGER'   => 'Finance Manager — Approval & tanda tangan',
                'BPM'               => 'Business Partner Manager — Partner & reservasi',
                'RESERVATION_STAFF' => 'Reservation Staff — Reservasi saja',
                'ADMISSION'         => 'Admission — QR scan & redeem',
            ] as $value => $label)
                <option value="{{ $value }}" @selected(old('user_status', $user->user_status ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('user_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Status</label>
        <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                   id="isActive" @checked(old('is_active', $user->is_active ?? true))>
            <label class="form-check-label" for="isActive">Aktif</label>
        </div>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Tanda Tangan (gambar)</label>
        <input type="file" name="signature_image" class="form-control @error('signature_image') is-invalid @enderror"
               accept="image/*">
        <div class="form-text">Max 2MB. JPG/PNG.</div>
        @error('signature_image') <div class="invalid-feedback">{{ $message }}</div> @enderror
        @if($editing && $user->signature_image)
            <div class="mt-2">
                <img src="{{ Storage::url($user->signature_image) }}" height="48" class="rounded border" alt="TTD saat ini">
                <div class="form-text">Tanda tangan saat ini</div>
            </div>
        @endif
    </div>
</div>
