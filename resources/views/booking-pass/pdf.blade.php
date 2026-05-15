<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11pt; color: #1e293b; padding: 20px; }

        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; border-bottom: 2px solid #0f1729; padding-bottom: 12px; }
        .brand { font-size: 18pt; font-weight: bold; color: #0f1729; }
        .brand-sub { font-size: 8pt; color: #64748b; letter-spacing: 1px; text-transform: uppercase; }
        .title-block { text-align: right; }
        .title-block h1 { font-size: 16pt; color: #0f1729; font-weight: bold; }
        .res-no { font-size: 13pt; color: #3b82f6; font-weight: bold; }

        .section { margin-bottom: 14px; }
        .section-title { font-size: 8pt; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-bottom: 4px; }

        .info-grid { display: flex; gap: 0; }
        .info-col { flex: 1; }
        .info-item { margin-bottom: 8px; }
        .info-label { font-size: 8pt; color: #64748b; text-transform: uppercase; letter-spacing: .5px; }
        .info-value { font-size: 11pt; font-weight: bold; color: #1e293b; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        thead tr { background: #0f1729; color: #fff; }
        thead th { padding: 6px 8px; font-size: 9pt; text-align: left; }
        tbody tr { border-bottom: 1px solid #e2e8f0; }
        tbody td { padding: 5px 8px; font-size: 10pt; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tfoot tr { background: #f1f5f9; font-weight: bold; }
        tfoot td { padding: 6px 8px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .custom-fields { background: #f8fafc; padding: 10px; border-radius: 4px; margin-bottom: 12px; }
        .custom-field { display: flex; margin-bottom: 4px; }
        .custom-label { width: 140px; color: #64748b; font-size: 9pt; }
        .custom-value { font-size: 9pt; font-weight: bold; }

        .footer { border-top: 1px solid #e2e8f0; padding-top: 10px; margin-top: 16px; font-size: 8pt; color: #64748b; }
        .qr-block { text-align: center; margin-top: 12px; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 9pt; }
        .badge-confirmed { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div>
        @if($companyLogo)
            <img src="{{ public_path('storage/' . ltrim($companyLogo, '/')) }}" height="40" alt="logo">
        @endif
        <div class="brand">{{ $companyName }}</div>
        <div class="brand-sub">Official Booking Pass</div>
    </div>
    <div class="title-block">
        <h1>BOOKING PASS</h1>
        <div class="res-no">{{ $reservation->reservation_no }}</div>
        <span class="badge badge-confirmed">CONFIRMED</span>
    </div>
</div>

{{-- Guest & Visit Info --}}
<div class="section">
    <div class="info-grid">
        <div class="info-col">
            <div class="info-item">
                <div class="info-label">Nama Tamu</div>
                <div class="info-value">{{ $reservation->guest_name }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Tanggal Kunjungan</div>
                <div class="info-value">{{ $reservation->visit_date->format('d F Y') }}</div>
            </div>
        </div>
        <div class="info-col">
            <div class="info-item">
                <div class="info-label">Partner / Agen</div>
                <div class="info-value">{{ $partner?->nama_partner ?? '—' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Negara</div>
                <div class="info-value">{{ $reservation->guest_country ?? '—' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Jumlah Tamu</div>
                <div class="info-value">
                    {{ $reservation->pax_adults ?? 1 }} Dewasa
                    @if(($reservation->pax_kids ?? 0) > 0)
                        · {{ $reservation->pax_kids }} Anak
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Products --}}
<div class="section">
    <div class="section-title">Produk / Tiket</div>
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Produk</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Harga/Pax</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->product_name }}</td>
                <td class="text-center">{{ $item->qty }}</td>
                <td class="text-right">Rp {{ number_format($item->price_per_pax, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right">Total</td>
                <td class="text-right">Rp {{ number_format($reservation->total_amount, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</div>

{{-- Custom Fields --}}
@if(!empty($customData))
<div class="section">
    <div class="section-title">Informasi Tambahan</div>
    <div class="custom-fields">
        @foreach($customData as $k => $v)
        <div class="custom-field">
            <span class="custom-label">{{ ucwords(str_replace('_', ' ', $k)) }}</span>
            <span class="custom-value">{{ $v }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Footer --}}
<div class="footer">
    <p>* Harap tunjukkan booking pass ini saat check-in di lokasi {{ $companyName }}.</p>
    <p>* Booking pass ini hanya berlaku untuk tanggal kunjungan yang tertera.</p>
    <p>Diterbitkan: {{ $reservation->created_at->format('d/m/Y H:i') }} | ID: {{ $reservation->reservation_no }}</p>
</div>

</body>
</html>
