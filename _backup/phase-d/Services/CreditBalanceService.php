<?php

namespace App\Services;

use App\Models\CreditBalance;
use App\Models\CreditBalanceUsage;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Manages partner credit balances arising from overpayments.
 *
 * Credits accumulate when payment exceeds invoice amount.
 * Credits can be applied to future invoices (reduces amount owed).
 */
class CreditBalanceService
{
    /**
     * Add credit to a partner's balance (called by PaymentAllocatorService on overpayment).
     */
    public function addCredit(int $partnerId, float $amount, int $paymentId, string $notes = ''): CreditBalance
    {
        return DB::transaction(function () use ($partnerId, $amount, $paymentId, $notes) {
            $balance = $this->getOrCreateBalance($partnerId);

            $balance->update([
                'balance'         => (float) $balance->balance + $amount,
                'last_updated_at' => now(),
            ]);

            CreditBalanceUsage::create([
                'credit_balance_id' => $balance->id,
                'payment_id'        => $paymentId,
                'type'              => 'CREDIT',
                'amount'            => $amount,
                'notes'             => $notes,
            ]);

            return $balance->fresh();
        });
    }

    /**
     * Apply credit balance to an invoice — reduces invoice outstanding balance.
     * Creates a virtual payment allocation note; does NOT create a Payment record
     * (credit is not cash — it's an internal offset).
     *
     * @throws LogicException if insufficient balance or invoice already paid
     */
    public function applyToInvoice(Invoice $invoice, float $amount, int $appliedBy): float
    {
        return DB::transaction(function () use ($invoice, $amount, $appliedBy) {
            $balance = CreditBalance::where('partner_id', $invoice->partner_id)
                ->lockForUpdate()
                ->firstOrFail();

            $available = (float) $balance->balance;

            if ($available < $amount) {
                throw new LogicException(
                    "Insufficient credit balance. Available: {$available}, requested: {$amount}."
                );
            }

            if ($invoice->payment_status === 'PAID') {
                throw new LogicException("Invoice #{$invoice->id} is already PAID.");
            }
            if ($invoice->payment_status === 'VOID') {
                throw new LogicException("Cannot apply credit to a VOID invoice.");
            }

            $balance->update([
                'balance'         => $available - $amount,
                'last_updated_at' => now(),
            ]);

            CreditBalanceUsage::create([
                'credit_balance_id' => $balance->id,
                'invoice_id'        => $invoice->id,
                'type'              => 'DEBIT',
                'amount'            => $amount,
                'notes'             => "Credit applied to invoice #{$invoice->id} by user #{$appliedBy}",
                'created_by'        => $appliedBy,
            ]);

            // Recalc invoice status — credit application may fully settle it
            $invoice->recalcStatus();

            return (float) $balance->balance;
        });
    }

    /**
     * Get current credit balance for a partner.
     */
    public function getBalance(int $partnerId): float
    {
        $balance = CreditBalance::where('partner_id', $partnerId)->first();
        return $balance ? (float) $balance->balance : 0.0;
    }

    private function getOrCreateBalance(int $partnerId): CreditBalance
    {
        return CreditBalance::firstOrCreate(
            ['partner_id' => $partnerId],
            ['balance' => 0, 'last_updated_at' => now()]
        );
    }
}
