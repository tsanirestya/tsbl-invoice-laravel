<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\AuditLogService;

/**
 * C3 — PaymentObserver
 *
 * Registered in AppServiceProvider.
 * Audit logs every change to Payment records.
 * Payments should be immutable after verification — observer enforces this.
 */
class PaymentObserver
{
    /**
     * Fields that must NOT be changed after a payment is verified.
     */
    private const IMMUTABLE_AFTER_VERIFICATION = [
        'amount',
        'payment_method',
        'payment_date',
        'invoice_id',
    ];

    public function __construct(
        private readonly AuditLogService $auditLog
    ) {}

    /**
     * Audit log on payment creation.
     */
    public function created(Payment $payment): void
    {
        $this->auditLog->log(
            $payment,
            'payment_created',
            [],
            [
                'amount'         => $payment->amount,
                'payment_method' => $payment->payment_method ?? 'n/a',
                'invoice_id'     => $payment->invoice_id,
                'verified'       => $payment->is_verified ?? false,
            ]
        );
    }

    /**
     * Block mutation of immutable fields after verification.
     */
    public function updating(Payment $payment): void
    {
        // Only guard once verified
        if (!$payment->getOriginal('is_verified')) {
            return;
        }

        $dirtyKeys  = array_keys($payment->getDirty());
        $violations = array_intersect($dirtyKeys, self::IMMUTABLE_AFTER_VERIFICATION);

        if (!empty($violations)) {
            throw new \LogicException(
                "Payment #{$payment->id} is verified and immutable. Cannot change: " . implode(', ', $violations)
            );
        }
    }

    /**
     * Audit log on payment update.
     */
    public function updated(Payment $payment): void
    {
        $dirty = $payment->getChanges();
        $meaningfulFields = array_diff(array_keys($dirty), ['updated_at']);

        if (empty($meaningfulFields)) {
            return;
        }

        $old = [];
        foreach ($meaningfulFields as $field) {
            $old[$field] = $payment->getOriginal($field);
        }

        $this->auditLog->log(
            $payment,
            'payment_updated',
            $old,
            array_intersect_key($dirty, array_flip($meaningfulFields))
        );
    }

    /**
     * Audit log on payment deletion.
     */
    public function deleting(Payment $payment): void
    {
        // Block deletion of verified payments
        if ($payment->is_verified ?? false) {
            throw new \LogicException(
                "Payment #{$payment->id} is verified and cannot be deleted. Use void workflow."
            );
        }
    }

    public function deleted(Payment $payment): void
    {
        $this->auditLog->log(
            $payment,
            'payment_deleted',
            [
                'amount'     => $payment->amount,
                'invoice_id' => $payment->invoice_id,
            ],
            []
        );
    }
}
