<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Kredit</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', sans-serif; font-size: 9px; color: #1a1a1a; }
        .header { width: 100%; border-collapse: collapse; margin-bottom: 14px; border-bottom: 2px solid #1a2235; padding-bottom: 8px; }
        .company-name { font-size: 14px; font-weight: bold; color: #1a2235; }
        .report-title { font-size: 12px; font-weight: bold; color: #1a2235; text-align: right; }
        .report-date  { font-size: 8px; color: #666; text-align: right; }
        .section-title { font-size: 10px; font-weight: bold; color: #1a2235; margin: 14px 0 6px; border-left: 3px solid #6d28d9; padding-left: 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th { background: #1a2235; color: #fff; padding: 4px 5px; text-align: left; font-size: 8px; white-space: nowrap; }
        th.text-right { text-align: right; }
        td { padding: 3px 5px; border-bottom: 1px solid #f0f0f0; font-size: 8px; }
        tr:nth-child(even) td { background: #fafafa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        tfoot td { background: #f0f2f5; font-weight: bold; font-size: 8px; }
        .badge { display: inline-block; padding: 1px 4px; border-radius: 3px; font-size: 7px; font-weight: bold; }
        .badge-normal    { background: #dcfce7; color: #166534; }
        .badge-warning   { background: #fef3c7; color: #92400e; }
        .badge-overlimit { background: #fee2e2; color: #991b1b; }
        .util-bar-outer { height: 4px; border-radius: 3px; background: #e5e7eb; overflow: hidden; min-width: 40px; display: inline-block; width: 50px; }
        .util-bar-inner { height: 100%; border-radius: 3px; }
        .footer { margin-top: 16px; border-top: 1px solid #e5e7eb; padding-top: 5px; font-size: 7px; color: #999; text-align: center; }
        .aging-overdue { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>

<table class="header">
    <tr>
        <td style="vertical-align:top;">
            <div class="company-name">{{ $settings['company_name'] ?? 'TSBL' }}</div>
            <div style="font-size:7px;color:#666;margin-top:2px;">{{ $settings['company_address'] ?? '' }}</div>
        </td>
        <td style="vertical-align:top;text-align:right;">
            <div class="report-title">LAPORAN KREDIT PARTNER</div>
            <div class="report-date">Dicetak: {{ now()->format('d/m/Y H:i') }}</div>
        </td>
    </tr>
</table>

{{-- Credit Summary --}}
<div class="section-title">Credit Summary per Partner</div>
<table>
    <thead>
        <tr>
            <th>Partner</th>
            <th>Credit Class</th>
            <th class="text-right">Limit</th>
            <th class="text-right">Used</th>
            <th class="text-right">Available</th>
            <th class="text-right">Util %</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($creditSummary as $row)
        @php
            $util  = $row->utilization_percent;
            $color = $util > 100 ? '#dc2626' : ($util >= 80 ? '#d97706' : '#059669');
        @endphp
        <tr>
            <td style="font-weight:600">{{ $row->partner->nama_partner }}</td>
            <td>{{ $row->credit_class_name ?? '—' }}</td>
            <td class="text-right">{{ number_format($row->limit, 0, ',', '.') }}</td>
            <td class="text-right" style="color:#dc2626">{{ number_format($row->used, 0, ',', '.') }}</td>
            <td class="text-right" style="color:{{ $row->available >= 0 ? '#059669' : '#dc2626' }}">{{ number_format($row->available, 0, ',', '.') }}</td>
            <td class="text-right" style="color:{{ $color }};font-weight:bold">{{ number_format($util, 1) }}%</td>
            <td>
                @if($row->status === 'OVER_LIMIT')
                    <span class="badge badge-overlimit">OVER LIMIT</span>
                @elseif($row->status === 'WARNING')
                    <span class="badge badge-warning">WARNING</span>
                @else
                    <span class="badge badge-normal">NORMAL</span>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;color:#999;padding:10px;">Tidak ada data.</td></tr>
        @endforelse
    </tbody>
    @if($creditSummary->count() > 0)
    <tfoot>
        <tr>
            <td colspan="2">TOTAL</td>
            <td class="text-right">{{ number_format($creditSummary->sum('limit'), 0, ',', '.') }}</td>
            <td class="text-right" style="color:#dc2626">{{ number_format($creditSummary->sum('used'), 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($creditSummary->sum('available'), 0, ',', '.') }}</td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
    @endif
</table>

{{-- Credit Aging --}}
@php
    $agingBuckets = $creditAging['buckets'];
    $agingRows    = $creditAging['rows'];
    $agingTotals  = $creditAging['totals'];
    $b1 = $agingBuckets['b1']; $b2 = $agingBuckets['b2'];
    $b3 = $agingBuckets['b3']; $b4 = $agingBuckets['b4'];
@endphp
<div class="section-title">
    Credit Aging &nbsp;
    <span style="font-size:7px;font-weight:normal;color:#666;">
        Bucket: Current | 1–{{ $b1 }} | {{ $b1+1 }}–{{ $b2 }} | {{ $b2+1 }}–{{ $b3 }} | {{ $b3+1 }}–{{ $b4 }} | >{{ $b4 }} hari
    </span>
</div>
<table>
    <thead>
        <tr>
            <th>Partner</th>
            <th>Class</th>
            <th class="text-right">Current</th>
            <th class="text-right">1–{{ $b1 }}</th>
            <th class="text-right">{{ $b1+1 }}–{{ $b2 }}</th>
            <th class="text-right">{{ $b2+1 }}–{{ $b3 }}</th>
            <th class="text-right">{{ $b3+1 }}–{{ $b4 }}</th>
            <th class="text-right">>{{ $b4 }}</th>
            <th class="text-right">Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($agingRows as $row)
        <tr>
            <td style="font-weight:600">{{ $row->partner->nama_partner }}</td>
            <td>{{ $row->partner->creditClass->name ?? '—' }}</td>
            @foreach(['current','b1','b2','b3','b4','b5'] as $bk)
            <td class="text-right {{ $bk !== 'current' && $row->buckets[$bk] > 0 ? 'aging-overdue' : '' }}">
                {{ $row->buckets[$bk] > 0 ? number_format($row->buckets[$bk], 0, ',', '.') : '—' }}
            </td>
            @endforeach
            <td class="text-right" style="font-weight:bold">{{ number_format($row->total, 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center;color:#999;padding:10px;">Tidak ada data aging.</td></tr>
        @endforelse
    </tbody>
    @if(count($agingRows) > 0)
    <tfoot>
        <tr>
            <td colspan="2">GRAND TOTAL</td>
            @foreach(['current','b1','b2','b3','b4','b5'] as $bk)
            <td class="text-right {{ $bk !== 'current' && $agingTotals[$bk] > 0 ? 'aging-overdue' : '' }}">
                {{ $agingTotals[$bk] > 0 ? number_format($agingTotals[$bk], 0, ',', '.') : '—' }}
            </td>
            @endforeach
            <td class="text-right">{{ number_format(array_sum($agingTotals), 0, ',', '.') }}</td>
        </tr>
    </tfoot>
    @endif
</table>

<div class="footer">
    Laporan Kredit &mdash; {{ $settings['company_name'] ?? 'TSBL' }} &mdash; Dicetak {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
