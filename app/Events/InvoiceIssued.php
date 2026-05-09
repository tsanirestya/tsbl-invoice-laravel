<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * C2 — Event: InvoiceIssued
 *
 * Fired when an invoice is finalized and issued to a partner.
 * Listened by: SendInvoiceEmailJob (dispatched directly).
 */
class InvoiceIssued
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly int $issuedByUserId
    ) {}
}
