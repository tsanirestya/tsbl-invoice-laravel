<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\AuditLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * C1 — MarkOverdueInvoicesJob
 *
 * Scheduled daily (see routes/console.php).
 * Processes invoices in chunks to avoid memory exhaustion on large datasets.
 * Logs each status change to invoice_logs for full audit trail.
 *
 * Replaces the side-effect in DashboardController (F017 fix).
 * Queue: default (dispatched by scheduler, not via queue worker)
 */
class MarkOverdueInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries     = 1;
    public int $timeout   = 300; // 5 minutes max

    /**
     * Chunk size — process N invoices at a time to keep memory bounded.
     */
    private const CHUNK_SIZE = 200;

    public function handle(AuditLogService $auditLog): void
    {
        $now       = now();
        $marked    = 0;
        $skipped   = 0;

        Log::channel('queue')->info('[MarkOverdueInvoicesJob] Starting daily overdue check', [
            'run_at' => $now->toDateTimeString(),
        ]);

        // Only target unpaid, finalized, past-due invoices (UNPAID + PARTIAL)
        Invoice::query()
            ->whereIn('payment_status', ['UNPAID', 'PARTIAL'])
            ->where('is_finalized', true)
            ->whereNotNull('due_date')
            ->where('due_date', '<', $now->toDateString())
            ->where('payment_status', '!=', 'OVERDUE') // already overdue
            ->select(['id', 'payment_status', 'due_date', 'partner_id'])
            ->chunkById(self::CHUNK_SIZE, function ($invoices) use ($auditLog, &$marked, &$skipped) {
                foreach ($invoices as $invoice) {
                    try {
                        DB::transaction(function () use ($invoice, $auditLog, &$marked) {
                            $oldStatus = $invoice->payment_status;

                            $invoice->update(['payment_status' => 'OVERDUE']);

                            // Write to invoice_logs audit trail (F017 compliance)
                            $invoice->logs()->create([
                                'user_id'    => null, // system action
                                'event_type' => 'STATUS_CHANGE',
                                'notes'      => "Auto-marked OVERDUE by scheduled job. Was: {$oldStatus}. Due date: {$invoice->due_date}",
                            ]);

                            $marked++;
                        });
                    } catch (Throwable $e) {
                        $skipped++;
                        Log::channel('queue')->warning('[MarkOverdueInvoicesJob] Failed to mark invoice', [
                            'invoice_id' => $invoice->id,
                            'error'      => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::channel('queue')->info('[MarkOverdueInvoicesJob] Completed', [
            'marked'  => $marked,
            'skipped' => $skipped,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::channel('queue')->error('[MarkOverdueInvoicesJob] Job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
