<?php

namespace App\Services;

use App\Models\BookingPassTemplate;
use App\Models\Reservation;
use App\Models\Setting;
use App\Services\BarcodeRenderer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class BookingPassService
{
    public function generate(Reservation $reservation): string
    {
        $template = $this->getTemplate($reservation);
        $path     = 'booking-passes/' . $reservation->reservation_no . '.pdf';

        if ($template && !empty($template->field_mapping)) {
            // Custom template with visual field_mapping
            $fieldValues = $this->renderFieldValues($reservation, $template->qr_type ?? 'qr');
            $pdf = Pdf::loadView('booking-pass.pdf-custom', [
                'template'    => $template,
                'fieldValues' => $fieldValues,
                'fieldMap'    => $template->field_mapping,
            ])->setPaper('a4', 'portrait');
        } else {
            // Default hardcoded layout
            $data = $this->buildDefaultData($reservation, $template);
            $pdf  = Pdf::loadView('booking-pass.pdf', $data)->setPaper('a4', 'portrait');
        }

        Storage::disk('public')->put($path, $pdf->output());
        $reservation->update(['booking_pass_file' => $path]);

        return $path;
    }

    public function getTemplate(Reservation $reservation): ?BookingPassTemplate
    {
        if ($reservation->partner_id) {
            $custom = BookingPassTemplate::where('partner_id', $reservation->partner_id)
                ->where('is_active', true)
                ->first();
            if ($custom) return $custom;
        }

        return BookingPassTemplate::whereNull('partner_id')
            ->where('is_active', true)
            ->first();
    }

    /** Build all renderable values from a reservation, keyed by field key */
    public function renderFieldValues(Reservation $reservation, string $qrType = 'qr'): array
    {
        $bpData  = $reservation->booking_pass_data ?? [];
        $items   = $reservation->items;
        $partner = $reservation->partner;

        $values = [
            'reservation_no'   => e($reservation->reservation_no),
            'guest_name'       => e($reservation->guest_name),
            'guest_country'    => e($reservation->guest_country ?? '—'),
            'visit_date'       => $reservation->visit_date?->format('d F Y') ?? '—',
            'partner_name'     => e($partner?->nama_partner ?? $reservation->partner_name_input ?? '—'),
            'product_name'     => e($items->first()?->product_name ?? '—'),
            'payment_method'   => e($reservation->payment_method ?? '—'),
            'payment_channel'  => e($reservation->payment_channel ?? '—'),
            'total_amount'     => 'Rp ' . number_format($reservation->total_amount ?? 0, 0, ',', '.'),
            'status'           => e($reservation->status ?? '—'),
            'notes'            => e($reservation->notes ?? '—'),
            'created_at'       => $reservation->created_at?->format('d/m/Y H:i') ?? '—',
            'items_table'      => $this->renderItemsTable($items),
            'items_list'       => $this->renderItemsList($items),
            'qr_code'          => $this->renderQrOrBarcode($reservation->reservation_no, $qrType),
            'logo'             => $this->renderLogo(),
        ];

        // Flatten booking_pass_data into prefixed keys
        foreach ($bpData as $k => $v) {
            $values['booking_pass_data.' . $k] = e((string) $v);
        }

        return $values;
    }

    /** Build dummy reservation values for preview (no real reservation needed) */
    public function renderDummyValues(string $qrType = 'qr'): array
    {
        return [
            'reservation_no'  => 'RES-20260513-0099',
            'guest_name'      => 'John Doe',
            'guest_country'   => 'Indonesia',
            'visit_date'      => '13 May 2026',
            'partner_name'    => 'Hotel ABC',
            'product_name'    => 'Trans Studio Theme Park',
            'payment_method'  => 'DEPOSIT',
            'payment_channel' => 'Transfer',
            'total_amount'    => 'Rp 500.000',
            'status'          => 'CONFIRMED',
            'notes'           => 'VIP Guest',
            'created_at'      => '13/05/2026 10:00',
            'items_table'     => '<table style="width:100%;border-collapse:collapse;font-size:10pt;">'
                . '<thead><tr style="background:#0f1729;color:#fff;">'
                . '<th style="padding:4px 6px;">No.</th><th style="padding:4px 6px;">Produk</th>'
                . '<th style="padding:4px 6px;">Qty</th><th style="padding:4px 6px;">Subtotal</th></tr></thead>'
                . '<tbody><tr><td style="padding:3px 6px;">1</td><td style="padding:3px 6px;">Trans Studio Theme Park</td>'
                . '<td style="padding:3px 6px;text-align:center;">2</td><td style="padding:3px 6px;">Rp 500.000</td></tr></tbody>'
                . '<tfoot><tr><td colspan="3" style="padding:3px 6px;text-align:right;">Total</td>'
                . '<td style="padding:3px 6px;font-weight:bold;">Rp 500.000</td></tr></tfoot></table>',
            'items_list'      => '• Trans Studio Theme Park x2',
            'qr_code'         => $this->renderQrOrBarcode('RES-20260513-0099', $qrType),
            'logo'            => $this->renderLogo(),
            'booking_pass_data.voucher_code' => 'HOTEL-PROMO-2026',
            'booking_pass_data.room_no'      => '205',
        ];
    }

    /** Generate preview PDF using a real reservation */
    public function previewWithReservation(BookingPassTemplate $template, Reservation $reservation): string
    {
        $fieldValues = $this->renderFieldValues($reservation, $template->qr_type ?? 'qr');
        $path        = 'booking-pass-previews/preview-' . $template->id . '-real.pdf';
        $mapping     = $template->field_mapping ?? [];

        if (!empty($mapping)) {
            $pdf = Pdf::loadView('booking-pass.pdf-custom', [
                'template'    => $template,
                'fieldValues' => $fieldValues,
                'fieldMap'    => $mapping,
            ])->setPaper('a4', 'portrait');
        } else {
            $pdf = Pdf::loadView('booking-pass.pdf', $this->buildDefaultData($reservation, $template))
                ->setPaper('a4', 'portrait');
        }

        Storage::disk('public')->put($path, $pdf->output());

        return asset('storage/' . $path);
    }

    /** Generate preview PDF using template field_mapping with dummy data */
    public function previewWithTemplate(BookingPassTemplate $template): string
    {
        $fieldValues = $this->renderDummyValues($template->qr_type ?? 'qr');
        $path        = 'booking-pass-previews/preview-' . $template->id . '.pdf';

        // Merge any custom_fields defined in the mapping that aren't in dummy
        $mapping = $template->field_mapping ?? [];
        foreach ($mapping['custom_fields'] ?? [] as $cf) {
            if (!isset($fieldValues[$cf['key']])) {
                $fieldValues[$cf['key']] = '[' . $cf['label'] . ']';
            }
        }

        if (!empty($mapping)) {
            $pdf = Pdf::loadView('booking-pass.pdf-custom', [
                'template'    => $template,
                'fieldValues' => $fieldValues,
                'fieldMap'    => $mapping,
            ])->setPaper('a4', 'portrait');
        } else {
            $pdf = Pdf::loadView('booking-pass.pdf', [
                'reservation' => (object) [
                    'reservation_no' => 'RES-20260513-0099',
                    'guest_name'     => 'John Doe',
                    'guest_country'  => 'Indonesia',
                    'visit_date'     => now(),
                    'total_amount'   => 500000,
                    'status'         => 'CONFIRMED',
                    'notes'          => '',
                    'created_at'     => now(),
                    'booking_pass_data' => [],
                ],
                'partner'     => null,
                'items'       => collect(),
                'companyName' => Setting::get('company_name', 'TSBL'),
                'companyLogo' => Setting::get('company_logo'),
                'customData'  => [],
            ])->setPaper('a4', 'portrait');
        }

        Storage::disk('public')->put($path, $pdf->output());

        return asset('storage/' . $path);
    }

    private function renderItemsTable($items): string
    {
        $html = '<table style="width:100%;border-collapse:collapse;font-size:10pt;">'
            . '<thead><tr style="background:#0f1729;color:#fff;">'
            . '<th style="padding:4px 6px;text-align:left;">No.</th>'
            . '<th style="padding:4px 6px;text-align:left;">Produk</th>'
            . '<th style="padding:4px 6px;text-align:center;">Qty</th>'
            . '<th style="padding:4px 6px;text-align:right;">Subtotal</th>'
            . '</tr></thead><tbody>';

        $total = 0;
        foreach ($items as $i => $item) {
            $total += $item->amount ?? 0;
            $html .= '<tr style="border-bottom:1px solid #e2e8f0;">'
                . '<td style="padding:3px 6px;">' . ($i + 1) . '</td>'
                . '<td style="padding:3px 6px;">' . e($item->product_name) . '</td>'
                . '<td style="padding:3px 6px;text-align:center;">' . ($item->qty ?? 1) . '</td>'
                . '<td style="padding:3px 6px;text-align:right;">Rp ' . number_format($item->amount ?? 0, 0, ',', '.') . '</td>'
                . '</tr>';
        }

        $html .= '</tbody><tfoot><tr style="background:#f1f5f9;font-weight:bold;">'
            . '<td colspan="3" style="padding:4px 6px;text-align:right;">Total</td>'
            . '<td style="padding:4px 6px;text-align:right;">Rp ' . number_format($total, 0, ',', '.') . '</td>'
            . '</tr></tfoot></table>';

        return $html;
    }

    private function renderItemsList($items): string
    {
        $lines = [];
        foreach ($items as $item) {
            $lines[] = '• ' . e($item->product_name) . ' x' . ($item->qty ?? 1);
        }
        return implode('<br>', $lines) ?: '—';
    }

    private function renderQrOrBarcode(string $reservationNo, string $type = 'qr'): string
    {
        if ($type === 'barcode') {
            return BarcodeRenderer::code39($reservationNo, 2, 55, true, true);
        }

        // QR: Fetch scannable QR from QRServer API and encode as base64
        $url  = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($reservationNo);
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $data = curl_exec($ch);
            $err  = curl_error($ch);
            curl_close($ch);

            if ($data && !$err) {
                return '<img src="data:image/png;base64,' . base64_encode($data) . '" style="width:100%; max-width:150px; height:auto; display:block; margin:0 auto;" />';
            }
        } catch (\Exception $e) {
            // Fallback to placeholder if offline
        }

        return '<table style="border-collapse:collapse;width:100%;font-family:monospace;">'
            . '<tr><td style="border:2px solid #1e293b;padding:6px 10px;text-align:center;">'
            . '<div style="font-size:8pt;font-weight:bold;letter-spacing:1px;">' . e($reservationNo) . '</div>'
            . '</td></tr></table>';
    }

    private function renderLogo(): string
    {
        $logo = Setting::get('company_logo');
        if (!$logo) return '';
        $path = public_path('storage/' . ltrim($logo, '/'));
        if (!file_exists($path)) return '';
        $ext  = pathinfo($path, PATHINFO_EXTENSION);
        $b64  = base64_encode(file_get_contents($path));
        return '<img src="data:image/' . $ext . ';base64,' . $b64 . '" style="max-height:50px;max-width:120px;" />';
    }

    private function buildDefaultData(Reservation $reservation, ?BookingPassTemplate $template): array
    {
        return [
            'reservation' => $reservation,
            'partner'     => $reservation->partner,
            'items'       => $reservation->items,
            'companyName' => Setting::get('company_name', 'TSBL'),
            'companyLogo' => Setting::get('company_logo'),
            'customData'  => $reservation->booking_pass_data ?? [],
        ];
    }
}
