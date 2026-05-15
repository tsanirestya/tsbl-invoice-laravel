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
            ->orderBy('product_name')
            ->get(['id', 'product_name', 'partner_type', 'publish_rate', 'nett_price', 'komisi', 'category', 'market_type', 'payment_mode', 'sub_payment_mode']);

        return view('reservations.create', compact('partners', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'partner_id'             => 'nullable|exists:partners,id',
            'guest_name'             => 'required|string|max:255',
            'guest_country'          => 'nullable|string|max:100',
            'visit_date'             => 'required|date',
            'reservation_type'       => 'required|in:PARTNER,INTERNAL,SELF_SERVICE',
            'payment_method'         => 'nullable|in:TRANSFER_GROSS,TRANSFER_NETT,ON_THE_SPOT',
            'payment_channel'        => 'nullable|in:CASH,DEBIT,CREDIT',
            'notes'                  => 'nullable|string|max:2000',
            'latitude'               => 'nullable|numeric',
            'longitude'              => 'nullable|numeric',
            'location_name'          => 'nullable|string|max:255',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.qty'            => 'required|integer|min:1',
            'items.*.price_per_pax'  => 'required|numeric|min:0',
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

            // Create items
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
        $products = Product::where('is_active', true)
            ->orderBy('product_name')
            ->get(['id', 'product_name', 'partner_type', 'publish_rate', 'nett_price', 'komisi', 'category', 'market_type', 'payment_mode', 'sub_payment_mode']);

        return view('reservations.edit', compact('reservation', 'partners', 'products'));
    }

    public function update(Request $request, Reservation $reservation)
    {
        if ($reservation->status === 'CANCELLED') {
            return back()->with('error', 'Reservasi yang sudah dibatalkan tidak bisa diedit.');
        }

        $validated = $request->validate([
            'guest_name'             => 'required|string|max:255',
            'guest_country'          => 'nullable|string|max:100',
            'visit_date'             => 'required|date',
            'payment_method'         => 'nullable|in:TRANSFER_GROSS,TRANSFER_NETT,ON_THE_SPOT',
            'payment_channel'        => 'nullable|in:CASH,DEBIT,CREDIT',
            'notes'                  => 'nullable|string|max:2000',
            'status'                 => 'required|in:PENDING,CONFIRMED,CANCELLED,NO_SHOW,COMPLETED',
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
