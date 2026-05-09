<?php

namespace App\Http\Controllers;

use App\Events\PaymentVerified;
use App\Models\CreditBalance;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\Payment;
use App\Services\CreditBalanceService;
use App\Services\PaymentAllocatorService;
use App\Services\PaymentRecorderService;
use App\Services\PaymentVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use LogicException;

class BillingPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentRecorderService    $recorder,
        private readonly PaymentVerificationService $verifier,
        private readonly PaymentAllocatorService   $allocator,
        private readonly CreditBalanceService      $creditBalance
    ) {}

    /**
     * D5 — List payments with status indicators.
     */
    public function index(Request $request)
    {
        $query = Payment::with(['invoice.partner'])
            ->orderByDesc('created_at');

        if ($request->filled('partner_id')) {
            $query->whereHas('invoice', fn ($q) => $q->where('partner_id', $request->partner_id));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                  ->orWhereHas('invoice', fn ($inv) => $inv->where('invoice_no', 'like', "%{$search}%"));
            });
        }

        $payments = $query->paginate(25)->withQueryString();
        $partners = Partner::orderBy('name')->get();

        return view('billing-payments.index', compact('payments', 'partners'));
    }

    /**
     * D5 — Record incoming payment against an invoice.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id'     => 'required|exists:invoices,id',
            'amount'         => 'required|numeric|min:0.01',
            'payment_date'   => 'required|date',
            'payment_method' => 'required|string|max:50',
            'reference_no'   => 'nullable|string|max:100',
            'proof_file'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'notes'          => 'nullable|string|max:2000',
        ]);

        $invoice = Invoice::findOrFail($validated['invoice_id']);

        // Handle proof file upload
        $proofPath = null;
        if ($request->hasFile('proof_file')) {
            $proofPath = $request->file('proof_file')->store('payment-proofs', 'public');
        }

        try {
            $payment = $this->recorder->record(
                invoice:   $invoice,
                data:      [
                    'payment_date'   => $validated['payment_date'],
                    'payment_method' => $validated['payment_method'],
                    'reference_no'   => $validated['reference_no'] ?? null,
                    'proof_file'     => $proofPath,
                    'notes'          => $validated['notes'] ?? null,
                ],
                amount:    (float) $validated['amount'],
                createdBy: Auth::id()
            );

            return redirect()
                ->route('billing-payments.show', $payment)
                ->with('success', "Payment of Rp " . number_format($payment->amount, 0, ',', '.') . " recorded successfully.");

        } catch (LogicException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * D5 — Show payment detail + allocation history.
     */
    public function show(Payment $billingPayment)
    {
        $billingPayment->load(['invoice.partner', 'allocations.invoice']);
        return view('billing-payments.show', compact('billingPayment'));
    }

    /**
     * D5 — Finance verifies a payment.
     */
    public function verify(Request $request, Payment $billingPayment)
    {
        try {
            $this->verifier->verify($billingPayment, Auth::id());

            // Fire event to trigger allocation job
            event(new PaymentVerified($billingPayment));

            return back()->with('success', "Payment #{$billingPayment->id} verified.");
        } catch (LogicException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * D5 — Reject a payment with reason.
     */
    public function reject(Request $request, Payment $billingPayment)
    {
        $validated = $request->validate([
            'reject_reason' => 'required|string|max:1000',
        ]);

        try {
            $this->verifier->reject($billingPayment, Auth::id(), $validated['reject_reason']);
            return back()->with('success', "Payment #{$billingPayment->id} rejected.");
        } catch (LogicException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * D5 — Allocate verified payment to an invoice.
     */
    public function allocate(Request $request, Payment $billingPayment)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'notes'      => 'nullable|string|max:1000',
        ]);

        $invoice = Invoice::findOrFail($validated['invoice_id']);

        try {
            $result = $this->allocator->allocate(
                payment:     $billingPayment,
                invoice:     $invoice,
                allocatedBy: Auth::id(),
                notes:       $validated['notes'] ?? null
            );

            $msg = "Allocated Rp " . number_format($result['allocated'], 0, ',', '.') . " to invoice {$invoice->invoice_no}.";
            if ($result['overpayment'] > 0) {
                $msg .= " Rp " . number_format($result['overpayment'], 0, ',', '.') . " added to partner credit balance.";
            }

            return back()->with('success', $msg);

        } catch (LogicException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * D5 — View credit balances for all partners (or filtered by partner).
     */
    public function creditBalance(Request $request)
    {
        $query = CreditBalance::with('partner')->orderByDesc('balance');

        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->partner_id);
        }

        $balances = $query->paginate(20)->withQueryString();
        $partners = Partner::orderBy('name')->get();

        return view('billing-payments.credit-balance', compact('balances', 'partners'));
    }

    /**
     * D5 — Apply credit balance to a specific invoice.
     */
    public function applyCreditBalance(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount'     => 'required|numeric|min:0.01',
        ]);

        $invoice = Invoice::findOrFail($validated['invoice_id']);

        try {
            $remaining = $this->creditBalance->applyToInvoice(
                invoice:   $invoice,
                amount:    (float) $validated['amount'],
                appliedBy: Auth::id()
            );

            return back()->with('success',
                "Credit of Rp " . number_format($validated['amount'], 0, ',', '.') .
                " applied to invoice {$invoice->invoice_no}. Remaining credit: Rp " .
                number_format($remaining, 0, ',', '.')
            );

        } catch (LogicException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
