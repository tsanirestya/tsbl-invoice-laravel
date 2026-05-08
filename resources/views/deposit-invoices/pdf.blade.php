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
            opacity: 0.05;
            transform: rotate(-30deg);
            z-index: -1;
        }
        .watermark-draft     { color: #6c757d; }
        .watermark-sent      { color: #0dcaf0; }
        .watermark-paid      { color: #198754; }
        .watermark-cancelled { color: #dc3545; }

        /* Header */
        .header { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .header td { padding: 0; vertical-align: middle; }
        .header td.col-right { text-align: right; width: 45%; }
        .company-name { font-size: 16pt; font-weight: bold; color: #0d3b6e; }
        .company-info { font-size: 8.5pt; color: #555; margin-top: 4px; line-height: 1.5; }

        .invoice-title { font-size: 18pt; font-weight: bold; color: #0d6efd; letter-spacing: 2px; }
        .invoice-subtitle { font-size: 10pt; color: #555; letter-spacing: 1px; margin-top: 2px; }
        .invoice-no    { font-size: 11pt; font-weight: bold; color: #333; margin-top: 4px; }
        .invoice-meta  { font-size: 8.5pt; color: #555; margin-top: 4px; line-height: 1.7; }

        hr { border: none; border-top: 1.5px solid #0d3b6e; margin: 12px 0; }
        hr.accent { border-top: 1.5px solid #0d6efd; }

        /* Bill to */
        .bill-section { display: flex; justify-content: space-between; margin-bottom: 16px; }
        .bill-to h4   { font-size: 8.5pt; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .bill-to p    { font-size: 10pt; }
        .bill-to .partner-name { font-weight: bold; font-size: 11pt; }

        /* Purpose box */
        .purpose-box {
            background: #eef4ff;
            border: 1px solid #b8d0f9;
            border-left: 4px solid #0d6efd;
            border-radius: 4px;
            padding: 10px 14px;
            margin-bottom: 16px;
        }
        .purpose-box h4 { font-size: 8.5pt; color: #0d6efd; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        .purpose-box p  { font-size: 10pt; color: #1a1a1a; }

        /* Amount box */
        .amount-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .amount-table th { background: #0d3b6e; color: #fff; padding: 7px 12px; font-size: 9pt; font-weight: normal; text-align: left; }
        .amount-table td { padding: 12px 12px; font-size: 10pt; border: 1px solid #e5e7eb; }
        .amount-table .total-row td { background: #f4f7fb; font-weight: bold; font-size: 12pt; color: #0d3b6e; }

        /* Terbilang */
        .terbilang-box { background: #f4f7fb; border-left: 3px solid #0d3b6e; padding: 7px 10px; font-size: 9pt; color: #333; margin-bottom: 14px; }
        .terbilang-box span { font-style: italic; }

        /* Bank */
        .bank-box { border: 1px solid #ddd; border-radius: 4px; padding: 8px 12px; font-size: 9pt; margin-bottom: 14px; }
        .bank-box h4 { font-size: 8.5pt; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }

        /* Signature */
        .signature-section { margin-top: 28px; display: flex; justify-content: flex-end; }
        .signature-block { text-align: center; font-size: 9pt; }
        .signature-block .sig-line { width: 160px; border-top: 1px solid #333; margin: 36px auto 4px; }

        /* Notes */
        .notes-box { font-size: 8.5pt; color: #666; margin-bottom: 14px; }
        .notes-box h4 { font-size: 8pt; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 3px; }

        /* Status badge */
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .status-draft     { background: #e9ecef; color: #555; }
        .status-sent      { background: #cff4fc; color: #0a5c6b; }
        .status-paid      { background: #d1e7dd; color: #0a3622; }
        .status-cancelled { background: #f8d7da; color: #58151c; }

        /* Footer */
        .footer { margin-top: 24px; border-top: 1px solid #ddd; padding-top: 8px; font-size: 8pt; color: #999; display: flex; justify-content: space-between; }
    </style>
</head>
<body>
<div class="page">

    {{-- Watermark --}}
    @php $wm = strtolower($depositInvoice->status); @endphp
    <div class="watermark watermark-{{ $wm }}">{{ $depositInvoice->status }}</div>

    {{-- Header --}}
    @php
        $logoPath = $settings['logo_path'] ?? null;
        $logoSrc  = null;
        if ($logoPath) {
            $logoKey = ltrim(preg_replace('#^storage/#', '', $logoPath), '/');
            $logoAbs = \Storage::disk('public')->exists($logoKey)
                ? \Storage::disk('public')->path($logoKey)
                : null;
            if ($logoAbs && file_exists($logoAbs)) {
                $mime    = mime_content_type($logoAbs) ?: 'image/png';
                $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoAbs));
            }
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
                <div class="invoice-title">INVOICE DEPOSIT</div>
                <div class="invoice-subtitle">Permintaan Setoran Deposit</div>
                <div class="invoice-no">{{ $depositInvoice->invoice_no }}</div>
                <div class="invoice-meta">
                    Tanggal: {{ $depositInvoice->invoice_date?->format('d/m/Y') }}<br>
                    Jatuh Tempo: {{ $depositInvoice->due_date?->format('d/m/Y') ?? '-' }}<br>
                    Status: <span class="status-badge status-{{ $wm }}">{{ $depositInvoice->status }}</span>
                </div>
            </td>
        </tr>
    </table>

    <hr class="accent">

    {{-- Bill To --}}
    <div class="bill-section">
        <div class="bill-to">
            <h4>Kepada Yth.</h4>
            <p class="partner-name">{{ $depositInvoice->partner?->nama_partner ?? '-' }}</p>
            @if($depositInvoice->partner?->nama_pt)
                <p>{{ $depositInvoice->partner->nama_pt }}</p>
            @endif
            @if($depositInvoice->partner?->address)
                <p>{{ $depositInvoice->partner->address }}</p>
            @endif
            @if($depositInvoice->partner?->npwp)
                <p>NPWP: {{ $depositInvoice->partner->npwp }}</p>
            @endif
            @if($depositInvoice->partner?->pic_partner)
                <p>u/p: {{ $depositInvoice->partner->pic_partner }}</p>
            @endif
        </div>
    </div>

    {{-- Purpose --}}
    <div class="purpose-box">
        <h4>Tujuan & Keterangan</h4>
        <p>
            Dengan hormat, kami mengajukan permohonan setoran deposit kepada <strong>{{ $depositInvoice->partner?->nama_partner ?? 'Partner' }}</strong>
            sebagai jaminan kelancaran kerja sama. Deposit ini akan digunakan sebagai saldo yang dapat digunakan untuk pembayaran invoice layanan.
            @if($depositInvoice->notes)<br><br>{{ $depositInvoice->notes }}@endif
        </p>
    </div>

    {{-- Amount --}}
    <table class="amount-table">
        <thead>
            <tr>
                <th>Keterangan</th>
                <th style="text-align:right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Setoran Deposit — {{ $depositInvoice->partner?->nama_partner }}</td>
                <td style="text-align:right">Rp {{ number_format($depositInvoice->amount, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td>TOTAL YANG HARUS DIBAYAR</td>
                <td style="text-align:right">Rp {{ number_format($depositInvoice->amount, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Terbilang --}}
    <div class="terbilang-box">
        Terbilang: <span>{{ $depositInvoice->terbilang }}</span>
    </div>

    {{-- Bank --}}
    @if(($settings['bank_name'] ?? null) || ($settings['bank_account_no'] ?? null))
    <div class="bank-box">
        <h4>Informasi Pembayaran / Transfer</h4>
        @if($settings['bank_name'] ?? null)Bank: <strong>{{ $settings['bank_name'] }}</strong> &nbsp;@endif
        @if($settings['bank_account_no'] ?? null)No. Rekening: <strong>{{ $settings['bank_account_no'] }}</strong> &nbsp;@endif
        @if($settings['bank_account_name'] ?? null)a/n <strong>{{ $settings['bank_account_name'] }}</strong>@endif
        <p style="margin-top:4px;color:#555;font-size:8.5pt">
            Mohon cantumkan nomor invoice <strong>{{ $depositInvoice->invoice_no }}</strong> pada keterangan transfer.
        </p>
    </div>
    @endif

    {{-- Notes --}}
    @if($settings['invoice_notes'] ?? null)
    <div class="notes-box">
        <h4>Catatan</h4>
        <p>{{ $settings['invoice_notes'] }}</p>
    </div>
    @endif
    @if($settings['terms_conditions'] ?? null)
    <div class="notes-box">
        <h4>Syarat & Ketentuan</h4>
        <p>{{ $settings['terms_conditions'] }}</p>
    </div>
    @endif

    {{-- Signature --}}
    <div class="signature-section">
        <div class="signature-block">
            <p>{{ $settings['company_name'] ?? 'TSBL' }}</p>
            @if($depositInvoice->creator?->signature_image && Storage::disk('public')->exists($depositInvoice->creator->signature_image))
                <img src="{{ Storage::disk('public')->path($depositInvoice->creator->signature_image) }}"
                     style="height:50px;margin-top:4px" alt="ttd">
            @endif
            <div class="sig-line"></div>
            <p>{{ $depositInvoice->creator?->full_name ?? '' }}</p>
            <p style="color:#888">{{ $depositInvoice->creator?->position_name ?? '' }}</p>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <span>{{ $settings['company_name'] ?? 'TSBL' }} — {{ $depositInvoice->invoice_no }}</span>
        <span>Dicetak: {{ now()->format('d/m/Y H:i') }}</span>
    </div>
</div>
</body>
</html>
