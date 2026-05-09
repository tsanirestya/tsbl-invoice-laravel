<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateFinalInvoiceJob;
use App\Models\Reconciliation;
use App\Services\ReconciliationApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use LogicException;

class ReconciliationController extends Controller
{
    public function __construct(
        private readonly ReconciliationApprovalService $approvalService
    ) {}

    /**
     * D4 — List reconciliations, default: PENDING_REVIEW first.
     */
    public function index(Request $request)
    {
        $query = Reconciliation::with(['reservation', 'proformaInvoice.partner', 'dsiTransaction'])
            ->orderByRaw("FIELD(status, 'PENDING_REVIEW', 'DISPUTED', 'APPROVED', 'REJECTED')")
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('partner_id')) {
            $query->whereHas('proformaInvoice', fn ($q) => $q->where('partner_id', $request->partner_id));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('dsiTransaction', fn ($dsi) => $dsi->where('ref_no', 'like', "%{$search}%"))
                  ->orWhereHas('proformaInvoice', fn ($inv) => $inv->where('invoice_no', 'like', "%{$search}%"));
            });
        }

        $reconciliations = $query->paginate(20)->withQueryString();
        $statuses        = ['PENDING_REVIEW', 'APPROVED', 'DISPUTED', 'REJECTED'];

        return view('reconciliations.index', compact('reconciliations', 'statuses'));
    }

    /**
     * D4 — Detail: proforma vs DSI comparison view.
     */
    public function show(Reconciliation $reconciliation)
    {
        $reconciliation->load([
            'reservation',
            'proformaInvoice.partner',
            'proformaInvoice.items',
            'dsiTransaction.lineItems',
            'finalInvoice',
            'dsiLines',
        ]);

        return view('reconciliations.show', compact('reconciliation'));
    }

    /**
     * D4 — Finance approves reconciliation → generates Final Invoice.
     */
    public function approve(Request $request, Reconciliation $reconciliation)
    {
        try {
            $finalInvoice = $this->approvalService->approve($reconciliation, Auth::id());

            return redirect()
                ->route('billing-invoices.show', $finalInvoice)
                ->with('success', "Reconciliation approved. Final invoice {$finalInvoice->invoice_no} created.");

        } catch (LogicException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * D4 — Finance disputes reconciliation → flags for further review.
     */
    public function dispute(Request $request, Reconciliation $reconciliation)
    {
        $validated = $request->validate([
            'dispute_reason' => 'required|string|max:2000',
        ]);

        try {
            $this->approvalService->dispute($reconciliation, Auth::id(), $validated['dispute_reason']);
            return back()->with('success', "Reconciliation #{$reconciliation->id} marked as DISPUTED.");
        } catch (LogicException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * D4 — Finance rejects reconciliation → unlocks DSI transaction.
     */
    public function reject(Request $request, Reconciliation $reconciliation)
    {
        $validated = $request->validate([
            'reject_reason' => 'required|string|max:2000',
        ]);

        try {
            $this->approvalService->reject($reconciliation, Auth::id(), $validated['reject_reason']);
            return back()->with('success', "Reconciliation #{$reconciliation->id} rejected. DSI transaction unlocked.");
        } catch (LogicException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
