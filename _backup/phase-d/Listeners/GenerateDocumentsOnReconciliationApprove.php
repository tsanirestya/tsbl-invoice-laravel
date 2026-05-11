<?php

namespace App\Listeners;

use App\Events\ReconciliationApproved;
use App\Jobs\GenerateFinalInvoiceJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * C2 — Listener: GenerateDocumentsOnReconciliationApprove
 *
 * Handles the ReconciliationApproved event.
 * Dispatches GenerateFinalInvoiceJob to the financial-critical queue.
 *
 * Intentionally kept thin — all document generation logic lives in the job.
 */
class GenerateDocumentsOnReconciliationApprove implements ShouldQueue
{
    public string $queue = 'financial-critical';
    public int $tries    = 3;

    public function handle(ReconciliationApproved $event): void
    {
        Log::channel('queue')->info('[GenerateDocumentsOnReconciliationApprove] Dispatching GenerateFinalInvoiceJob', [
            'reconciliation_id' => $event->reconciliation->id,
            'approved_by'       => $event->approvedByUserId,
        ]);

        GenerateFinalInvoiceJob::dispatch(
            $event->reconciliation,
            $event->approvedByUserId
        );
    }
}
