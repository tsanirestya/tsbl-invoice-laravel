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
        $partner = $request->attributes->get('partner');

        $products = Product::where('is_active', true)
            ->whereNotNull('parents_name')
            ->select('id','product_name','parents_name','pax_type',
                     'sub_payment_mode','market_type','publish_rate',
                     'nett_price','komisi','payment_mode',
                     'bundle_adult_count','bundle_child_count')
            ->orderBy('parents_name')
            ->orderBy('pax_type')
            ->get();

        $groupedProducts = [];
        foreach ($products as $p) {
            $parent  = $p->parents_name;
            $payMode = strtoupper($p->sub_payment_mode ?? 'GROSS');
            $market  = strtoupper($p->market_type ?? 'FOREIGN');
            $groupedProducts[$parent][$payMode][$market][] = [
                'id'                 => $p->id,
                'product_name'       => $p->product_name,
                'pax_type'           => $p->pax_type,
                'publish_rate'       => (float) $p->publish_rate,
                'nett_price'         => (float) $p->nett_price,
                'komisi'             => (float) $p->komisi,
                'bundle_adult_count' => (int) $p->bundle_adult_count,
                'bundle_child_count' => (int) $p->bundle_child_count,
            ];
        }

        return view('partner-reserve.form', compact('partner', 'groupedProducts', 'token'));
    }

    public function store(Request $request, string $token)
    {
        $partner = $request->attributes->get('partner');

        $validated = $request->validate([
            'guest_name'                    => 'required|string|max:255',
            'guest_country'                 => 'nullable|string|max:100',
            'visit_date'                    => 'required|date',
            'payment_method'                => 'required|in:TRANSFER_GROSS,TRANSFER_NETT,ON_THE_SPOT',
            'payment_channel'               => 'nullable|in:CASH,DEBIT,CREDIT',
            'notes'                         => 'nullable|string|max:2000',
            'latitude'                      => 'nullable|numeric',
            'longitude'                     => 'nullable|numeric',
            'location_name'                 => 'nullable|string|max:255',
            'device_fingerprint'            => 'nullable|string|max:255',
            'pax_babies'                    => 'nullable|integer|min:0',
            'items'                         => 'required|array|min:1',
            'items.*.row_type'              => 'required|in:ADULT_CHILD,BUNDLE,TICKET,DOMESTIC',
            'items.*.adult_product_id'      => 'nullable|exists:products,id',
            'items.*.adult_qty'             => 'nullable|integer|min:0',
            'items.*.adult_price'           => 'nullable|numeric|min:0',
            'items.*.child_product_id'      => 'nullable|exists:products,id',
            'items.*.child_qty'             => 'nullable|integer|min:0',
            'items.*.child_price'           => 'nullable|numeric|min:0',
            'items.*.bundle_product_id'     => 'nullable|exists:products,id',
            'items.*.bundle_qty'            => 'nullable|integer|min:0',
            'items.*.bundle_price'          => 'nullable|numeric|min:0',
            'items.*.ticket_product_id'     => 'nullable|exists:products,id',
            'items.*.ticket_qty'            => 'nullable|integer|min:0',
            'items.*.ticket_price'          => 'nullable|numeric|min:0',
        ]);

        // Guard: at least one item must have qty > 0
        $hasQty = false;
        foreach ($validated['items'] as $item) {
            if (($item['adult_qty'] ?? 0) > 0 || ($item['child_qty'] ?? 0) > 0 ||
                ($item['bundle_qty'] ?? 0) > 0 || ($item['ticket_qty'] ?? 0) > 0) {
                $hasQty = true;
                break;
            }
        }
        if (!$hasQty) {
            return back()->withErrors(['items' => 'Minimal satu aktivitas harus diisi dengan jumlah tamu.'])->withInput();
        }

        // Rate limit: max per hour
        $maxPerHour  = (int) \App\Models\Setting::get('reservation_max_per_hour_per_partner', 5);
        $recentCount = Reservation::where('partner_id', $partner->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();
        if ($recentCount >= $maxPerHour) {
            return back()->withInput()->with('error', "Batas maksimum {$maxPerHour} reservasi per jam telah tercapai. Coba lagi nanti.");
        }

        // Visit date range validation
        $minDays   = (int) \App\Models\Setting::get('reservation_min_days_before', 0);
        $maxDays   = (int) \App\Models\Setting::get('reservation_max_days_before', 30);
        $visitDate = \Illuminate\Support\Carbon::parse($validated['visit_date']);
        $daysAhead = now()->startOfDay()->diffInDays($visitDate->startOfDay(), false);

        if ($daysAhead < $minDays || $daysAhead > $maxDays) {
            return back()->withInput()->with('error',
                "Tanggal kunjungan harus antara {$minDays}–{$maxDays} hari dari hari ini."
            );
        }

        $reservation = DB::transaction(function () use ($partner, $validated, $request) {
            $isDangerZone = false;
            $locationName = $validated['location_name'] ?? null;
            if ($validated['latitude'] && $validated['longitude']) {
                $isDangerZone = $this->dangerZone->isInDangerZone(
                    (float) $validated['latitude'],
                    (float) $validated['longitude']
                );
            }

            $spotCheckPct = (int) \App\Models\Setting::get('spot_check_percentage', 10);
            $isSpotCheck  = $validated['payment_method'] === 'ON_THE_SPOT' && rand(1, 100) <= $spotCheckPct;

            $reservation = Reservation::create([
                'reservation_no'       => Reservation::generateReservationNo(),
                'partner_id'           => $partner->id,
                'guest_name'           => $validated['guest_name'],
                'guest_country'        => $validated['guest_country'] ?? null,
                'visit_date'           => $validated['visit_date'],
                'status'               => 'CONFIRMED',
                'reservation_type'     => 'PARTNER',
                'payment_method'       => $validated['payment_method'],
                'payment_channel'      => $validated['payment_channel'] ?? null,
                'customer_origin'      => match($partner->partner_type) {
                    'HOTEL'    => 'HOTEL',
                    'TRAVEL'   => 'TRAVEL_AGENT',
                    'TOURDESK' => 'TRAVEL_AGENT',
                    default    => 'OTHER'
                },
                'customer_origin_detail' => $partner->nama_partner,
                'notes'                => $validated['notes'] ?? null,
                'latitude'             => $validated['latitude'] ?? null,
                'longitude'            => $validated['longitude'] ?? null,
                'location_name'        => $locationName,
                'is_danger_zone'       => $isDangerZone,
                'is_spot_check'        => $isSpotCheck,
                'fraud_score_snapshot' => $partner->fraud_score,
                'ip_address'           => $request->ip(),
                'user_agent'           => $request->userAgent(),
                'device_fingerprint'   => $validated['device_fingerprint'] ?? null,
                'created_by'           => null,
            ]);

            $total       = 0;
            $totalAdults = 0;
            $totalKids   = 0;
            $paxBabies   = (int) ($validated['pax_babies'] ?? 0);
            $sortOrder   = 0;

            foreach ($validated['items'] as $item) {
                switch ($item['row_type']) {
                    case 'ADULT_CHILD':
                        if (!empty($item['adult_product_id']) && ($item['adult_qty'] ?? 0) > 0) {
                            $prod   = Product::find($item['adult_product_id']);
                            $amount = (float) $item['adult_price'] * (int) $item['adult_qty'];
                            $total += $amount;
                            $totalAdults += (int) $item['adult_qty'];
                            ReservationItem::create([
                                'reservation_id' => $reservation->id,
                                'product_id'     => $item['adult_product_id'],
                                'product_name'   => $prod->product_name,
                                'qty'            => $item['adult_qty'],
                                'price_per_pax'  => $item['adult_price'],
                                'amount'         => $amount,
                                'sort_order'     => $sortOrder++,
                            ]);
                        }
                        if (!empty($item['child_product_id']) && ($item['child_qty'] ?? 0) > 0) {
                            $prod   = Product::find($item['child_product_id']);
                            $amount = (float) $item['child_price'] * (int) $item['child_qty'];
                            $total += $amount;
                            $totalKids += (int) $item['child_qty'];
                            ReservationItem::create([
                                'reservation_id' => $reservation->id,
                                'product_id'     => $item['child_product_id'],
                                'product_name'   => $prod->product_name,
                                'qty'            => $item['child_qty'],
                                'price_per_pax'  => $item['child_price'],
                                'amount'         => $amount,
                                'sort_order'     => $sortOrder++,
                            ]);
                        }
                        break;

                    case 'BUNDLE':
                        if (!empty($item['bundle_product_id']) && ($item['bundle_qty'] ?? 0) > 0) {
                            $prod   = Product::find($item['bundle_product_id']);
                            $amount = (float) $item['bundle_price'] * (int) $item['bundle_qty'];
                            $total += $amount;
                            $totalAdults += $prod->bundle_adult_count * (int) $item['bundle_qty'];
                            $totalKids   += $prod->bundle_child_count * (int) $item['bundle_qty'];
                            ReservationItem::create([
                                'reservation_id' => $reservation->id,
                                'product_id'     => $item['bundle_product_id'],
                                'product_name'   => $prod->product_name,
                                'qty'            => $item['bundle_qty'],
                                'price_per_pax'  => $item['bundle_price'],
                                'amount'         => $amount,
                                'sort_order'     => $sortOrder++,
                            ]);
                        }
                        break;

                    case 'TICKET':
                    case 'DOMESTIC':
                        if (!empty($item['ticket_product_id']) && ($item['ticket_qty'] ?? 0) > 0) {
                            $prod   = Product::find($item['ticket_product_id']);
                            $amount = (float) $item['ticket_price'] * (int) $item['ticket_qty'];
                            $total += $amount;
                            $totalAdults += (int) $item['ticket_qty'];
                            ReservationItem::create([
                                'reservation_id' => $reservation->id,
                                'product_id'     => $item['ticket_product_id'],
                                'product_name'   => $prod->product_name,
                                'qty'            => $item['ticket_qty'],
                                'price_per_pax'  => $item['ticket_price'],
                                'amount'         => $amount,
                                'sort_order'     => $sortOrder++,
                            ]);
                        }
                        break;
                }
            }

            if ($paxBabies > 0) {
                ReservationItem::create([
                    'reservation_id' => $reservation->id,
                    'product_id'     => null,
                    'product_name'   => 'Baby (FREE)',
                    'qty'            => $paxBabies,
                    'price_per_pax'  => 0,
                    'amount'         => 0,
                    'sort_order'     => $sortOrder++,
                ]);
            }

            $reservation->update([
                'total_amount' => $total,
                'pax_adults'   => $totalAdults,
                'pax_kids'     => $totalKids,
                'pax_babies'   => $paxBabies,
            ]);

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
