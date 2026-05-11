<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Reconciliation;
use App\Services\InvoiceCreatorService;
use App\Services\InvoiceStatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * C1 — GenerateFinalInvoiceJob
 *
 * Triggered when a Reconciliation is approved.
 * Creates the FINAL invoice from the approved reconciliation data,
 * locks it, and transitions the proforma to REPLACED status.
 *
 * Runs on the financial-critical queue (highest priority, single worker
 * to prevent concurrent financial document generation).
 *
 * Queue: financial-critical
 */
class GenerateFinalInvoiceJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;
    public int $uniqueFor = 3600;

    public function __construct(
        private readonly Reconciliation $reconciliation,
        private readonly int $approvedByUserId
    ) {}

    /**
     * Unique key — one final invoice job per reconciliation.
     */
    public function uniqueId(): string
    {
        return 'final_invoice_reconciliation_' . $this->reconciliation->id;
    }

    public function queue(): string
    {
        return 'financial-critical';
    }

    public function handle(InvoiceCreatorService $creator, InvoiceStatusService $statusService): void
    {
        $reconciliation = $this->reconciliation->load(['proformaInvoice.partner', 'proformaInvoice.items']);
        $proforma       = $reconciliation->proformaInvoice;

        Log::channel('queue')->info('[GenerateFinalInvoiceJob] Starting', [
            'reconciliation_id' => $reconciliation->id,
            'proforma_id'       => $proforma->id,
            'approved_by'       => $this->approvedByUserId,
        ]);

        // Guard: prevent double-generation
        if (Invoice::where('replaces_invoice_id', $proforma->id)
            ->where('invoice_type', Invoice::TYPE_FINAL)
            ->exists()
        ) {
            Log::channel('queue')->warning('[GenerateFinalInvoiceJob] Final invoice already exists', [
                'proforma_id' => $proforma->id,
            ]);
            return;
        }

        $finalInvoice = $creator->createFinal(
            proforma: $proforma,
            reconciliation: $reconciliation,
            createdBy: $this->approvedByUserId
        );

        // Lock the final invoice immediately after creation
        $finalInvoice->update([
            'is_locked'   => true,
            'lock_reason' => 'Generated from Reconciliation #' . $reconciliation->id,
        ]);

        Log::channel('queue')->info('[GenerateFinalInvoiceJob] Final invoice created', [
            'final_invoice_id' => $finalInvoice->id,
            'invoice_no'       => $finalInvoice->invoice_no,
            'grand_total'      => $finalInvoice->grand_total,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::channel('queue')->error('[GenerateFinalInvoiceJob] Failed', [
            'reconciliation_id' => $this->reconciliation->id,
            'error'             => $exception->getMessage(),
        ]);
    }
}
