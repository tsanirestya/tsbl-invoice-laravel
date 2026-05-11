<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>{{ $billingInvoice->invoice_no }} — TSBL Invoice</title>
<style>
    * { margin:0;padding:0;box-sizing:border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size:11px; color:#1e293b; background:#fff; }
    .page { padding:32px 40px; max-width:800px; margin:0 auto; }

    /* Header */
    .header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:24px; padding-bottom:16px; border-bottom:2px solid #e2e8f0; }
    .company-name { font-size:16px; font-weight:800; color:#1e293b; letter-spacing:-.5px; }
    .company-sub  { font-size:9px; color:#94a3b8; margin-top:2px; }
    .inv-meta { text-align:right; }
    .inv-no { font-size:14px; font-weight:800; color:#1e40af; }
    .inv-type { display:inline-block; padding:2px 8px; border-radius:20px; font-size:9px; font-weight:700; margin-top:3px; }
    .type-PROFORMA    { background:#eff6ff; color:#1d4ed8; }
    .type-FINAL       { background:#f0fdf4; color:#15803d; }
    .type-CREDIT_NOTE { background:#fff7ed; color:#c2410c; }
    .type-DEBIT_NOTE  { background:#fef2f2; color:#991b1b; }
    .type-CANCELLATION{ background:#f1f5f9; color:#64748b; }

    /* Addresses */
    .addresses { display:flex; gap:20px; margin-bottom:20px; }
    .addr-block { flex:1; }
    .addr-label { font-size:8px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#94a3b8; margin-bottom:4px; }
    .addr-name  { font-size:11px; font-weight:700; }
    .addr-sub   { font-size:9.5px; color:#64748b; margin-top:2px; }

    /* Details Row */
    .inv-details { display:flex; gap:20px; background:#f8fafc; padding:10px 14px; border-radius:6px; margin-bottom:20px; }
    .inv-detail-item { flex:1; }
    .inv-detail-label { font-size:8px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#94a3b8; margin-bottom:2px; }
    .inv-detail-value { font-size:10px; font-weight:600; }

    /* Table */
    table { width:100%; border-collapse:collapse; margin-bottom:16px; }
    thead th { background:#f1f5f9; font-size:8px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#64748b; padding:7px 10px; border-bottom:1px solid #e2e8f0; }
    tbody td { padding:8px 10px; font-size:10px; border-bottom:1px solid #f1f5f9; }
    tbody tr:last-child td { border-bottom:none; }
    .text-right { text-align:right; }
    .text-center { text-align:center; }

    /* Totals */
    .totals { display:flex; justify-content:flex-end; }
    .totals-table { width:240px; }
    .totals-table td { padding:4px 10px; font-size:10px; }
    .totals-table .grand { font-size:12px; font-weight:800; color:#1e40af; border-top:2px solid #e2e8f0; padding-top:8px; }

    /* Status badge */
    .status-badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:9px; font-weight:700; }
    .status-PAID    { background:#dcfce7; color:#166534; }
    .status-PARTIAL { background:#fef3c7; color:#92400e; }
    .status-OVERDUE { background:#fee2e2; color:#991b1b; }
    .status-VOID    { background:#f1f5f9; color:#94a3b8; }
    .status-UNPAID  { background:#f1f5f9; color:#475569; }

    /* Notes */
    .notes { background:#f8fafc; border-left:3px solid #e2e8f0; padding:8px 12px; border-radius:4px; font-size:9.5px; color:#64748b; margin-bottom:16px; }

    /* Footer */
    .footer { margin-top:24px; padding-top:12px; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:flex-end; }
    .footer-note { font-size:8.5px; color:#94a3b8; max-width:60%; }
    .sig-area { text-align:center; }
    .sig-line { width:120px; border-top:1px solid #1e293b; margin:40px auto 4px; }
    .sig-label { font-size:8px; color:#64748b; }

    /* Parent ref */
    .ref-note { font-size:9px; color:#64748b; background:#fffbeb; padding:6px 10px; border-radius:4px; margin-bottom:12px; border-left:3px solid #f59e0b; }
</style>
</head>
<body>
<div class="page">
    {{-- Header --}}
    <div class="header">
        <div>
            @php $logo = App\Models\Setting::get('navbar_logo_path'); @endphp
            @if($logo)
                <img src="{{ public_path($logo) }}" alt="Logo" style="max-height:40px;max-width:160px;object-fit:contain;">
            @else
                <div class="company-name">TSBL Invoice</div>
                <div class="company-sub">Billing Management System</div>
            @endif
        </div>
        <div class="inv-meta">
            <div class="inv-no">{{ $billingInvoice->invoice_no }}</div>
            <span class="inv-type type-{{ $billingInvoice->invoice_type }}">
                {{ match($billingInvoice->invoice_type){'PROFORMA'=>'PROFORMA','FINAL'=>'FINAL INVOICE','CREDIT_NOTE'=>'CREDIT NOTE','DEBIT_NOTE'=>'DEBIT NOTE','CANCELLATION'=>'CANCELLATION',default=>$billingInvoice->invoice_type} }}
            </span>
            <div style="margin-top:6px;">
                <span class="status-badge status-{{ $billingInvoice->payment_status }}">{{ $billingInvoice->payment_status }}</span>
            </div>
        </div>
    </div>

    {{-- Addresses --}}
    <div class="addresses">
        <div class="addr-block">
            <div class="addr-label">Dari</div>
            <div class="addr-name">{{ App\Models\Setting::get('company_name','TSBL') }}</div>
            <div class="addr-sub">{{ App\Models\Setting::get('company_address','') }}</div>
        </div>
        <div class="addr-block">
            <div class="addr-label">Kepada</div>
            <div class="addr-name">{{ $billingInvoice->partner?->name ?? '-' }}</div>
            <div class="addr-sub">{{ $billingInvoice->partner?->address ?? '' }}</div>
        </div>
    </div>

    {{-- Invoice Details Row --}}
    <div class="inv-details">
        <div class="inv-detail-item">
            <div class="inv-detail-label">Tanggal Invoice</div>
            <div class="inv-detail-value">{{ $billingInvoice->invoice_date?->format('d M Y') }}</div>
        </div>
        <div class="inv-detail-item">
            <div class="inv-detail-label">Jatuh Tempo</div>
            <div class="inv-detail-value">{{ $billingInvoice->due_date?->format('d M Y') ?? '-' }}</div>
        </div>
        @if($billingInvoice->sent_at)
        <div class="inv-detail-item">
            <div class="inv-detail-label">Dikirim</div>
            <div class="inv-detail-value">{{ $billingInvoice->sent_at->format('d M Y') }}</div>
        </div>
        @endif
    </div>

    {{-- Parent Invoice Reference --}}
    @if($billingInvoice->parentInvoice)
    <div class="ref-note">
        ⚠ Merujuk ke invoice: <strong>{{ $billingInvoice->parentInvoice->invoice_no }}</strong>
        @if($billingInvoice->delta_amount)
            — Delta: Rp {{ number_format(abs($billingInvoice->delta_amount),0,',','.') }}
        @endif
    </div>
    @endif

    {{-- Line Items Table --}}
    <table>
        <thead>
            <tr>
                <th style="width:50%">Deskripsi</th>
                <th class="text-center" style="width:12%">Qty</th>
                <th class="text-right" style="width:19%">Harga Satuan</th>
                <th class="text-right" style="width:19%">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($billingInvoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">Rp {{ number_format($item->unit_price,0,',','.') }}</td>
                <td class="text-right" style="font-weight:700;">Rp {{ number_format($item->amount,0,',','.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="totals">
        <table class="totals-table">
            <tr>
                <td>Subtotal</td>
                <td class="text-right">Rp {{ number_format($billingInvoice->subtotal ?? $billingInvoice->grand_total,0,',','.') }}</td>
            </tr>
            @if($billingInvoice->tax_amount)
            <tr>
                <td>Pajak</td>
                <td class="text-right">Rp {{ number_format($billingInvoice->tax_amount,0,',','.') }}</td>
            </tr>
            @endif
            <tr class="grand">
                <td>Grand Total</td>
                <td class="text-right">Rp {{ number_format($billingInvoice->grand_total,0,',','.') }}</td>
            </tr>
        </table>
    </div>

    {{-- Notes --}}
    @if($billingInvoice->notes)
    <div class="notes"><strong>Catatan:</strong> {{ $billingInvoice->notes }}</div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <div class="footer-note">
            Dokumen ini diterbitkan secara elektronik oleh sistem TSBL Invoice.<br>
            Dicetak pada: {{ now()->format('d M Y, H:i') }}
        </div>
        <div class="sig-area">
            <div class="sig-line"></div>
            <div class="sig-label">Authorized Signature</div>
        </div>
    </div>
</div>
</body>
</html>
