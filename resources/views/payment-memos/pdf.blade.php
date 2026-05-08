<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1e293b; padding: 30px 40px; }

        .header { display: table; width: 100%; margin-bottom: 18px; }
        .header-logo { display: table-cell; vertical-align: middle; width: 80px; }
        .header-logo img { max-height: 60px; max-width: 70px; }
        .header-info { display: table-cell; vertical-align: middle; padding-left: 12px; }
        .company-name { font-size: 15px; font-weight: 700; color: #0f1729; }
        .company-sub { font-size: 9px; color: #64748b; margin-top: 2px; }

        .divider { border: none; border-top: 2px solid #0f1729; margin: 10px 0; }
        .divider-thin { border: none; border-top: 1px solid #cbd5e1; margin: 8px 0; }

        .doc-title { text-align: center; font-size: 14px; font-weight: 700; letter-spacing: 1px; color: #0f1729; margin: 14px 0 10px; }

        .meta-row { display: table; width: 100%; margin-bottom: 14px; }
        .meta-left { display: table-cell; width: 50%; }
        .meta-right { display: table-cell; width: 50%; text-align: right; }
        .meta-row dt { font-size: 9px; color: #64748b; margin-bottom: 1px; }
        .meta-row dd { font-size: 11px; font-weight: 600; }

        .to-block { margin-bottom: 14px; }
        .to-block .label { font-size: 9px; color: #64748b; margin-bottom: 4px; }
        .to-block .partner-name { font-size: 12px; font-weight: 700; }
        .to-block .partner-sub  { font-size: 10px; color: #334155; }

        .body-text { font-size: 10.5px; line-height: 1.6; margin-bottom: 12px; }

        table.invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.invoice-table th {
            background: #f1f5f9; padding: 6px 8px;
            font-size: 9px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .4px; color: #475569;
            border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1;
        }
        table.invoice-table td { padding: 6px 8px; border-bottom: 1px solid #f1f5f9; font-size: 10.5px; }
        table.invoice-table tr.total-row td { background: #f8fafc; font-weight: 700; border-top: 1px solid #cbd5e1; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .bank-block { margin: 12px 0; padding: 10px 12px; background: #f8fafc; border-left: 3px solid #3b82f6; }
        .bank-block .bank-label { font-size: 9px; font-weight: 700; color: #64748b; margin-bottom: 4px; text-transform: uppercase; }
        .bank-row { font-size: 10.5px; margin-bottom: 2px; }

        .signature-block { margin-top: 30px; display: table; width: 100%; }
        .sig-right { display: table-cell; width: 200px; text-align: center; float: right; }
        .sig-right .sig-name { margin-top: 50px; border-top: 1px solid #475569; padding-top: 4px; font-weight: 600; font-size: 11px; }
        .sig-right .sig-title { font-size: 9px; color: #64748b; }

        .footer { margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 6px; color: #94a3b8; font-size: 8.5px; text-align: center; }
    </style>
</head>
<body>

{{-- Company header --}}
<div class="header">
    @if(!empty($settings['company_logo']) && file_exists(public_path($settings['company_logo'])))
    <div class="header-logo">
        <img src="{{ public_path($settings['company_logo']) }}" alt="Logo">
    </div>
    @endif
    <div class="header-info">
        <div class="company-name">{{ $settings['company_name'] }}</div>
        @if($settings['company_address'])
            <div class="company-sub">{{ $settings['company_address'] }}</div>
        @endif
        @if($settings['company_phone'] || $settings['company_email'])
            <div class="company-sub">
                @if($settings['company_phone']) Telp: {{ $settings['company_phone'] }} @endif
                @if($settings['company_email'] && $settings['company_phone'])  |  @endif
                @if($settings['company_email']) {{ $settings['company_email'] }} @endif
            </div>
        @endif
    </div>
</div>

<hr class="divider">
<div class="doc-title">MEMO OUTSTANDING PAYMENT</div>
<hr class="divider">

{{-- Meta --}}
<div class="meta-row">
    <div class="meta-left">
        <dt>No. Memo</dt>
        <dd>{{ $paymentMemo->memo_no }}</dd>
    </div>
    <div class="meta-right">
        <dt>Tanggal</dt>
        <dd>{{ $paymentMemo->memo_date->format('d M Y') }}</dd>
    </div>
</div>

<hr class="divider-thin">

{{-- Kepada --}}
<div class="to-block">
    <div class="label">Kepada Yth.</div>
    <div class="partner-name">{{ $paymentMemo->partner->nama_partner }}</div>
    @if($paymentMemo->partner->nama_pt)
        <div class="partner-sub">{{ $paymentMemo->partner->nama_pt }}</div>
    @endif
    @if($paymentMemo->partner->pic_partner)
        <div class="partner-sub">u.p. {{ $paymentMemo->partner->pic_partner }}</div>
    @endif
    <div class="partner-sub" style="margin-top:4px">Di Tempat</div>
</div>

<hr class="divider-thin">

{{-- Opening --}}
<div class="body-text">
    <p style="margin-bottom:8px">Dengan hormat,</p>
    <p style="margin-bottom:8px">Pertama-tama kami mengucapkan terima kasih atas kerja sama dan kepercayaan yang telah terjalin antara <strong>{{ $settings['company_name'] }}</strong> dan <strong>{{ $paymentMemo->partner->nama_partner }}</strong>.</p>
    <p>Bersama memo ini, kami ingin menginformasikan bahwa hingga saat ini masih terdapat outstanding pembayaran atas invoice-invoice berikut:</p>
</div>

{{-- Invoice table --}}
<table class="invoice-table">
    <thead>
        <tr>
            <th class="text-center" style="width:30px">No</th>
            <th>No. Invoice</th>
            <th class="text-center">Tgl Invoice</th>
            <th class="text-center">Jth. Tempo</th>
            <th class="text-right">Sisa Tagihan</th>
        </tr>
    </thead>
    <tbody>
        @foreach($paymentMemo->memoInvoices as $i => $mi)
        <tr>
            <td class="text-center">{{ $i + 1 }}</td>
            <td>{{ $mi->invoice->invoice_no }}</td>
            <td class="text-center">{{ $mi->invoice->invoice_date?->format('d/m/Y') }}</td>
            <td class="text-center">{{ $mi->invoice->due_date?->format('d/m/Y') ?? '—' }}</td>
            <td class="text-right">Rp {{ number_format($mi->sisa_tagihan, 0, ',', '.') }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="4" class="text-right">Total Outstanding</td>
            <td class="text-right">Rp {{ number_format($paymentMemo->totalOutstanding(), 0, ',', '.') }}</td>
        </tr>
    </tbody>
</table>

{{-- Body text --}}
<div class="body-text">
    <p style="margin-bottom:8px">Sehubungan dengan hal tersebut, kami mohon kesediaannya untuk dapat melakukan pembayaran atas invoice-invoice tersebut selambat-lambatnya pada tanggal <strong>{{ $paymentMemo->payment_deadline->format('d M Y') }}</strong>. Pembayaran tepat waktu sangat kami harapkan guna menjaga kelancaran administrasi dan kerja sama yang baik di antara kedua belah pihak.</p>
    <p>Pembayaran dapat dilakukan melalui transfer ke rekening:</p>
</div>

{{-- Bank info --}}
<div class="bank-block">
    <div class="bank-label">Rekening Tujuan Pembayaran</div>
    @if($settings['bank_name'])
        <div class="bank-row"><strong>Bank</strong> : {{ $settings['bank_name'] }}</div>
    @endif
    @if($settings['bank_account_no'])
        <div class="bank-row"><strong>No. Rekening</strong> : {{ $settings['bank_account_no'] }}</div>
    @else
        <div class="bank-row" style="color:#dc2626"><em>[Belum diisi — lengkapi di Settings]</em></div>
    @endif
    @if($settings['bank_account_name'])
        <div class="bank-row"><strong>Atas Nama</strong> : {{ $settings['bank_account_name'] }}</div>
    @endif
</div>

<div class="body-text">
    <p style="margin-bottom:8px">Apabila pembayaran telah dilakukan, kami mohon bantuannya untuk mengirimkan bukti pembayaran kepada kami agar dapat segera kami tindak lanjuti pada sistem kami.</p>
    <p>Demikian memo ini kami sampaikan. Atas perhatian, kerja sama, dan itikad baiknya kami ucapkan terima kasih.</p>
</div>

{{-- Signature --}}
<div style="margin-top:24px; text-align:right;">
    <div style="display:inline-block; width:180px; text-align:center;">
        <div style="font-size:10px; color:#64748b; margin-bottom:4px">Hormat kami,</div>
        <div style="height:50px;"></div>
        <div style="border-top:1px solid #475569; padding-top:4px;">
            <div style="font-weight:700; font-size:11px;">{{ $paymentMemo->creator->full_name ?? '—' }}</div>
            <div style="font-size:9px; color:#64748b;">Finance</div>
            <div style="font-size:9px; color:#64748b;">{{ $settings['company_name'] }}</div>
        </div>
    </div>
</div>

<div class="footer">
    {{ $paymentMemo->memo_no }} &middot; Digenerate pada {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
