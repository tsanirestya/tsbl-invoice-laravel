<?php

namespace App\Observers;

use App\Models\DsiTransaction;
use App\Services\AuditLogService;
use LogicException;

/**
 * C3 — DsiTransactionObserver
 *
 * Registered in AppServiceProvider.
 *
 * Enforces the immutability contract:
 * Once a DsiTransaction is locked (is_locked = true), NO field may be modified.
 * This is the core data integrity guarantee for the DSI reconciliation system.
 *
 * A locked DSI transaction means it has been matched to a reconciliation.
 * Any retrospective change would corrupt the reconciliation audit trail.
 */
class DsiTransactionObserver
{
    public function __construct(
        private readonly AuditLogService $auditLog
    ) {}

    /**
     * Audit log on creation.
     */
    public function created(DsiTransaction $transaction): void
    {
        $this->auditLog->log(
            $transaction,
            'dsi_transaction_imported',
            [],
            [
                'import_batch_id' => $transaction->import_batch_id,
                'ref_no'          => $transaction->ref_no ?? 'n/a',
                'amount'          => $transaction->amount ?? 0,
                'is_locked'       => false,
            ]
        );
    }

    /**
     * Block ALL updates when is_locked = true.
     * Exception: the lock operation itself (setting is_locked from false → true) is allowed.
     */
    public function updating(DsiTransaction $transaction): void
    {
        // Was it already locked before this update?
        if (!$transaction->getOriginal('is_locked')) {
            // Not yet locked — allow the update (might be the lock operation itself)
            return;
        }

        // It WAS locked — block everything
        throw new LogicException(
            "DsiTransaction #{$transaction->id} is locked by a reconciliation and cannot be modified. " .
            "Dirty fields: " . implode(', ', array_keys($transaction->getDirty()))
        );
    }

    /**
     * Audit log when a DSI transaction is locked (reconciliation matched).
     */
    public function updated(DsiTransaction $transaction): void
    {
        $dirty = $transaction->getChanges();

        // If this update set is_locked = true, emit a specific audit event
        if (isset($dirty['is_locked']) && $dirty['is_locked']) {
            $this->auditLog->log(
                $transaction,
                'dsi_transaction_locked',
                ['is_locked' => false],
                ['is_locked' => true, 'locked_at' => now()->toDateTimeString()]
            );
            return;
        }

        // General update audit (only if not locked — double guard)
        $meaningfulFields = array_diff(array_keys($dirty), ['updated_at']);
        if (!empty($meaningfulFields)) {
            $old = [];
            foreach ($meaningfulFields as $field) {
                $old[$field] = $transaction->getOriginal($field);
            }

            $this->auditLog->log(
                $transaction,
                'dsi_transaction_updated',
                $old,
                array_intersect_key($dirty, array_flip($meaningfulFields))
            );
        }
    }

    /**
     * Block deletion of locked DSI transactions.
     */
    public function deleting(DsiTransaction $transaction): void
    {
        if ($transaction->is_locked) {
            throw new LogicException(
                "DsiTransaction #{$transaction->id} is locked and cannot be deleted."
            );
        }
    }

    public function deleted(DsiTransaction $transaction): void
    {
        $this->auditLog->log(
            $transaction,
            'dsi_transaction_deleted',
            ['import_batch_id' => $transaction->import_batch_id, 'ref_no' => $transaction->ref_no ?? 'n/a'],
            []
        );
    }
}
