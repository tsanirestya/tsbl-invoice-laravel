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

class ReservationController extends Controller
{
    public function __construct(
        private DangerZoneService      $dangerZone,
        private AnomalyDetectionService $anomalyDetection,
        private BookingPassService      $bookingPass,
    ) {}

    public function index(Request $request)
    {
        $query = Reservation::with(['partner', 'items', 'payment'])
            ->latest();

        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->partner_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('reservation_type', $request->type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('visit_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('visit_date', '<=', $request->date_to);
        }

        $reservations = $query->paginate(20)->withQueryString();
        $partners = Partner::where('is_active', true)->orderBy('nama_partner')->get(['id', 'nama_partner']);

        return view('reservations.index', compact('reservations', 'partners'));
    }

    public function create()
    {
        $partners = Partner::where('is_active', true)->orderBy('nama_partner')->get(['id', 'nama_partner', 'partner_type', 'category']);

        $products = Product::where('is_active', true)
            ->whereNotNull('parents_name')
            ->select('id','product_name','parents_name','pax_type',
                     'sub_payment_mode','market_type','publish_rate',
                     'nett_price','komisi','payment_mode',
                     'bundle_adult_count','bundle_child_count')
            ->orderBy('parents_name')
            ->orderBy('pax_type')
            ->get();

        // grouped[parents_name][sub_payment_mode][market_type] = [{id, pax_type, ...}]
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

        return view('reservations.create', compact('partners', 'groupedProducts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'partner_id'                    => 'nullable|exists:partners,id',
            'guest_name'                    => 'required|string|max:255',
            'guest_country'                 => 'nullable|string|max:100',
            'visit_date'                    => 'required|date',
            'reservation_type'              => 'required|in:PARTNER,INTERNAL,SELF_SERVICE',
            'payment_method'                => 'nullable|in:TRANSFER_GROSS,TRANSFER_NETT,ON_THE_SPOT',
            'payment_channel'               => 'nullable|in:CASH,DEBIT,CREDIT',
            'notes'                         => 'nullable|string|max:2000',
            'latitude'                      => 'nullable|numeric',
            'longitude'                     => 'nullable|numeric',
            'location_name'                 => 'nullable|string|max:255',
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

        $reservation = DB::transaction(function () use ($validated, $request) {
            // Danger zone check
            $isDangerZone = false;
            $locationName = $validated['location_name'] ?? null;
            if ($validated['latitude'] && $validated['longitude']) {
                $isDangerZone = $this->dangerZone->isInDangerZone(
                    (float) $validated['latitude'],
                    (float) $validated['longitude']
                );
            }

            // Spot check: random 10%
            $spotCheckPct = (int) \App\Models\Setting::get('spot_check_percentage', 10);
            $isSpotCheck  = ($validated['reservation_type'] === 'ON_THE_SPOT' || $validated['payment_method'] === 'ON_THE_SPOT')
                            && (rand(1, 100) <= $spotCheckPct);

            $partner = $validated['partner_id'] ? Partner::find($validated['partner_id']) : null;
            $origin = null;
            $originDetail = null;

            if ($partner) {
                $origin = match($partner->partner_type) {
                    'HOTEL'    => 'HOTEL',
                    'TRAVEL'   => 'TRAVEL_AGENT',
                    'TOURDESK' => 'TRAVEL_AGENT',
                    default    => 'OTHER'
                };
                $originDetail = $partner->nama_partner;
            }

            $reservation = Reservation::create([
                'reservation_no'   => Reservation::generateReservationNo(),
                'partner_id'       => $validated['partner_id'] ?? null,
                'guest_name'       => $validated['guest_name'],
                'guest_country'    => $validated['guest_country'] ?? null,
                'visit_date'       => $validated['visit_date'],
                'status'           => 'CONFIRMED',
                'reservation_type' => $validated['reservation_type'],
                'payment_method'   => $validated['payment_method'] ?? null,
                'payment_channel'  => $validated['payment_channel'] ?? null,
                'customer_origin'  => $origin,
                'customer_origin_detail' => $originDetail,
                'notes'            => $validated['notes'] ?? null,
                'latitude'         => $validated['latitude'] ?? null,
                'longitude'        => $validated['longitude'] ?? null,
                'location_name'    => $locationName,
                'is_danger_zone'   => $isDangerZone,
                'is_spot_check'    => $isSpotCheck,
                'ip_address'       => $request->ip(),
                'user_agent'       => $request->userAgent(),
                'created_by'       => auth()->id(),
            ]);

            // Create items (pax-based)
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

            $reservation->update([
                'total_amount' => $total,
                'pax_adults'   => $totalAdults,
                'pax_kids'     => $totalKids,
                'pax_babies'   => $paxBabies,
            ]);

            // Create payment record
            if ($validated['payment_method']) {
                $this->createPaymentRecord($reservation, $validated['payment_method'], $total);
            }

            // Anomaly detection (PARTNER type only)
            if ($reservation->partner_id && $reservation->reservation_type === 'PARTNER') {
                $reservation->update(['fraud_score_snapshot' => $reservation->partner->fraud_score]);
                $this->anomalyDetection->check($reservation->fresh());
            }

            // Generate booking pass PDF
            $this->bookingPass->generate($reservation->fresh());

            return $reservation;
        });

        return redirect()->route('reservations.show', $reservation)
            ->with('success', "Reservasi {$reservation->reservation_no} berhasil dibuat.");
    }

    public function show(Reservation $reservation)
    {
        $reservation->load(['partner', 'items.product', 'payment', 'anomalies.resolvedBy', 'creator']);
        return view('reservations.show', compact('reservation'));
    }

    public function edit(Reservation $reservation)
    {
        if ($reservation->status === 'CANCELLED') {
            return back()->with('error', 'Reservasi yang sudah dibatalkan tidak bisa diedit.');
        }

        $partners = Partner::where('is_active', true)->orderBy('nama_partner')->get(['id', 'nama_partner', 'partner_type', 'category']);

        return view('reservations.edit', compact('reservation', 'partners'));
    }

    public function update(Request $request, Reservation $reservation)
    {
        if ($reservation->status === 'CANCELLED') {
            return back()->with('error', 'Reservasi yang sudah dibatalkan tidak bisa diedit.');
        }

        $validated = $request->validate([
            'guest_name'     => 'required|string|max:255',
            'guest_country'  => 'nullable|string|max:100',
            'visit_date'     => 'required|date',
            'payment_method' => 'nullable|in:TRANSFER_GROSS,TRANSFER_NETT,ON_THE_SPOT',
            'payment_channel'=> 'nullable|in:CASH,DEBIT,CREDIT',
            'notes'          => 'nullable|string|max:2000',
            'status'         => 'required|in:PENDING,CONFIRMED,CANCELLED,NO_SHOW,COMPLETED',
            'pax_babies'     => 'nullable|integer|min:0',
        ]);

        $reservation->update($validated);

        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'Reservasi berhasil diperbarui.');
    }

    public function cancel(Request $request, Reservation $reservation)
    {
        if (in_array($reservation->status, ['CANCELLED', 'COMPLETED'])) {
            return back()->with('error', 'Reservasi ini tidak bisa dibatalkan.');
        }

        $reservation->update(['status' => 'CANCELLED']);

        return back()->with('success', 'Reservasi berhasil dibatalkan.');
    }

    public function bookingPassDownload(Reservation $reservation)
    {
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

    private function createPaymentRecord(Reservation $reservation, string $method, float $grossAmount): void
    {
        $partner = $reservation->partner;
        $items   = $reservation->items;

        // Calculate commission from product komisi fields
        $commissionAmount = 0;
        foreach ($items as $item) {
            $product = $item->product;
            if ($product && $product->komisi > 0) {
                $commissionAmount += $product->komisi * $item->qty;
            }
        }

        $nettAmount = $grossAmount - $commissionAmount;
        $commissionRate = $grossAmount > 0 ? ($commissionAmount / $grossAmount) * 100 : 0;
        $isEligible = $method === 'TRANSFER_GROSS';

        // Hold commission if partner is HIGH/CRITICAL risk
        $isHeld = false;
        $holdReason = null;
        if ($partner && in_array($partner->fraudRiskLevel(), ['HIGH', 'CRITICAL'])) {
            $isHeld     = true;
            $holdReason = "Auto-hold: partner fraud risk level {$partner->fraudRiskLevel()} (score: {$partner->fraud_score})";
        }

        ReservationPayment::create([
            'reservation_id'       => $reservation->id,
            'payment_method'       => $method,
            'payment_channel'      => $reservation->payment_channel,
            'gross_amount'         => $grossAmount,
            'nett_amount'          => $nettAmount,
            'commission_amount'    => $commissionAmount,
            'commission_rate'      => round($commissionRate, 2),
            'is_commission_eligible' => $isEligible,
            'payment_status'       => 'PENDING',
            'is_commission_held'   => $isHeld,
            'commission_hold_reason' => $holdReason,
        ]);
    }
}
