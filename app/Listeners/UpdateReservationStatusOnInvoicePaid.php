<?php

namespace App\Listeners;

use App\Events\InvoiceFullyPaid;
use App\Models\Reservation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * C2 — Listener: UpdateReservationStatusOnInvoicePaid
 *
 * Handles the InvoiceFullyPaid event.
 * When a FINAL invoice is fully paid, marks the linked reservation as COMPLETED.
 */
class UpdateReservationStatusOnInvoicePaid implements ShouldQueue
{
    public string $queue = 'default';
    public int $tries    = 3;

    public function handle(InvoiceFullyPaid $event): void
    {
        $invoice = $event->invoice;

        // Only act on FINAL invoices (not PROFORMA or CN/DN)
        if ($invoice->invoice_type !== \App\Models\Invoice::TYPE_FINAL) {
            return;
        }

        // Find the associated reservation via source polymorphic link
        if ($invoice->source_type !== Reservation::class || !$invoice->source_id) {
            return;
        }

        $reservation = Reservation::find($invoice->source_id);

        if (!$reservation) {
            Log::channel('queue')->warning('[UpdateReservationStatusOnInvoicePaid] Reservation not found', [
                'invoice_id'   => $invoice->id,
                'source_id'    => $invoice->source_id,
            ]);
            return;
        }

        if ($reservation->status === 'COMPLETED') {
            return; // Already completed
        }

        $reservation->update(['status' => 'COMPLETED']);

        Log::channel('queue')->info('[UpdateReservationStatusOnInvoicePaid] Reservation marked COMPLETED', [
            'reservation_id' => $reservation->id,
            'invoice_id'     => $invoice->id,
        ]);
    }
}
