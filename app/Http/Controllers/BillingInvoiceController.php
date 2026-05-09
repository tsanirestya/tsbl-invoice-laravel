<?php

namespace App\Http\Controllers;

use App\Jobs\SendInvoiceEmailJob;
use App\Models\Invoice;
use App\Models\Partner;
use App\Services\InvoiceCreatorService;
use App\Services\InvoiceVoidService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use LogicException;

class BillingInvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceCreatorService $invoiceCreator,
        private readonly InvoiceVoidService $voidService
    ) {}

    /**
     * D2 — List invoices with type/status filters.
     */
    public function index(Request $request)
    {
        $query = Invoice::with('partner')
            ->orderByDesc('created_at');

        if ($request->filled('invoice_type')) {
            $query->where('invoice_type', $request->invoice_type);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->partner_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('partner', fn ($p) => $p->where('name', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->paginate(25)->withQueryString();
        $partners = Partner::orderBy('name')->get();

        $types    = [
            Invoice::TYPE_PROFORMA     => 'Proforma',
            Invoice::TYPE_FINAL        => 'Final',
            Invoice::TYPE_CREDIT_NOTE  => 'Credit Note',
            Invoice::TYPE_DEBIT_NOTE   => 'Debit Note',
            Invoice::TYPE_CANCELLATION => 'Cancellation',
        ];
        $statuses = ['UNPAID', 'PARTIAL', 'PAID', 'OVERDUE', 'VOID'];

        return view('billing-invoices.index', compact('invoices', 'partners', 'types', 'statuses'));
    }

    /**
     * D2 — Invoice detail with line items.
     */
    public function show(Invoice $billingInvoice)
    {
        $billingInvoice->load([
            'partner',
            'items',
            'parentInvoice',
            'replacesInvoice',
            'childInvoices',
            'allocations.payment',
        ]);

        return view('billing-invoices.show', compact('billingInvoice'));
    }

    /**
     * D2 — Mark invoice as SENT and dispatch email.
     */
    public function send(Request $request, Invoice $billingInvoice)
    {
        if ($billingInvoice->payment_status === 'VOID') {
            return back()->with('error', 'Cannot send a VOID invoice.');
        }

        $billingInvoice->update([
            'sent_at'    => now(),
            'updated_by' => Auth::id(),
        ]);

        SendInvoiceEmailJob::dispatch($billingInvoice->id);

        return back()->with('success', "Invoice {$billingInvoice->invoice_no} marked as sent and email queued.");
    }

    /**
     * D2 — Propose void (Step 1 — Finance).
     */
    public function void(Request $request, Invoice $billingInvoice)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $this->voidService->propose($billingInvoice, Auth::id(), $validated['reason']);
            return back()->with('success', "Void proposal submitted for invoice {$billingInvoice->invoice_no}. Awaiting senior approval.");
        } catch (LogicException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * D2 — Approve void (Step 2 — Senior Finance / Admin only).
     */
    public function approveVoid(Request $request, Invoice $billingInvoice)
    {
        try {
            $this->voidService->approve($billingInvoice, Auth::id());
            return back()->with('success', "Invoice {$billingInvoice->invoice_no} voided successfully.");
        } catch (LogicException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * D2 — Cancel a pending void proposal.
     */
    public function cancelVoid(Request $request, Invoice $billingInvoice)
    {
        try {
            $this->voidService->cancelProposal($billingInvoice);
            return back()->with('success', "Void proposal for invoice {$billingInvoice->invoice_no} cancelled.");
        } catch (LogicException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * D2 — Download invoice as PDF.
     */
    public function download(Invoice $billingInvoice)
    {
        $billingInvoice->load(['partner', 'items', 'parentInvoice']);

        $pdf = Pdf::loadView('billing-invoices.pdf', compact('billingInvoice'))
            ->setPaper('A4', 'portrait');

        $filename = "invoice-{$billingInvoice->invoice_no}.pdf";

        return $pdf->download($filename);
    }
}
