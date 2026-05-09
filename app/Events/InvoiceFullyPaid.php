<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * C2 — Event: InvoiceFullyPaid
 *
 * Fired when a payment allocation causes an invoice to reach PAID status.
 * Listened by: UpdateReservationStatusOnInvoicePaid
 */
class InvoiceFullyPaid
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Invoice $invoice
    ) {}
}
