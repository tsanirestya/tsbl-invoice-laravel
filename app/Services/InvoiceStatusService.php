<?php

namespace App\Services;

use App\Models\Invoice;
use LogicException;

/**
 * State machine for invoice_type-aware payment_status transitions.
 *
 * Allowed payment_status values: UNPAID | PARTIAL | PAID | OVERDUE | VOID
 * Allowed transitions per type:
 *   PROFORMA:     UNPAID → PARTIAL → PAID → OVERDUE (via cron)
 *   FINAL:        UNPAID → PARTIAL → PAID → OVERDUE (via cron)
 *   CREDIT_NOTE:  UNPAID → PAID (single settlement)
 *   DEBIT_NOTE:   UNPAID → PARTIAL → PAID → OVERDUE
 *   CANCELLATION: always VOID (set on creation)
 */
class InvoiceStatusService
{
    private const TRANSITIONS = [
        'UNPAID'  => ['PARTIAL', 'PAID', 'OVERDUE', 'VOID'],
        'PARTIAL' => ['PAID', 'OVERDUE', 'VOID'],
        'PAID'    => [],                        // terminal — no further transitions
        'OVERDUE' => ['PARTIAL', 'PAID', 'VOID'],
        'VOID'    => [],                        // terminal
    ];

    public function transition(Invoice $invoice, string $newStatus): void
    {
        $current = $invoice->payment_status ?? 'UNPAID';

        $allowed = self::TRANSITIONS[$current] ?? [];

        if (!in_array($newStatus, $allowed, true)) {
            throw new LogicException(
                "Invoice #{$invoice->id} ({$invoice->invoice_type}): cannot transition from {$current} to {$newStatus}."
            );
        }

        if ($invoice->is_locked && $newStatus !== 'VOID') {
            throw new LogicException("Invoice #{$invoice->id} is locked — status change blocked.");
        }

        $invoice->update(['payment_status' => $newStatus]);
    }

    /**
     * Recalculate status from actual payment data.
     * Safe to call after any payment event.
     */
    public function recalculate(Invoice $invoice): void
    {
        // Void and Cancellation invoices are terminal — never auto-recalc
        if (in_array($invoice->payment_status, ['VOID'], true)) {
            return;
        }
        if ($invoice->invoice_type === Invoice::TYPE_CANCELLATION) {
            return;
        }

        $invoice->recalcStatus();
    }

    public function markVoid(Invoice $invoice): void
    {
        if ($invoice->invoice_type === Invoice::TYPE_FINAL) {
            throw new LogicException('FINAL invoices cannot be voided — issue a Credit Note or Debit Note instead.');
        }

        $invoice->update([
            'payment_status' => 'VOID',
            'is_locked'      => true,
            'lock_reason'    => 'Voided by finance.',
        ]);
    }
}
