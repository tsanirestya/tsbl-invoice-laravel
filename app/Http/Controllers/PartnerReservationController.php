<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\ReservationPayment;
use App\Services\AnomalyDetectionService;
use App\Services\BookingPassService;
use App\Services\DangerZoneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartnerReservationController extends Controller
{
    public function __construct(
        private DangerZoneService       $dangerZone,
        private AnomalyDetectionService $anomalyDetection,
        private BookingPassService      $bookingPass,
    ) {}

    /** Public form for partner (token-based, no auth) */
    public function form(Request $request, string $token)
    {
        $partner  = $request->attributes->get('partner'); // set by middleware
        $products = Product::where('is_active', true)->orderBy('product_name')->get(['id', 'product_name', 'publish_rate', 'nett_price', 'komisi']);

        return view('partner-reserve.form', compact('partner', 'products', 'token'));
    }

    public function store(Request $request, string $token)
    {
        $partner = $request->attributes->get('partner');

        $validated = $request->validate([
            'guest_name'             => 'required|string|max:255',
            'guest_country'          => 'nullable|string|max:100',
            'visit_date'             => 'required|date',
            'payment_method'         => 'required|in:TRANSFER_GROSS,TRANSFER_NETT,ON_THE_SPOT',
            'payment_channel'        => 'nullable|in:CASH,DEBIT,CREDIT',
            'customer_origin'        => 'nullable|in:HOTEL,TRAVEL_AGENT,WALK_IN,ONLINE_AD,OTHER',
            'customer_origin_detail' => 'nullable|string|max:255',
            'notes'                  => 'nullable|string|max:2000',
            'latitude'               => 'nullable|numeric',
            'longitude'              => 'nullable|numeric',
            'device_fingerprint'     => 'nullable|string|max:255',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.qty'            => 'required|integer|min:1',
            'items.*.price_per_pax'  => 'required|numeric|min:0',
        ]);

        // Rate limit: max per hour
        $maxPerHour = (int) \App\Models\Setting::get('reservation_max_per_hour_per_partner', 5);
        $recentCount = Reservation::where('partner_id', $partner->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();
        if ($recentCount >= $maxPerHour) {
            return back()->withInput()->with('error', "Batas maksimum {$maxPerHour} reservasi per jam telah tercapai. Coba lagi nanti.");
        }

        // Visit date range validation
        $minDays = (int) \App\Models\Setting::get('reservation_min_days_before', 0);
        $maxDays = (int) \App\Models\Setting::get('reservation_max_days_before', 30);
        $visitDate = \Illuminate\Support\Carbon::parse($validated['visit_date']);
        $daysAhead = now()->startOfDay()->diffInDays($visitDate->startOfDay(), false);

        if ($daysAhead < $minDays || $daysAhead > $maxDays) {
            return back()->withInput()->with('error',
                "Tanggal kunjungan harus antara {$minDays}–{$maxDays} hari dari hari ini."
            );
        }

        $reservation = DB::transaction(function () use ($partner, $validated, $request, $token) {
            $isDangerZone = false;
            if ($validated['latitude'] && $validated['longitude']) {
                $isDangerZone = $this->dangerZone->isInDangerZone(
                    (float) $validated['latitude'],
                    (float) $validated['longitude']
                );
            }

            $spotCheckPct = (int) \App\Models\Setting::get('spot_check_percentage', 10);
            $isSpotCheck  = $validated['payment_method'] === 'ON_THE_SPOT' && rand(1, 100) <= $spotCheckPct;

            $reservation = Reservation::create([
                'reservation_no'     => Reservation::generateReservationNo(),
                'partner_id'         => $partner->id,
                'guest_name'         => $validated['guest_name'],
                'guest_country'      => $validated['guest_country'] ?? null,
                'visit_date'         => $validated['visit_date'],
                'status'             => 'CONFIRMED',
                'reservation_type'   => 'PARTNER',
                'payment_method'     => $validated['payment_method'],
                'payment_channel'    => $validated['payment_channel'] ?? null,
                'customer_origin'    => $validated['customer_origin'] ?? null,
                'customer_origin_detail' => $validated['customer_origin_detail'] ?? null,
                'notes'              => $validated['notes'] ?? null,
                'latitude'           => $validated['latitude'] ?? null,
                'longitude'          => $validated['longitude'] ?? null,
                'is_danger_zone'     => $isDangerZone,
                'is_spot_check'      => $isSpotCheck,
                'fraud_score_snapshot' => $partner->fraud_score,
                'ip_address'         => $request->ip(),
                'user_agent'         => $request->userAgent(),
                'device_fingerprint' => $validated['device_fingerprint'] ?? null,
                'qr_token'           => $token,
                'created_by'         => null,
            ]);

            $total = 0;
            foreach ($validated['items'] as $i => $item) {
                $product = Product::find($item['product_id']);
                $amount  = $item['price_per_pax'] * $item['qty'];
                $total  += $amount;
                ReservationItem::create([
                    'reservation_id' => $reservation->id,
                    'product_id'     => $item['product_id'],
                    'product_name'   => $product->product_name,
                    'qty'            => $item['qty'],
                    'price_per_pax'  => $item['price_per_pax'],
                    'amount'         => $amount,
                    'sort_order'     => $i,
                ]);
            }
            $reservation->update(['total_amount' => $total]);

            // Payment record
            $this->createPaymentRecord($reservation, $validated['payment_method'], $total, $partner);

            // Anomaly detection
            $this->anomalyDetection->check($reservation->fresh());

            // Booking pass
            $this->bookingPass->generate($reservation->fresh());

            return $reservation;
        });

        return redirect()->route('partner.reserve.success', [$token, $reservation->reservation_no])
            ->with('success', 'Reservasi berhasil! Silakan download booking pass Anda.');
    }

    public function success(Request $request, string $token, string $reservationNo)
    {
        $partner     = $request->attributes->get('partner');
        $reservation = Reservation::with('items')
            ->where('reservation_no', $reservationNo)
            ->where('partner_id', $partner->id)
            ->firstOrFail();

        return view('partner-reserve.success', compact('partner', 'reservation', 'token'));
    }

    public function history(Request $request, string $token)
    {
        $partner      = $request->attributes->get('partner');
        $reservations = Reservation::with('items', 'payment')
            ->where('partner_id', $partner->id)
            ->latest()
            ->paginate(10);

        return view('partner-reserve.history', compact('partner', 'reservations', 'token'));
    }

    public function bookingPassDownload(Request $request, string $token, string $reservationNo)
    {
        $partner     = $request->attributes->get('partner');
        $reservation = Reservation::where('reservation_no', $reservationNo)
            ->where('partner_id', $partner->id)
            ->firstOrFail();

        if (!$reservation->booking_pass_file) {
            $this->bookingPass->generate($reservation);
            $reservation->refresh();
        }

        $path = storage_path('app/public/' . $reservation->booking_pass_file);
        if (!file_exists($path)) {
            return back()->with('error', 'File booking pass tidak ditemukan.');
        }

        return response()->download($path, $reservation->reservation_no . '-booking-pass.pdf');
    }

    private function createPaymentRecord(Reservation $reservation, string $method, float $gross, Partner $partner): void
    {
        $commissionAmount = 0;
        foreach ($reservation->items as $item) {
            $product = $item->product;
            if ($product && $product->komisi > 0) {
                $commissionAmount += $product->komisi * $item->qty;
            }
        }

        $nett    = $gross - $commissionAmount;
        $rate    = $gross > 0 ? ($commissionAmount / $gross) * 100 : 0;
        $isHeld  = in_array($partner->fraudRiskLevel(), ['HIGH', 'CRITICAL']);

        ReservationPayment::create([
            'reservation_id'        => $reservation->id,
            'payment_method'        => $method,
            'payment_channel'       => $reservation->payment_channel,
            'gross_amount'          => $gross,
            'nett_amount'           => $nett,
            'commission_amount'     => $commissionAmount,
            'commission_rate'       => round($rate, 2),
            'is_commission_eligible'=> $method === 'TRANSFER_GROSS',
            'payment_status'        => 'PENDING',
            'is_commission_held'    => $isHeld,
            'commission_hold_reason'=> $isHeld ? "Auto-hold: fraud risk {$partner->fraudRiskLevel()} (score {$partner->fraud_score})" : null,
        ]);
    }
}
