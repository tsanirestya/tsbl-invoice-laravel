<?php

namespace App\Listeners;

use App\Events\PaymentVerified;
use App\Jobs\ProcessPaymentAllocationJob;
use Illuminate\Support\Facades\Log;

/**
 * C2 — Listener: DispatchPaymentAllocationOnVerified
 *
 * Handles the PaymentVerified event.
 * Dispatches ProcessPaymentAllocationJob to the financial-critical queue.
 */
class DispatchPaymentAllocationOnVerified
{
    public function handle(PaymentVerified $event): void
    {
        Log::channel('queue')->info('[DispatchPaymentAllocationOnVerified] Dispatching allocation job', [
            'payment_id'  => $event->payment->id,
            'verified_by' => $event->verifiedByUserId,
            'invoice_ids' => $event->targetInvoiceIds,
        ]);

        ProcessPaymentAllocationJob::dispatch(
            $event->payment,
            $event->targetInvoiceIds
        );
    }
}
