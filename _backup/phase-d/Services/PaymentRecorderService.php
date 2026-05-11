<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Records an incoming payment against an invoice.
 * Does NOT allocate — that is handled by PaymentAllocatorService.
 * Sets amount_unallocated = full amount on creation.
 */
class PaymentRecorderService
{
    /**
     * Record a payment for an invoice.
     *
     * @param  array $data  Keys: payment_date, payment_method, reference_no, proof_file, notes
     * @param  float $amount
     * @param  int   $createdBy
     */
    public function record(Invoice $invoice, array $data, float $amount, int $createdBy): Payment
    {
        if ($invoice->payment_status === 'VOID') {
            throw new LogicException("Cannot record payment on a VOID invoice.");
        }

        if ($invoice->payment_status === 'PAID') {
            throw new LogicException("Invoice #{$invoice->id} is already fully paid.");
        }

        return DB::transaction(function () use ($invoice, $data, $amount, $createdBy) {
            $payment = Payment::create(array_merge($data, [
                'invoice_id'         => $invoice->id,
                'amount'             => $amount,
                'amount_allocated'   => 0,
                'amount_unallocated' => $amount,
                'created_by'         => $createdBy,
            ]));

            // Recalc invoice status after new payment recorded
            $invoice->recalcStatus();

            return $payment;
        });
    }
}
