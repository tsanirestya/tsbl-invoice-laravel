<?php

namespace App\Events;

use App\Models\DsiImportBatch;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * C2 — Event: DSIImported
 *
 * Fired when a DSI import batch has completed successfully.
 * Listened by: TriggerReconciliationOnDsiImport
 */
class DSIImported
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly DsiImportBatch $batch,
        public readonly int $importedByUserId
    ) {}
}
