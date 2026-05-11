@extends('layouts.app')
@section('title', 'Reservasi — '.$reservation->reservation_no)
@section('page-title', 'Reservasi')

@push('styles')
<style>
.timeline{list-style:none;padding:0;margin:0;position:relative}
.timeline::before{content:'';position:absolute;left:15px;top:0;bottom:0;width:2px;background:#e2e8f0}
.timeline li{position:relative;padding:.6rem 0 .6rem 42px}
.timeline li::before{content:'';position:absolute;left:9px;top:14px;width:14px;height:14px;border-radius:50%;background:#e2e8f0;border:2px solid #fff;box-shadow:0 0 0 2px #e2e8f0}
.timeline li.done::before{background:#3b82f6;box-shadow:0 0 0 2px #bfdbfe}
.timeline li.active::before{background:#10b981;box-shadow:0 0 0 3px rgba(16,185,129,.2)}
.timeline li.error::before{background:#ef4444;box-shadow:0 0 0 2px #fecaca}
.tl-title{font-size:.82rem;font-weight:600;color:#1e293b}
.tl-sub{font-size:.73rem;color:#94a3b8;margin-top:1px}
.detail-label{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8;margin-bottom:2px}
.detail-value{font-size:.88rem;color:#1e293b;font-weight:500}
.sb{display:inline-flex;align-items:center;gap:.25rem;font-size:.75rem;font-weight:700;border-radius:20px;padding:.3em .8em}
.sb-pending{background:#eff6ff;color:#1d4ed8}.sb-confirmed{background:#f0fdf4;color:#166534}
.sb-cancelled{background:#fef2f2;color:#991b1b}.sb-no_show{background:#fff7ed;color:#c2410c}
.sb-completed{background:#dcfce7;color:#15803d}
</style>
@endpush

@section('content')
@php
    $sCls='sb-'.strtolower($reservation->status);
    $sLabel=match($reservation->status){'PENDING'=>'Pending','CONFIRMED'=>'Confirmed','CANCELLED'=>'Cancelled','NO_SHOW'=>'No Show','COMPLETED'=>'Completed',default=>$reservation->status};
@endphp

<div class="d-flex align-items-center gap-2 mb-3 page-hdr">
    <a href="{{ route('reservations.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;"><i class="bi bi-arrow-left"></i></a>
    <div>
        <div class="page-title">{{ $reservation->reservation_no }}</div>
        <div class="page-sub">Detail Reservasi</div>
    </div>
    <div class="ms-auto"><span class="sb {{ $sCls }}">{{ $sLabel }}</span></div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        {{-- Info --}}
        <div class="card card-clean mb-3">
            <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-info-circle" style="color:#3b82f6"></i>Informasi Reservasi</div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-6 col-md-4"><div class="detail-label">Partner</div><div class="detail-value">{{ $reservation->partner?->name ?? '-' }}</div></div>
                    <div class="col-6 col-md-4"><div class="detail-label">Tamu</div><div class="detail-value">{{ $reservation->guest_name ?? '-' }}</div></div>
                    <div class="col-6 col-md-4"><div class="detail-label">Booking Ref</div><div class="detail-value">{{ $reservation->booking_ref ?? '-' }}</div></div>
                    <div class="col-6 col-md-4"><div class="detail-label">Check-in</div><div class="detail-value">{{ $reservation->check_in_date?->format('d M Y') ?? '-' }}</div></div>
                    <div class="col-6 col-md-4"><div class="detail-label">Check-out</div><div class="detail-value">{{ $reservation->check_out_date?->format('d M Y') ?? '-' }}</div></div>
                    <div class="col-6 col-md-4"><div class="detail-label">Pax</div><div class="detail-value">{{ $reservation->pax ?? '-' }}</div></div>
                    <div class="col-6 col-md-4"><div class="detail-label">Produk</div><div class="detail-value">{{ $reservation->product_type ?? '-' }}</div></div>
                    <div class="col-6 col-md-4"><div class="detail-label">Est. Proforma</div><div class="detail-value fw-bold">{{ $reservation->proforma_amount ? 'Rp '.number_format($reservation->proforma_amount,0,',','.') : '-' }}</div></div>
                    @if($reservation->cancel_reason)
                    <div class="col-12"><div class="detail-label text-danger">Alasan Pembatalan</div><div class="detail-value text-danger">{{ $reservation->cancel_reason }}</div></div>
                    @endif
                    @if($reservation->notes)
                    <div class="col-12"><div class="detail-label">Catatan</div><div class="detail-value" style="white-space:pre-wrap;">{{ $reservation->notes }}</div></div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Proforma Invoices --}}
        @if($reservation->proformaInvoices->isNotEmpty())
        <div class="card card-clean mb-3">
            <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-file-earmark-text" style="color:#3b82f6"></i>Proforma Invoice</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0" style="font-size:.83rem;">
                    <thead><tr>
                        <th class="ps-4" style="background:#f8fafc;font-size:.65rem;text-transform:uppercase;color:#64748b;">No Invoice</th>
                        <th style="background:#f8fafc;font-size:.65rem;text-transform:uppercase;color:#64748b;">Tipe</th>
                        <th class="text-end" style="background:#f8fafc;font-size:.65rem;text-transform:uppercase;color:#64748b;">Total</th>
                        <th class="text-center pe-3" style="background:#f8fafc;font-size:.65rem;text-transform:uppercase;color:#64748b;">Status</th>
                    </tr></thead>
                    <tbody>
                        @foreach($reservation->proformaInvoices as $inv)
                        @php $ps=$inv->payment_status;$pcls=match($ps){'PAID'=>'badge-paid','PARTIAL'=>'badge-partial','OVERDUE'=>'badge-overdue',default=>'badge-unpaid'}; @endphp
                        <tr>
                            <td class="ps-4"><a href="{{ route('billing-invoices.show',$inv) }}" style="color:#1e40af;font-weight:700;text-decoration:none;">{{ $inv->invoice_no }}</a></td>
                            <td><span class="badge" style="background:#eff6ff;color:#1d4ed8;">{{ $inv->invoice_type }}</span></td>
                            <td class="text-end fw-bold">Rp {{ number_format($inv->grand_total,0,',','.') }}</td>
                            <td class="text-center pe-3"><span class="badge {{ $pcls }}">{{ $ps }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- DSI Transactions --}}
        @if($reservation->dsiTransactions->isNotEmpty())
        <div class="card card-clean">
            <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-table" style="color:#06b6d4"></i>Transaksi DSI</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0" style="font-size:.83rem;">
                    <thead><tr>
                        <th class="ps-4" style="background:#f8fafc;font-size:.65rem;text-transform:uppercase;color:#64748b;">Ref No</th>
                        <th class="text-end" style="background:#f8fafc;font-size:.65rem;text-transform:uppercase;color:#64748b;">Jumlah</th>
                        <th class="text-center pe-3" style="background:#f8fafc;font-size:.65rem;text-transform:uppercase;color:#64748b;">Locked</th>
                    </tr></thead>
                    <tbody>
                        @foreach($reservation->dsiTransactions as $dsi)
                        <tr>
                            <td class="ps-4 fw-semibold">{{ $dsi->ref_no }}</td>
                            <td class="text-end fw-bold">Rp {{ number_format($dsi->total_amount ?? 0,0,',','.') }}</td>
                            <td class="text-center pe-3">
                                @if($dsi->is_locked)
                                    <span class="badge" style="background:#fef2f2;color:#991b1b;"><i class="bi bi-lock-fill"></i> Locked</span>
                                @else
                                    <span class="badge" style="background:#f0fdf4;color:#166534;"><i class="bi bi-unlock-fill"></i> Open</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        {{-- Timeline --}}
        <div class="card card-clean mb-3">
            <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-activity" style="color:#8b5cf6"></i>Alur Reservasi</div>
            <div class="card-body px-3 py-3">
                <ul class="timeline">
                    <li class="done"><div class="tl-title">Reservasi Dibuat</div><div class="tl-sub">{{ $reservation->created_at?->format('d M Y, H:i') }}</div></li>
                    <li class="{{ in_array($reservation->status,['CONFIRMED','COMPLETED','NO_SHOW'])?'done':($reservation->status==='CANCELLED'?'error':'') }}">
                        <div class="tl-title">Dikonfirmasi</div>
                        <div class="tl-sub">{{ $reservation->status==='CANCELLED'?'CANCELLED':(in_array($reservation->status,['CONFIRMED','COMPLETED','NO_SHOW'])?'Confirmed':'Menunggu') }}</div>
                    </li>
                    <li class="{{ $reservation->proformaInvoices->isNotEmpty()?'done':'' }}">
                        <div class="tl-title">Proforma Invoice</div>
                        <div class="tl-sub">{{ $reservation->proformaInvoices->isNotEmpty()?$reservation->proformaInvoices->first()->invoice_no:'Belum diterbitkan' }}</div>
                    </li>
                    <li class="{{ $reservation->dsiTransactions->isNotEmpty()?'done':'' }}">
                        <div class="tl-title">DSI Diimpor</div>
                        <div class="tl-sub">{{ $reservation->dsiTransactions->isNotEmpty()?$reservation->dsiTransactions->count().' transaksi':'Belum ada data DSI' }}</div>
                    </li>
                    <li class="{{ $reservation->reconciliations->isNotEmpty()?'active':'' }}">
                        <div class="tl-title">Rekonsiliasi</div>
                        <div class="tl-sub">{{ $reservation->reconciliations->isNotEmpty()?$reservation->reconciliations->first()->status:'Belum direkonsiliasi' }}</div>
                    </li>
                    <li class="{{ $reservation->status==='COMPLETED'?'done':'' }}"><div class="tl-title">Selesai</div><div class="tl-sub">{{ $reservation->status==='COMPLETED'?'Completed':'Menunggu' }}</div></li>
                </ul>
            </div>
        </div>

        {{-- Actions --}}
        @if(auth()->user()->isAdmin() || auth()->user()->user_status === 'FINANCE')
        <div class="card card-clean">
            <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-lightning-charge-fill" style="color:#f59e0b"></i>Tindakan</div>
            <div class="card-body d-grid gap-2 p-3">
                @if($reservation->status === 'PENDING')
                <form method="POST" action="{{ route('reservations.confirm',$reservation) }}" onsubmit="return confirm('Konfirmasi reservasi ini?')">@csrf
                    <button type="submit" class="btn btn-success btn-sm w-100"><i class="bi bi-check-circle me-1"></i>Konfirmasi Reservasi</button>
                </form>
                @endif
                @if($reservation->status === 'CONFIRMED' && $reservation->proformaInvoices->isEmpty())
                <button type="button" class="btn btn-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#modalProforma"><i class="bi bi-file-earmark-plus me-1"></i>Terbitkan Proforma</button>
                @endif
                @if(!in_array($reservation->status,['CANCELLED','COMPLETED']))
                <button type="button" class="btn btn-outline-danger btn-sm w-100" data-bs-toggle="modal" data-bs-target="#modalCancel"><i class="bi bi-x-circle me-1"></i>Batalkan Reservasi</button>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Modal Proforma --}}
<div class="modal fade" id="modalProforma" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:12px;border:none;">
            <div class="modal-header" style="border-bottom:1px solid #f1f5f9;">
                <h6 class="modal-title fw-bold"><i class="bi bi-file-earmark-plus me-2" style="color:#3b82f6"></i>Terbitkan Proforma Invoice</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('reservations.issue-proforma',$reservation) }}" id="proforma-form">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">Jatuh Tempo</label>
                            <input type="date" name="due_date" class="form-control" value="{{ now()->addDays(30)->format('Y-m-d') }}">
                        </div>
                    </div>
                    <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8;" class="mb-2">Item Invoice</div>
                    <div id="proforma-items">
                        <div class="row g-2 mb-2 proforma-item">
                            <div class="col-md-5"><input type="text" name="items[0][description]" class="form-control" placeholder="Deskripsi" required></div>
                            <div class="col-md-2"><input type="number" name="items[0][quantity]" class="form-control" placeholder="Qty" value="1" min="0" step="any" required></div>
                            <div class="col-md-3"><input type="text" name="items[0][unit_price]" class="form-control currency-input" placeholder="Harga Satuan" required></div>
                            <div class="col-md-2"><input type="text" name="items[0][amount]" class="form-control currency-input" placeholder="Total" required readonly style="background:#f8fafc;"></div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm mt-1" onclick="addProformaItem()"><i class="bi bi-plus"></i> Tambah Item</button>
                    <div class="d-flex justify-content-end mt-3">
                        <div class="text-end">
                            <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;color:#94a3b8;">Grand Total</div>
                            <div style="font-size:1.1rem;font-weight:800;color:#1e40af;" id="proforma-grand-total">Rp 0</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">Catatan</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check me-1"></i>Terbitkan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Cancel --}}
<div class="modal fade" id="modalCancel" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:12px;border:none;">
            <div class="modal-header" style="border-bottom:1px solid #f1f5f9;">
                <h6 class="modal-title fw-bold text-danger"><i class="bi bi-x-circle me-2"></i>Batalkan Reservasi</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('reservations.cancel',$reservation) }}">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-modern mb-3" style="background:#fff7ed;border-left:4px solid #f59e0b;color:#92400e;"><i class="bi bi-exclamation-triangle-fill me-2"></i>Tidak dapat dibatalkan jika ada transaksi DSI terkunci.</div>
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Alasan Pembatalan <span class="text-danger">*</span></label>
                    <textarea name="cancel_reason" class="form-control" rows="3" required placeholder="Tuliskan alasan..."></textarea>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle me-1"></i>Ya, Batalkan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var itemIdx=1;
