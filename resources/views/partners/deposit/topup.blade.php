@extends('layouts.app')

@section('title', 'Top-up Deposit — ' . $partner->nama_partner)
@section('page-title', 'Top-up Deposit')

@section('content')
<div class="d-flex gap-2 mb-3">
    <a href="{{ route('deposits.index', $partner) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold bg-white">
                <i class="bi bi-plus-circle text-success me-2"></i>
                Top-up Deposit — {{ $partner->nama_partner }}
            </div>
            <div class="card-body">
                @php $info = $partner->depositInfo(); @endphp
                <div class="alert alert-info py-2 mb-4">
                    <small><strong>Saldo saat ini:</strong> {{ $info['balance_formatted'] }}</small>
                </div>

                <form method="POST" action="{{ route('deposits.store', $partner) }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jumlah Top-up <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" inputmode="numeric" name="amount" class="form-control currency-input @error('amount') is-invalid @enderror"
                                   value="{{ old('amount') }}" required placeholder="Nominal top-up">
                        </div>
                        @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">No. Referensi / Bukti Transfer</label>
                        <input type="text" name="reference_no" class="form-control"
                               value="{{ old('reference_no') }}" maxlength="100"
                               placeholder="Opsional — no bukti transfer/cek">
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="3"
                                  placeholder="Opsional">{{ old('notes') }}</textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success px-4">
                            <i class="bi bi-check-lg me-1"></i> Simpan Top-up
                        </button>
                        <a href="{{ route('deposits.index', $partner) }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
