<?php

namespace App\Listeners;

use App\Events\DSIImported;
use App\Jobs\GenerateReconciliationJob;
use App\Models\DsiTransaction;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Services\DsiMatcherService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * C2 — Listener: TriggerReconciliationOnDsiImport
 *
 * Handles the DSIImported event.
 * For each new DSI transaction in the batch, attempts to match it to a reservation.
 * If a proforma invoice exists for the reservation, dispatches GenerateReconciliationJob.
 *
 * Runs asynchronously (ShouldQueue) so the import response is not blocked.
 */
class TriggerReconciliationOnDsiImport implements ShouldQueue
{
    public string $queue = 'reconciliation';
    public int $tries    = 3;

    public function __construct(
        private readonly DsiMatcherService $matcher
    ) {}

    public function handle(DSIImported $event): void
    {
        $batch = $event->batch;

        Log::channel('queue')->info('[TriggerReconciliationOnDsiImport] Processing batch', [
            'batch_id' => $batch->id,
        ]);

        // Load all completed (non-duplicate) transactions from this batch
        $transactions = DsiTransaction::where('import_batch_id', $batch->id)
            ->where('is_locked', false)
            ->whereDoesntHave('duplicateFlags', fn($q) => $q->where('status', 'PENDING'))
            ->get();

        $dispatched = 0;

        foreach ($transactions as $dsiTx) {
            // Try to match to a reservation
            $reservation = $this->matcher->match($dsiTx);

            if (!$reservation) {
                Log::channel('queue')->debug('[TriggerReconciliationOnDsiImport] No reservation match', [
                    'dsi_transaction_id' => $dsiTx->id,
                    'ref_no'             => $dsiTx->ref_no ?? 'n/a',
                ]);
                continue;
            }

            // Find the proforma invoice for this reservation
            $proforma = Invoice::where('source_type', Reservation::class)
                ->where('source_id', $reservation->id)
                ->where('invoice_type', Invoice::TYPE_PROFORMA)
                ->whereNotIn('payment_status', ['VOID'])
                ->first();

            if (!$proforma) {
                Log::channel('queue')->debug('[TriggerReconciliationOnDsiImport] No proforma found', [
                    'reservation_id' => $reservation->id,
                ]);
                continue;
            }

            GenerateReconciliationJob::dispatch($reservation, $proforma, $dsiTx);
            $dispatched++;
        }

        Log::channel('queue')->info('[TriggerReconciliationOnDsiImport] Done', [
            'batch_id'   => $batch->id,
            'dispatched' => $dispatched,
        ]);
    }
}
