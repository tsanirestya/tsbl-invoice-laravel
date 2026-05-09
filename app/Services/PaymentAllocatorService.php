<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Allocates verified payment funds to invoice(s).
 * Uses lockForUpdate to prevent concurrent over-allocation.
 *
 * Over-payment → remainder goes to CreditBalanceService.
 */
class PaymentAllocatorService
{
    public function __construct(
        private readonly CreditBalanceService $creditBalanceService,
        private readonly PaymentVerificationService $verificationService
    ) {}

    /**
     * Allocate a payment to a single invoice.
     * Handles partial allocation and overpayment.
     *
     * @return array{allocated: float, overpayment: float}
     */
    public function allocate(Payment $payment, Invoice $invoice, int $allocatedBy, ?string $notes = null): array
    {
        if (!$this->verificationService->isVerified($payment)) {
            throw new LogicException("Payment #{$payment->id} must be verified before allocation.");
        }

        return DB::transaction(function () use ($payment, $invoice, $allocatedBy, $notes) {
            // Lock both rows to prevent concurrent allocation
            $payment = Payment::where('id', $payment->id)->lockForUpdate()->firstOrFail();
            $invoice = Invoice::where('id', $invoice->id)->lockForUpdate()->firstOrFail();

            if ($invoice->payment_status === 'VOID') {
                throw new LogicException("Cannot allocate to a VOID invoice.");
            }
            if ($invoice->payment_status === 'PAID') {
                throw new LogicException("Invoice #{$invoice->id} is already PAID.");
            }

            $unallocated   = (float) $payment->amount_unallocated;
            $invoiceOwed   = (float) $invoice->grand_total - $this->totalAllocatedToInvoice($invoice);

            if ($unallocated <= 0) {
                throw new LogicException("Payment #{$payment->id} has no unallocated funds.");
            }
            if ($invoiceOwed <= 0) {
                throw new LogicException("Invoice #{$invoice->id} has no outstanding balance.");
            }

            $toAllocate  = min($unallocated, $invoiceOwed);
            $overpayment = max(0, round($unallocated - $invoiceOwed, 2));

            PaymentAllocation::create([
                'payment_id'       => $payment->id,
                'invoice_id'       => $invoice->id,
                'amount_allocated' => $toAllocate,
                'allocated_by'     => $allocatedBy,
                'notes'            => $notes,
            ]);

            $payment->update([
                'amount_allocated'   => (float) $payment->amount_allocated + $toAllocate,
                'amount_unallocated' => $overpayment,
            ]);

            $invoice->recalcStatus();

            // Route overpayment to credit balance
            if ($overpayment > 0) {
                $this->creditBalanceService->addCredit(
                    $invoice->partner_id,
                    $overpayment,
                    $payment->id,
                    "Overpayment from payment #{$payment->id} on invoice #{$invoice->id}"
                );
            }

            return [
                'allocated'   => $toAllocate,
                'overpayment' => $overpayment,
            ];
        });
    }

    private function totalAllocatedToInvoice(Invoice $invoice): float
    {
        return (float) $invoice->allocations()->sum('amount_allocated');
    }
}