function addProformaItem(){
    var i=itemIdx++;
    var html=`<div class="row g-2 mb-2 proforma-item">
        <div class="col-md-5"><input type="text" name="items[${i}][description]" class="form-control" placeholder="Deskripsi" required></div>
        <div class="col-md-2"><input type="number" name="items[${i}][quantity]" class="form-control" placeholder="Qty" value="1" min="0" step="any" required></div>
        <div class="col-md-3"><input type="text" name="items[${i}][unit_price]" class="form-control currency-input" placeholder="Harga" required></div>
        <div class="col-md-2"><input type="text" name="items[${i}][amount]" class="form-control currency-input" placeholder="Total" required readonly style="background:#f8fafc;"></div>
    </div>`;
    document.getElementById('proforma-items').insertAdjacentHTML('beforeend',html);
    initCurrencyInputs(document.getElementById('proforma-items'));
}
document.getElementById('proforma-items').addEventListener('input',function(e){
    var row=e.target.closest('.proforma-item');
    if(!row)return;
    var qty=parseFloat(row.querySelector('[name$="[quantity]"]').value)||0;
    var price=parseRaw(row.querySelector('[name$="[unit_price]"]').value)||0;
    var amtEl=row.querySelector('[name$="[amount]"]');
    var total=qty*price;
    amtEl.value=total>0?fmtCurrency(total):'';
    var grand=0;
    document.querySelectorAll('[name$="[amount]"]').forEach(function(el){grand+=parseRaw(el.value)||0;});
    document.getElementById('proforma-grand-total').textContent='Rp '+new Intl.NumberFormat('id-ID').format(grand);
});
document.getElementById('proforma-form')?.addEventListener('submit',function(){
    document.querySelectorAll('[name$="[amount]"],[name$="[unit_price]"]').forEach(function(el){el.value=String(parseRaw(el.value)||0);});
});
</script>
@endpush
