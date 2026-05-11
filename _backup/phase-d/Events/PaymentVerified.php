<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * C2 — Event: PaymentVerified
 *
 * Fired when a finance user verifies an incoming payment.
 * Listened by: ProcessPaymentAllocationJob (dispatched via listener).
 */
class PaymentVerified
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Payment $payment,
        public readonly int $verifiedByUserId,
        public readonly array $targetInvoiceIds = []
    ) {}
}
