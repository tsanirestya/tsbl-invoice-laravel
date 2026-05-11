<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\AuditLogService;
use LogicException;

/**
 * C3 — InvoiceObserver
 *
 * Registered in AppServiceProvider.
 *
 * Responsibilities:
 * 1. Block any amount/type mutation when invoice is_locked = true
 * 2. Audit log every state change (create, update, delete)
 */
class InvoiceObserver
{
    /**
     * Fields that CANNOT be changed when invoice is locked.
     */
    private const LOCKED_GUARD_FIELDS = [
        'grand_total',
        'subtotal',
        'tax',
        'discount',
        'invoice_type',
        'partner_id',
        'invoice_no',
        'items',
    ];

    public function __construct(
        private readonly AuditLogService $auditLog
    ) {}

    /**
     * Called before saving a new invoice.
     */
    public function creating(Invoice $invoice): void
    {
        // Nothing to guard on create
    }

    /**
     * Called after creating.
     */
    public function created(Invoice $invoice): void
    {
        $this->auditLog->log(
            $invoice,
            'invoice_created',
            [],
            [
                'invoice_no'   => $invoice->invoice_no,
                'invoice_type' => $invoice->invoice_type,
                'grand_total'  => $invoice->grand_total,
                'partner_id'   => $invoice->partner_id,
            ]
        );
    }

    /**
     * Called BEFORE updating — block mutations on locked invoices.
     */
    public function updating(Invoice $invoice): void
    {
        // Skip lock check if we're the ones setting is_locked
        if (!$invoice->is_locked) {
            return;
        }

        // Check if any guarded financial field is dirty
        $dirtyKeys = array_keys($invoice->getDirty());
        $violations = array_intersect($dirtyKeys, self::LOCKED_GUARD_FIELDS);

        if (!empty($violations)) {
            throw new LogicException(
                "Invoice #{$invoice->id} is locked. Cannot modify: " . implode(', ', $violations)
            );
        }
    }

    /**
     * Called after updating — audit log the change.
     */
    public function updated(Invoice $invoice): void
    {
        $dirty = $invoice->getChanges();

        // Don't log trivial timestamp-only updates
        $meaningfulFields = array_diff(array_keys($dirty), ['updated_at']);
        if (empty($meaningfulFields)) {
            return;
        }

        $old = [];
        foreach ($meaningfulFields as $field) {
            $old[$field] = $invoice->getOriginal($field);
        }

        $this->auditLog->log(
            $invoice,
            'invoice_updated',
            $old,
            array_intersect_key($dirty, array_flip($meaningfulFields))
        );
    }

    /**
     * Called before deleting — block deletion of locked or finalized invoices.
     */
    public function deleting(Invoice $invoice): void
    {
        if ($invoice->is_locked) {
            throw new LogicException(
                "Invoice #{$invoice->id} is locked and cannot be deleted."
            );
        }

        if ($invoice->is_finalized) {
            throw new LogicException(
                "Invoice #{$invoice->id} is finalized. Use void workflow instead of delete."
            );
        }
    }

    /**
     * Called after deleting — audit log the deletion.
     */
    public function deleted(Invoice $invoice): void
    {
        $this->auditLog->log(
            $invoice,
            'invoice_deleted',
            [
                'invoice_no'   => $invoice->invoice_no,
                'invoice_type' => $invoice->invoice_type,
                'grand_total'  => $invoice->grand_total,
            ],
            []
        );
    }
}
