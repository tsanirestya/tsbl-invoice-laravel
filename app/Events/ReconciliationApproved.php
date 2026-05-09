<?php

namespace App\Events;

use App\Models\Reconciliation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * C2 — Event: ReconciliationApproved
 *
 * Fired when a finance user approves a reconciliation.
 * Listened by: GenerateDocumentsOnReconciliationApprove
 * which dispatches GenerateFinalInvoiceJob.
 */
class ReconciliationApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Reconciliation $reconciliation,
        public readonly int $approvedByUserId
    ) {}
}
