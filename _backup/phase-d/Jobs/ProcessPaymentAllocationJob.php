<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\AuditLogService;
use App\Services\CreditBalanceService;
use App\Services\PaymentAllocatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * C1 — ProcessPaymentAllocationJob
 *
 * Triggered after a payment is verified by finance.
 * Allocates the verified payment amount to outstanding invoice(s)
 * and handles any remaining overpayment as credit balance.
 *
 * Uses PaymentAllocatorService (which uses lockForUpdate internally)
 * to prevent race conditions when two payments land simultaneously.
 *
 * Queue: financial-critical
 */
class ProcessPaymentAllocationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 30;
    public int $timeout = 120;

    public function __construct(
        private readonly Payment $payment,
        private readonly array $invoiceIds = [] // specific invoices to allocate to (optional)
    ) {}

    public function queue(): string
    {
        return 'financial-critical';
    }

    public function handle(
        PaymentAllocatorService $allocator,
        CreditBalanceService $creditBalance,
        AuditLogService $auditLog
    ): void {
        Log::channel('queue')->info('[ProcessPaymentAllocationJob] Starting', [
            'payment_id' => $this->payment->id,
            'amount'     => $this->payment->amount,
            'partner_id' => $this->payment->partner_id ?? 'n/a',
        ]);

        $result = $allocator->allocate(
            payment: $this->payment,
            invoiceIds: $this->invoiceIds
        );

        // Handle overpayment — store as credit balance
        if ($result['unallocated'] > 0) {
            $creditBalance->addCredit(
                partnerId: $this->payment->invoice->partner_id,
                amount: $result['unallocated'],
                sourcePaymentId: $this->payment->id,
                notes: 'Overpayment credit from Payment #' . $this->payment->id
            );

            Log::channel('queue')->info('[ProcessPaymentAllocationJob] Overpayment credited', [
                'payment_id'  => $this->payment->id,
                'credit_amount' => $result['unallocated'],
            ]);
        }

        $auditLog->log($this->payment, 'payment_allocated', [], [
            'allocated'   => $result['allocated'],
            'unallocated' => $result['unallocated'],
            'invoices'    => $result['invoice_ids'],
        ]);

        Log::channel('queue')->info('[ProcessPaymentAllocationJob] Completed', [
            'payment_id' => $this->payment->id,
            'allocated'  => $result['allocated'],
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::channel('queue')->error('[ProcessPaymentAllocationJob] Failed', [
            'payment_id' => $this->payment->id,
            'error'      => $exception->getMessage(),
        ]);
    }
}
