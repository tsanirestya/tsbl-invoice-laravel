<?php

namespace App\Jobs;

use App\Models\DsiImportBatch;
use App\Services\DsiImporterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * C1 — ImportDsiTransactionsJob
 *
 * Processes a DSI import batch asynchronously.
 * ShouldBeUnique ensures only one job runs per batch_id at a time,
 * preventing double-processing if dispatcher is called twice.
 *
 * Queue: dsi-import
 */
class ImportDsiTransactionsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 30;

    /**
     * Unique lock duration (seconds). Prevents re-dispatch while running.
     */
    public int $uniqueFor = 3600;

    public function __construct(
        private readonly DsiImportBatch $batch,
        private readonly ?int $triggeredByUserId = null
    ) {}

    /**
     * Unique key — one job per batch.
     */
    public function uniqueId(): string
    {
        return 'dsi_import_batch_' . $this->batch->id;
    }

    /**
     * Queue assignment.
     */
    public function queue(): string
    {
        return 'dsi-import';
    }

    /**
     * Execute the job.
     */
    public function handle(DsiImporterService $importer): void
    {
        Log::channel('queue')->info('[ImportDsiTransactionsJob] Starting', [
            'batch_id'    => $this->batch->id,
            'triggered_by' => $this->triggeredByUserId,
        ]);

        $this->batch->update(['status' => 'PROCESSING']);

        try {
            $result = $importer->processBatch($this->batch);

            $this->batch->update([
                'status'          => 'COMPLETED',
                'processed_count' => $result['processed'],
                'duplicate_count' => $result['duplicates'],
                'error_count'     => $result['errors'],
            ]);

            Log::channel('queue')->info('[ImportDsiTransactionsJob] Completed', [
                'batch_id'  => $this->batch->id,
                'processed' => $result['processed'],
                'duplicates' => $result['duplicates'],
            ]);
        } catch (Throwable $e) {
            $this->batch->update(['status' => 'FAILED', 'notes' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::channel('queue')->error('[ImportDsiTransactionsJob] Failed', [
            'batch_id' => $this->batch->id,
            'error'    => $exception->getMessage(),
        ]);

        $this->batch->update(['status' => 'FAILED', 'notes' => $exception->getMessage()]);
    }
}
