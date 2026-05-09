<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Two-step void flow:
 *   Step 1 — Finance proposes void: sets void_proposed_at + void_proposed_by on invoice
 *   Step 2 — Senior Finance approves: actually voids + locks
 *
 * Only PROFORMA invoices can be voided via this flow.
 * CANCELLATION invoices are created via InvoiceCreatorService::createCancellation().
 */
class InvoiceVoidService
{
    public function __construct(
        private readonly InvoiceStatusService $statusService
    ) {}

    /**
     * Step 1: Finance proposes void.
     * Stores proposal metadata — does NOT change payment_status yet.
     */
    public function propose(Invoice $invoice, int $proposedBy, string $reason): void
    {
        $this->guardVoidable($invoice);

        if ($invoice->is_locked) {
            throw new LogicException("Invoice #{$invoice->id} is locked.");
        }

        $invoice->update([
            'notes' => trim(($invoice->notes ?? '') . "\n[VOID PROPOSED] {$reason}"),
            // Piggyback lock_reason as staging area for the proposal
            'lock_reason' => "PROPOSED_VOID:{$proposedBy}:" . now()->toDateTimeString(),
        ]);
    }

    /**
     * Step 2: Senior Finance approves the void proposal.
     * Checks proposer ≠ approver, then executes void.
     */
    public function approve(Invoice $invoice, int $approvedBy): void
    {
        $this->guardVoidable($invoice);

        if (!$invoice->lock_reason || !str_starts_with($invoice->lock_reason, 'PROPOSED_VOID:')) {
            throw new LogicException("Invoice #{$invoice->id} has no pending void proposal.");
        }

        [, $proposedBy] = explode(':', $invoice->lock_reason);

        if ((int) $proposedBy === $approvedBy) {
            throw new LogicException('Approver cannot be the same user who proposed the void.');
        }

        DB::transaction(function () use ($invoice, $approvedBy) {
            $this->statusService->markVoid($invoice);
            $invoice->update([
                'lock_reason' => "VOIDED by user #{$approvedBy} on " . now()->toDateTimeString(),
                'updated_by'  => $approvedBy,
            ]);
        });
    }

    /**
     * Cancel a pending void proposal (proposer or admin).
     */
    public function cancelProposal(Invoice $invoice): void
    {
        if (!$invoice->lock_reason || !str_starts_with($invoice->lock_reason, 'PROPOSED_VOID:')) {
            throw new LogicException("Invoice #{$invoice->id} has no pending void proposal.");
        }

        $invoice->update(['lock_reason' => null]);
    }

    private function guardVoidable(Invoice $invoice): void
    {
        $voidable = [Invoice::TYPE_PROFORMA];

        if (!in_array($invoice->invoice_type, $voidable, true)) {
            throw new LogicException(
                "Only PROFORMA invoices can be voided. Use CN/DN for FINAL invoices. Got: {$invoice->invoice_type}"
            );
        }

        if ($invoice->payment_status === 'VOID') {
            throw new LogicException("Invoice #{$invoice->id} is already voided.");
        }

        if ($invoice->payment_status === 'PAID') {
            throw new LogicException("Cannot void a PAID invoice.");
        }
    }
}
