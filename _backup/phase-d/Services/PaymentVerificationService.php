<?php

namespace App\Services;

use App\Models\Payment;
use LogicException;

/**
 * Finance verifies or rejects a recorded payment.
 *
 * Verification status is stored in payment notes (field reuse until a
 * dedicated `status` column is added to the payments table in a future migration).
 *
 * Tag format in notes: [VERIFIED by #{userId} at {datetime}]
 *                      [REJECTED by #{userId} at {datetime}: {reason}]
 */
class PaymentVerificationService
{
    public function verify(Payment $payment, int $verifiedBy): void
    {
        $this->guardNotAlreadyProcessed($payment);

        $tag = "[VERIFIED by #{$verifiedBy} at " . now()->toDateTimeString() . "]";

        $payment->update([
            'notes' => trim(($payment->notes ?? '') . "\n" . $tag),
        ]);
    }

    public function reject(Payment $payment, int $rejectedBy, string $reason): void
    {
        $this->guardNotAlreadyProcessed($payment);

        $tag = "[REJECTED by #{$rejectedBy} at " . now()->toDateTimeString() . ": {$reason}]";

        $payment->update([
            'notes' => trim(($payment->notes ?? '') . "\n" . $tag),
        ]);

        // Zero out unallocated so this payment cannot be allocated
        $payment->update(['amount_unallocated' => 0]);
    }

    public function isVerified(Payment $payment): bool
    {
        return str_contains($payment->notes ?? '', '[VERIFIED by #');
    }

    public function isRejected(Payment $payment): bool
    {
        return str_contains($payment->notes ?? '', '[REJECTED by #');
    }

    private function guardNotAlreadyProcessed(Payment $payment): void
    {
        if ($this->isVerified($payment)) {
            throw new LogicException("Payment #{$payment->id} is already verified.");
        }
        if ($this->isRejected($payment)) {
            throw new LogicException("Payment #{$payment->id} is already rejected.");
        }
    }
}
