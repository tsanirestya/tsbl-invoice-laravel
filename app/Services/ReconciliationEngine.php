<?php

namespace App\Services;

use App\Models\DsiTransaction;
use App\Models\Invoice;
use App\Models\Reconciliation;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Core reconciliation engine.
 * Compares proforma invoice against matched DSI transaction,
 * applies no-show policy, and creates the Reconciliation record.
 *
 * Uses lockForUpdate to prevent concurrent reconciliation of the same reservation.
 */
class ReconciliationEngine
{
    public function __construct(
        private readonly DeltaCalculatorService $deltaCalculator,
        private readonly NoShowPolicyService $noShowPolicy
    ) {}

    /**
     * Reconcile a reservation: compare proforma vs DSI, create Reconciliation record.
     *
     * @throws LogicException on guard failures
     */
    public function reconcile(
        Reservation $reservation,
        Invoice $proforma,
        DsiTransaction $dsi
    ): Reconciliation {
        return DB::transaction(function () use ($reservation, $proforma, $dsi) {
            // Lock reservation row — prevents two workers reconciling same reservation
            $reservation = Reservation::where('id', $reservation->id)->lockForUpdate()->firstOrFail();

            $this->guardPreconditions($reservation, $proforma, $dsi);

            $delta    = $this->deltaCalculator->calculate($proforma, $dsi);
            $partner  = $proforma->partner;
            $noShow   = $this->noShowPolicy->apply($delta, $partner, $reservation);

            $reconciliation = Reconciliation::create([
                'reservation_id'         => $reservation->id,
                'proforma_invoice_id'    => $proforma->id,
                'dsi_transaction_id'     => $dsi->id,
                'status'                 => 'PENDING_REVIEW',
                'proforma_amount'        => $delta['proforma_amount'],
                'dsi_amount'             => $delta['dsi_amount'],
                'delta_amount'           => $delta['delta_amount'],
                'delta_reason'           => $delta['delta_type'],
                'no_show_policy_applied' => $noShow['policy_applied'],
                'no_show_charge_amount'  => $noShow['no_show_charge_amount'],
            ]);

            // Attach DSI line items to reconciliation
            $lineItemIds = $dsi->lineItems()->pluck('id')->toArray();
            if ($lineItemIds) {
                $reconciliation->dsiLines()->sync($lineItemIds);
            }

            // Lock the DSI transaction — it is now part of a reconciliation
            $dsi->update(['is_locked' => true]);

            return $reconciliation;
        });
    }

    private function guardPreconditions(Reservation $reservation, Invoice $proforma, DsiTransaction $dsi): void
    {
        // No double reconciliation
        if (Reconciliation::where('reservation_id', $reservation->id)
            ->whereNotIn('status', ['REJECTED'])
            ->exists()
        ) {
            throw new LogicException("Reservation #{$reservation->id} already has an active reconciliation.");
        }

        if ($proforma->invoice_type !== Invoice::TYPE_PROFORMA) {
            throw new LogicException("Reconciliation requires a PROFORMA invoice, got: {$proforma->invoice_type}");
        }

        if ($dsi->is_locked) {
            throw new LogicException("DSI transaction #{$dsi->id} is already locked by another reconciliation.");
        }

        if ($dsi->duplicateFlags()->where('status', 'PENDING')->exists()) {
            throw new LogicException("DSI transaction #{$dsi->id} has unresolved duplicate flags.");
        }
    }
}
