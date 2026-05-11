<?php

namespace App\Listeners;

use App\Events\InvoiceIssued;
use App\Jobs\SendInvoiceEmailJob;
use Illuminate\Support\Facades\Log;

/**
 * C2 — Listener: DispatchInvoiceEmailOnIssued
 *
 * Handles the InvoiceIssued event.
 * Dispatches SendInvoiceEmailJob to the notifications queue.
 *
 * NOTE: Named in the TODO as "SendInvoiceEmailJob::dispatch()" directly,
 * but following best practice we route through a named Listener class
 * so the event→action mapping is explicit in EventServiceProvider.
 */
class DispatchInvoiceEmailOnIssued
{
    public function handle(InvoiceIssued $event): void
    {
        Log::channel('queue')->info('[DispatchInvoiceEmailOnIssued] Dispatching email job', [
            'invoice_id' => $event->invoice->id,
            'issued_by'  => $event->issuedByUserId,
        ]);

        SendInvoiceEmailJob::dispatch($event->invoice);
    }
}
