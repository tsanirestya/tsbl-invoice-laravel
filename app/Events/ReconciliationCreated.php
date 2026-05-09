<?php

namespace App\Events;

use App\Models\Reconciliation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * C2 — Event: ReconciliationCreated
 *
 * Fired when a new Reconciliation record is created (status: PENDING_REVIEW).
 * Listened by: NotifyFinanceOnReconciliationPending
 */
class ReconciliationCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Reconciliation $reconciliation
    ) {}
}
