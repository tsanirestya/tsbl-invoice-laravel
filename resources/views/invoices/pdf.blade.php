<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #1a1a1a; }

        .page { padding: 30px 36px; }

        /* Watermark */
        .watermark {
            position: fixed;
            top: 35%;
            left: 10%;
            width: 80%;
            text-align: center;
            font-size: 80pt;
            font-weight: bold;
            opacity: 0.06;
            transform: rotate(-30deg);
            z-index: -1;
        }
        .watermark-paid    { color: #198754; }
        .watermark-unpaid  { color: #6c757d; }
        .watermark-partial { color: #fd7e14; }
        .watermark-overdue { color: #dc3545; }

        /* Header */
        .header { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .header td { padding: 0; vertical-align: middle; }
        .header td.col-right { text-align: right; width: 45%; }
        .company-name { font-size: 16pt; font-weight: bold; color: #0d3b6e; }
        .company-info { font-size: 8.5pt; color: #555; margin-top: 4px; line-height: 1.5; }

        .invoice-title { font-size: 20pt; font-weight: bold; color: #0d3b6e; letter-spacing: 2px; }
        .invoice-no    { font-size: 11pt; font-weight: bold; color: #333; }
        .invoice-meta  { font-size: 8.5pt; color: #555; margin-top: 4px; line-height: 1.7; }

        hr { border: none; border-top: 1.5px solid #0d3b6e; margin: 12px 0; }
        hr.light { border-top: 1px solid #ddd; }

        /* Bill to */
        .bill-section { display: flex; justify-content: space-between; margin-bottom: 16px; }
        .bill-to h4   { font-size: 8.5pt; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .bill-to p    { font-size: 10pt; }
        .bill-to .partner-name { font-weight: bold; font-size: 11pt; }

        /* Items table */
        table { width: 100%; border-collapse: collapse; }
        .items-table thead tr { background: #0d3b6e; color: #fff; }
        .items-table thead th { padding: 7px 8px; font-size: 9pt; font-weight: normal; }
        .items-table tbody tr:nth-child(even) { background: #f4f7fb; }
        .items-table tbody td { padding: 6px 8px; font-size: 9.5pt; border-bottom: 1px solid #e5e7eb; }
        .items-table tfoot td { padding: 6px 8px; font-size: 10pt; }

        .text-right  { text-align: right; }
        .text-center { text-align: center; }

        /* Totals */
        .totals-table { margin-top: 8px; margin-left: auto; width: 55%; border-collapse: collapse; }
        .totals-table td { padding: 3px 8px; font-size: 10pt; }
        .totals-table .grand td { border-top: 1.5px solid #0d3b6e; padding-top: 6px; font-weight: bold; font-size: 11pt; color: #0d3b6e; }

        /* Terbilang */
        .terbilang-box { margin-top: 10px; background: #f4f7fb; border-left: 3px solid #0d3b6e; padding: 7px 10px; font-size: 9pt; color: #333; }
        .terbilang-box span { font-style: italic; }

        /* Bank */
        .bank-box { margin-top: 14px; border: 1px solid #ddd; border-radius: 4px; padding: 8px 12px; font-size: 9pt; }
        .bank-box h4 { font-size: 8.5pt; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }

        /* Signature */
        .signature-section { margin-top: 28px; display: flex; justify-content: flex-end; }
        .signature-block { text-align: center; font-size: 9pt; }
        .signature-block .sig-line { width: 160px; border-top: 1px solid #333; margin: 36px auto 4px; }

        /* Notes */
        .notes-box { margin-top: 14px; font-size: 8.5pt; color: #666; }
        .notes-box h4 { font-size: 8pt; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 3px; }

        /* Footer */
        .footer { margin-top: 24px; border-top: 1px solid #ddd; padding-top: 8px; font-size: 8pt; color: #999; display: flex; justify-content: space-between; }
    </style>
</head>
<body>
<div class="page">

    {{-- Watermark --}}
    @php $wm = strtolower($invoice->payment_status); @endphp
    <div class="watermark watermark-{{ $wm }}">{{ $invoice->payment_status }}</div>

    {{-- Header --}}
    @php
        $logoPath = $settings['logo_path'] ?? null;
        $logoAbs  = $logoPath ? public_path($logoPath) : null;
        $logoSrc  = null;
        if ($logoAbs && file_exists($logoAbs)) {
            $mime = mime_content_type($logoAbs) ?: 'image/png';
            $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoAbs));
        }
    @endphp
    <table class="header">
        <tr>
            <td style="width:55%;">
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" style="max-height:70px;max-width:220px;width:auto;height:auto;display:block;" alt="Logo">
                @else
                    <div class="company-name">{{ $settings['company_name'] ?? 'TSBL' }}</div>
                @endif
                <div class="company-info">
                    @if($settings['company_address'] ?? null){{ $settings['company_address'] }}<br>@endif
                    @if($settings['company_phone'] ?? null)Telp: {{ $settings['company_phone'] }}<br>@endif
                    @if($settings['company_email'] ?? null)Email: {{ $settings['company_email'] }}<br>@endif
                    @if($settings['company_npwp'] ?? null)NPWP: {{ $settings['company_npwp'] }}@endif
                </div>
            </td>
            <td class="col-right">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-no">{{ $invoice->invoice_no }}</div>
                <div class="invoice-meta">
                    Tanggal: {{ $invoice->invoice_date?->format('d/m/Y') }}<br>
                    Jatuh Tempo: {{ $invoice->due_date?->format('d/m/Y') ?? '-' }}
                </div>
            </td>
        </tr>
    </table>

    <hr>

    {{-- Bill To --}}
    <div class="bill-section">
        <div class="bill-to">
            <h4>Tagihan Kepada</h4>
            <p class="partner-name">{{ $invoice->partner?->nama_partner ?? '-' }}</p>
            @if($invoice->partner?->nama_pt)
                <p>{{ $invoice->partner->nama_pt }}</p>
            @endif
            @if($invoice->partner?->address)
                <p>{{ $invoice->partner->address }}</p>
            @endif
            @if($invoice->partner?->npwp)
                <p>NPWP: {{ $invoice->partner->npwp }}</p>
            @endif
        </div>
        <div>
            @if($invoice->guest_name)
            <div class="bill-to">
                <h4>Nama Tamu</h4>
                <p>{{ $invoice->guest_name }}</p>
            </div>
            @endif
            @if($invoice->booking_pass_no)
            <div class="bill-to" style="margin-top:8px">
                <h4>Booking Pass No</h4>
                <p>{{ $invoice->booking_pass_no }}</p>
            </div>
            @endif
            @if($invoice->visit_date)
            <div class="bill-to" style="margin-top:8px">
                <h4>Tanggal Kunjungan</h4>
                <p>{{ $invoice->visit_date->format('d/m/Y') }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Items --}}
    <table class="items-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Deskripsi Layanan</th>
                <th class="text-center">Pax</th>
                <th class="text-right">Harga / Pax</th>
                <th class="text-right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->product_name }}</td>
                <td class="text-center">{{ number_format($item->pax) }}</td>
                <td class="text-right">{{ number_format($item->price_per_pax, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->amount, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <table class="totals-table">
        <tr>
            <td>Subtotal</td>
            <td style="text-align:right;">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
        </tr>
        <tr class="grand">
            <td>Grand Total</td>
            <td style="text-align:right;">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
        </tr>
        @if($invoice->deposit > 0)
        <tr>
            <td style="color:#0d6efd;font-size:9pt">Dibayar via Deposit</td>
            <td style="text-align:right;color:#0d6efd;font-size:9pt">(Rp {{ number_format($invoice->deposit, 0, ',', '.') }})</td>
        </tr>
        <tr>
            <td style="font-size:9pt">Sisa Tagihan</td>
            <td style="text-align:right;font-size:9pt">Rp {{ number_format(max(0, $invoice->grand_total - $invoice->deposit), 0, ',', '.') }}</td>
        </tr>
        @endif
    </table>

    {{-- Terbilang --}}
    <div class="terbilang-box">
        Terbilang: <span>{{ $invoice->terbilang }}</span>
    </div>

    {{-- Bank --}}
    @if(($settings['bank_name'] ?? null) || ($settings['bank_account_no'] ?? null))
    <div class="bank-box">
        <h4>Informasi Pembayaran</h4>
        @if($settings['bank_name'] ?? null)Bank: <strong>{{ $settings['bank_name'] }}</strong> &nbsp;@endif
        @if($settings['bank_account_no'] ?? null)No. Rekening: <strong>{{ $settings['bank_account_no'] }}</strong> &nbsp;@endif
        @if($settings['bank_account_name'] ?? null)a/n <strong>{{ $settings['bank_account_name'] }}</strong>@endif
    </div>
    @endif

    {{-- Notes --}}
    @if(($invoice->notes ?? null) || ($settings['invoice_notes'] ?? null) || ($settings['terms_conditions'] ?? null))
    <div class="notes-box">
        @if($invoice->notes)
        <h4>Catatan</h4>
        <p>{{ $invoice->notes }}</p>
        @endif
        @if($settings['invoice_notes'] ?? null)
        <p style="margin-top:4px">{{ $settings['invoice_notes'] }}</p>
        @endif
        @if($settings['terms_conditions'] ?? null)
        <p style="margin-top:4px;font-size:8pt">{{ $settings['terms_conditions'] }}</p>
        @endif
    </div>
    @endif

    {{-- Signature --}}
    <div class="signature-section">
        <div class="signature-block">
            <p>{{ $settings['company_name'] ?? 'TSBL' }}</p>
            @if($invoice->creator?->signature_image && Storage::disk('public')->exists($invoice->creator->signature_image))
                <img src="{{ Storage::disk('public')->path($invoice->creator->signature_image) }}"
                     style="height:50px;margin-top:4px" alt="ttd">
            @endif
            <div class="sig-line"></div>
            <p>{{ $invoice->creator?->full_name ?? '' }}</p>
            <p style="color:#888">{{ $invoice->creator?->position_name ?? '' }}</p>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <span>{{ $settings['company_name'] ?? 'TSBL' }} — {{ $invoice->invoice_no }}</span>
        <span>Dicetak: {{ now()->format('d/m/Y H:i') }}</span>
    </div>
</div>
</body>
</html>
