<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Reconciliation;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Human-review flow for Reconciliation records.
 *
 * States: PENDING_REVIEW → APPROVED | DISPUTED | REJECTED
 *
 * On APPROVED: triggers final invoice generation (caller must dispatch GenerateFinalInvoiceJob).
 * On DISPUTED: record dispute reason — awaits further action.
 * On REJECTED: unlocks the DSI transaction so it can be re-matched.
 */
class ReconciliationApprovalService
{
    public function __construct(
        private readonly InvoiceCreatorService $invoiceCreator
    ) {}

    /**
     * Finance reviews and approves the reconciliation.
     * Creates the Final Invoice based on DSI amount + no-show charge.
     */
    public function approve(Reconciliation $reconciliation, int $reviewedBy): Invoice
    {
        $this->guardPendingReview($reconciliation);

        return DB::transaction(function () use ($reconciliation, $reviewedBy) {
            $finalAmount = $this->resolveFinalAmount($reconciliation);

            $proforma    = $reconciliation->proformaInvoice;
            $items       = $this->buildFinalLineItems($reconciliation, $finalAmount);

            $finalInvoice = $this->invoiceCreator->createFinal(
                [
                    'partner_id'         => $proforma->partner_id,
                    'invoice_date'       => now()->toDateString(),
                    'due_date'           => now()->addDays($proforma->partner->payment_due_days ?? 30)->toDateString(),
                    'grand_total'        => $finalAmount,
                    'subtotal'           => $finalAmount,
                    'deposit'            => 0,
                    'source_type'        => $proforma->source_type,
                    'source_id'          => $proforma->source_id,
                    'delta_amount'       => $reconciliation->delta_amount,
                    'notes'              => "Reconciled from proforma {$proforma->invoice_no}",
                ],
                $items,
                $reviewedBy,
                $proforma->id
            );

            $reconciliation->update([
                'status'         => 'APPROVED',
                'reviewed_by'    => $reviewedBy,
                'reviewed_at'    => now(),
                'approved_at'    => now(),
                'final_invoice_id' => $finalInvoice->id,
            ]);

            // Transfer payment allocations from proforma to final invoice
            $proforma->allocations()->update(['invoice_id' => $finalInvoice->id]);

            // Refresh and recalc statuses
            $proforma->refresh()->recalcStatus();
            $finalInvoice->refresh()->recalcStatus();

            return $finalInvoice;
        });
    }

    /**
     * Finance disputes the reconciliation — flags for further review.
     */
    public function dispute(Reconciliation $reconciliation, int $reviewedBy, string $reason): void
    {
        $this->guardPendingReview($reconciliation);

        $reconciliation->update([
            'status'         => 'DISPUTED',
            'reviewed_by'    => $reviewedBy,
            'reviewed_at'    => now(),
            'dispute_reason' => $reason,
        ]);
    }

    /**
     * Finance rejects the reconciliation — unlocks DSI so it can be re-matched.
     */
    public function reject(Reconciliation $reconciliation, int $reviewedBy, string $reason): void
    {
        $this->guardPendingReview($reconciliation);

        DB::transaction(function () use ($reconciliation, $reviewedBy, $reason) {
            // Unlock DSI transaction so it can be matched to a different reservation
            $reconciliation->dsiTransaction?->update(['is_locked' => false]);

            $reconciliation->update([
                'status'         => 'REJECTED',
                'reviewed_by'    => $reviewedBy,
                'reviewed_at'    => now(),
                'dispute_reason' => $reason,
            ]);
        });
    }

    private function guardPendingReview(Reconciliation $reconciliation): void
    {
        if ($reconciliation->status !== 'PENDING_REVIEW') {
            throw new LogicException(
                "Reconciliation #{$reconciliation->id} is not PENDING_REVIEW (current: {$reconciliation->status})."
            );
        }
    }

    private function resolveFinalAmount(Reconciliation $reconciliation): float
    {
        // If no-show policy applied, use the no-show charge amount
        if ($reconciliation->no_show_policy_applied) {
            return (float) $reconciliation->no_show_charge_amount;
        }

        // Otherwise use DSI amount (what was actually charged)
        return (float) $reconciliation->dsi_amount;
    }

    private function buildFinalLineItems(Reconciliation $reconciliation, float $finalAmount): array
    {
        $dsiLines = $reconciliation->dsiLines;

        if ($dsiLines->isNotEmpty()) {
            return $dsiLines->map(fn ($line, $i) => [
                'product_name'  => $line->description,
                'pax'           => $line->quantity,
                'price_per_pax' => $line->unit_price,
                'amount'        => $line->amount,
                'sort_order'    => $i + 1,
                'dsi_line_item_id' => $line->id,
            ])->toArray();
        }

        // Fallback: single line from reconciliation amounts
        return [[
            'product_name'  => 'Reconciled services per DSI',
            'pax'           => 1,
            'price_per_pax' => $finalAmount,
            'amount'        => $finalAmount,
            'sort_order'    => 1,
        ]];
    }
}
