<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Partner;
use App\Models\Reservation;
use App\Services\InvoiceCreatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use LogicException;

class ReservationController extends Controller
{
    public function __construct(
        private readonly InvoiceCreatorService $invoiceCreator
    ) {}

    /**
     * D1 — List reservations with filters (status, partner, date range).
     */
    public function index(Request $request)
    {
        $query = Reservation::with('partner')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->partner_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('check_in_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('check_in_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reservation_no', 'like', "%{$search}%")
                  ->orWhere('guest_name', 'like', "%{$search}%")
                  ->orWhere('booking_ref', 'like', "%{$search}%");
            });
        }

        $reservations = $query->paginate(20)->withQueryString();
        $partners     = Partner::orderBy('name')->get();
        $statuses     = ['PENDING', 'CONFIRMED', 'CANCELLED', 'NO_SHOW', 'COMPLETED'];

        return view('reservations.index', compact('reservations', 'partners', 'statuses'));
    }

    /**
     * D1 — Show create reservation form.
     */
    public function create()
    {
        $partners = Partner::orderBy('name')->get();
        return view('reservations.create', compact('partners'));
    }

    /**
     * D1 — Store a new reservation.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'partner_id'       => 'required|exists:partners,id',
            'guest_name'       => 'required|string|max:255',
            'booking_ref'      => 'nullable|string|max:100',
            'check_in_date'    => 'required|date',
            'check_out_date'   => 'nullable|date|after_or_equal:check_in_date',
            'pax'              => 'nullable|integer|min:1',
            'product_type'     => 'nullable|string|max:100',
            'proforma_amount'  => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string|max:2000',
        ]);

        $reservation = Reservation::create(array_merge($validated, [
            'reservation_no' => 'RES-' . now()->format('YmdHis') . '-' . strtoupper(substr(uniqid(), -4)),
            'status'         => 'PENDING',
            'created_by'     => Auth::id(),
        ]));

        return redirect()
            ->route('reservations.show', $reservation)
            ->with('success', "Reservation {$reservation->reservation_no} created.");
    }

    /**
     * D1 — Show reservation detail.
     */
    public function show(Reservation $reservation)
    {
        $reservation->load(['partner', 'proformaInvoices', 'dsiTransactions', 'reconciliations']);
        return view('reservations.show', compact('reservation'));
    }

    /**
     * D1 — Confirm a PENDING reservation.
     */
    public function confirm(Request $request, Reservation $reservation)
    {
        if ($reservation->status !== 'PENDING') {
            return back()->with('error', "Reservation is not PENDING (current: {$reservation->status}).");
        }

        $reservation->update([
            'status'     => 'CONFIRMED',
            'updated_by' => Auth::id(),
        ]);

        return back()->with('success', "Reservation {$reservation->reservation_no} confirmed.");
    }

    /**
     * D1 — Cancel a reservation. Guard: no active DSI transaction exists.
     */
    public function cancel(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'cancel_reason' => 'required|string|max:1000',
        ]);

        if (in_array($reservation->status, ['CANCELLED', 'COMPLETED'], true)) {
            return back()->with('error', "Cannot cancel a {$reservation->status} reservation.");
        }

        // Guard: no locked DSI transaction exists
        if ($reservation->dsiTransactions()->where('is_locked', true)->exists()) {
            return back()->with('error', 'Cannot cancel — this reservation has a locked DSI transaction tied to a reconciliation.');
        }

        $reservation->update([
            'status'        => 'CANCELLED',
            'cancel_reason' => $validated['cancel_reason'],
            'cancelled_at'  => now(),
            'updated_by'    => Auth::id(),
        ]);

        return back()->with('success', "Reservation {$reservation->reservation_no} cancelled.");
    }

    /**
     * D1 — Issue a Proforma invoice for a CONFIRMED reservation.
     */
    public function issueProforma(Request $request, Reservation $reservation)
    {
        if ($reservation->status !== 'CONFIRMED') {
            return back()->with('error', "Reservation must be CONFIRMED to issue a Proforma invoice.");
        }

        // Guard: no existing proforma for this reservation
        if ($reservation->proformaInvoices()->exists()) {
            return back()->with('error', 'A Proforma invoice already exists for this reservation.');
        }

        $validated = $request->validate([
            'items'               => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity'    => 'required|numeric|min:0',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'items.*.amount'      => 'required|numeric|min:0',
            'due_date'            => 'nullable|date|after_or_equal:today',
            'notes'               => 'nullable|string|max:2000',
        ]);

        try {
            $partner = $reservation->partner;
            $items   = collect($validated['items'])->values()->map(fn ($item, $i) => array_merge($item, [
                'sort_order' => $i + 1,
            ]))->toArray();

            $grandTotal = collect($items)->sum('amount');

            $proforma = $this->invoiceCreator->createProforma(
                [
                    'partner_id'    => $reservation->partner_id,
                    'invoice_date'  => now()->toDateString(),
                    'due_date'      => $validated['due_date'] ?? now()->addDays($partner->payment_due_days ?? 30)->toDateString(),
                    'grand_total'   => $grandTotal,
                    'subtotal'      => $grandTotal,
                    'deposit'       => 0,
                    'source_type'   => Reservation::class,
                    'source_id'     => $reservation->id,
                    'notes'         => $validated['notes'] ?? null,
                ],
                $items,
                Auth::id()
            );

            return redirect()
                ->route('billing-invoices.show', $proforma)
                ->with('success', "Proforma invoice {$proforma->invoice_no} issued for reservation {$reservation->reservation_no}.");

        } catch (LogicException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
