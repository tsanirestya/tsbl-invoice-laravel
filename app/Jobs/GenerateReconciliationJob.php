<?php

namespace App\Jobs;

use App\Models\DsiTransaction;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Services\ReconciliationEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * C1 — GenerateReconciliationJob
 *
 * Triggered after a DSI import batch is completed.
 * Matches the imported DSI transaction to a reservation's proforma invoice
 * and invokes the ReconciliationEngine to create a Reconciliation record.
 *
 * ShouldBeUnique per reservation_id — prevents duplicate reconciliation
 * if the event fires multiple times.
 *
 * Queue: reconciliation
 */
class GenerateReconciliationJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $uniqueFor = 1800;

    public function __construct(
        private readonly Reservation    $reservation,
        private readonly Invoice        $proforma,
        private readonly DsiTransaction $dsiTransaction
    ) {}

    /**
     * Unique key — one reconciliation job per reservation at a time.
     */
    public function uniqueId(): string
    {
        return 'reconciliation_reservation_' . $this->reservation->id;
    }

    public function queue(): string
    {
        return 'reconciliation';
    }

    public function handle(ReconciliationEngine $engine): void
    {
        Log::channel('queue')->info('[GenerateReconciliationJob] Starting', [
            'reservation_id' => $this->reservation->id,
            'proforma_id'    => $this->proforma->id,
            'dsi_id'         => $this->dsiTransaction->id,
        ]);

        $reconciliation = $engine->reconcile($this->reservation, $this->proforma, $this->dsiTransaction);

        Log::channel('queue')->info('[GenerateReconciliationJob] Reconciliation created', [
            'reconciliation_id' => $reconciliation->id,
            'status'            => $reconciliation->status,
            'delta_amount'      => $reconciliation->delta_amount,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::channel('queue')->error('[GenerateReconciliationJob] Failed', [
            'reservation_id' => $this->reservation->id,
            'error'          => $exception->getMessage(),
        ]);
    }
}
