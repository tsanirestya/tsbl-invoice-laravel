<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Invoice</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #1a1a1a; }
        .header { width: 100%; border-collapse: collapse; margin-bottom: 16px; border-bottom: 2px solid #1a2235; padding-bottom: 10px; }
        .company-name { font-size: 16px; font-weight: bold; color: #1a2235; }
        .report-title { font-size: 13px; font-weight: bold; color: #1a2235; text-align: right; }
        .report-date { font-size: 9px; color: #666; text-align: right; }
        .filters { background: #f8f9fa; border: 1px solid #e5e7eb; border-radius: 4px; padding: 6px 10px; margin-bottom: 12px; font-size: 9px; color: #555; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .summary-box { border: 1px solid #e5e7eb; border-radius: 4px; padding: 8px; text-align: center; width: 25%; }
        .summary-box .label { font-size: 8px; color: #888; text-transform: uppercase; letter-spacing: .5px; }
        .summary-box .value { font-size: 11px; font-weight: bold; margin-top: 2px; }
        .summary-box.paid .value { color: #198754; }
        .summary-box.outstanding .value { color: #fd7e14; }
        .summary-box.overdue .value { color: #dc3545; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th { background: #1a2235; color: #fff; padding: 5px 6px; text-align: left; font-size: 9px; }
        td { padding: 4px 6px; border-bottom: 1px solid #f0f0f0; font-size: 9px; }
        tr:nth-child(even) td { background: #fafafa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 8px; font-weight: bold; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-unpaid { background: #f3f4f6; color: #374151; }
        .badge-partial { background: #ffedd5; color: #9a3412; }
        .badge-overdue { background: #fee2e2; color: #991b1b; }
        .tfoot td { background: #f0f2f5; font-weight: bold; }
        .section-title { font-size: 11px; font-weight: bold; color: #1a2235; margin: 14px 0 6px; border-left: 3px solid #1a2235; padding-left: 8px; }
        .footer { margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 6px; font-size: 8px; color: #999; text-align: center; width: 100%; }
    </style>
</head>
<body>

<table class="header">
    <tr>
        <td style="vertical-align:top;">
            <div class="company-name">{{ $settings['company_name'] ?? 'TSBL' }}</div>
            <div style="font-size:8px;color:#666;margin-top:2px;">{{ $settings['company_address'] ?? '' }}</div>
            <div style="font-size:8px;color:#666;">{{ $settings['company_phone'] ?? '' }} | {{ $settings['company_email'] ?? '' }}</div>
        </td>
        <td style="vertical-align:top;text-align:right;">
            <div class="report-title">LAPORAN KEUANGAN INVOICE</div>
            <div class="report-date">Dicetak: {{ now()->format('d/m/Y H:i') }}</div>
        </td>
    </tr>
</table>

@if(count($filters))
<div class="filters">
    <strong>Filter:</strong> {{ implode(' | ', $filters) }}
</div>
@endif

<table class="summary">
    <tr>
        <td class="summary-box">
            <div class="label">Total Invoice</div>
            <div class="value">{{ number_format($summary['total_invoice']) }}</div>
        </td>
        <td class="summary-box paid">
            <div class="label">Revenue (PAID)</div>
            <div class="value">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</div>
        </td>
        <td class="summary-box outstanding">
            <div class="label">Outstanding</div>
            <div class="value">Rp {{ number_format($summary['total_outstanding'], 0, ',', '.') }}</div>
        </td>
        <td class="summary-box overdue">
            <div class="label">Overdue</div>
            <div class="value">Rp {{ number_format($summary['total_overdue'], 0, ',', '.') }}</div>
        </td>
    </tr>
</table>

<div class="section-title">Detail Invoice</div>

<table>
    <thead>
        <tr>
            <th>No Invoice</th>
            <th>Partner</th>
            <th>Tamu</th>
            <th>Tgl Invoice</th>
            <th>Jatuh Tempo</th>
            <th class="text-right">Grand Total</th>
            <th class="text-right">Dibayar</th>
            <th class="text-right">Sisa</th>
            <th class="text-center">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($invoices as $inv)
        <tr>
            <td>{{ $inv->invoice_no }}</td>
            <td>{{ $inv->partner->nama_partner ?? '-' }}</td>
            <td>{{ $inv->guest_name ?? '-' }}</td>
            <td>{{ $inv->invoice_date?->format('d/m/Y') }}</td>
            <td>{{ $inv->due_date?->format('d/m/Y') ?? '-' }}</td>
            <td class="text-right">{{ number_format($inv->grand_total, 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($inv->totalPaid(), 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format(max(0, $inv->grand_total - $inv->totalPaid()), 0, ',', '.') }}</td>
            <td class="text-center">
                <span class="badge badge-{{ strtolower($inv->payment_status) }}">{{ $inv->payment_status }}</span>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="9" class="text-center" style="padding:12px;color:#999;">Tidak ada data.</td>
        </tr>
        @endforelse
    </tbody>
    @if($invoices->count() > 0)
    <tfoot>
        <tr class="tfoot">
            <td colspan="5">TOTAL ({{ $invoices->count() }} invoice)</td>
            <td class="text-right">{{ number_format($invoices->sum('grand_total'), 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($invoices->sum(fn($i) => $i->totalPaid()), 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($invoices->sum(fn($i) => max(0, $i->grand_total - $i->totalPaid())), 0, ',', '.') }}</td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>

<div class="footer">
    {{ $settings['company_name'] ?? 'TSBL' }} — Laporan digenerate otomatis oleh sistem pada {{ now()->format('d/m/Y H:i:s') }}
</div>

</body>
</html>
