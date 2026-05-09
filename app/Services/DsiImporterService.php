<?php

namespace App\Services;

use App\Models\DsiImportBatch;
use App\Models\DsiLineItem;
use App\Models\DsiTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Ingests DSI data from CSV rows or API payload into dsi_import_batches + dsi_transactions.
 * Runs duplicate detection (all 3 layers) per row.
 * Flags suspected duplicates — does NOT reject them outright (finance must review).
 */
class DsiImporterService
{
    public function __construct(
        private readonly DsiDuplicateDetectorService $duplicateDetector,
        private readonly DsiMatcherService $matcher
    ) {}

    /**
     * Import from array of rows (already parsed from CSV or API).
     *
     * Each row must have:
     *   ref_no, transaction_date, guest_name, amount, product_description
     * Optional:
     *   reservation_id, raw_data (any extra columns), line_items (array)
     *
     * @param  array  $rows
     * @param  string $source     'CSV' | 'API'
     * @param  string $fileName   Original filename (CSV) or API ref
     * @param  string $fileHash   SHA-256 of raw file content
     * @param  int    $importedBy User ID
     * @return DsiImportBatch
     */
    public function import(
        array $rows,
        string $source,
        string $fileName,
        string $fileHash,
        int $importedBy
    ): DsiImportBatch {
        // Layer 1 — file-level duplicate
        if ($this->duplicateDetector->isBatchDuplicate($fileHash)) {
            throw new \RuntimeException("File already imported (hash match). Import rejected.");
        }

        return DB::transaction(function () use ($rows, $source, $fileName, $fileHash, $importedBy) {
            $batch = DsiImportBatch::create([
                'batch_ref'     => 'DSI-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
                'file_name'     => $fileName,
                'file_hash'     => $fileHash,
                'source'        => $source,
                'status'        => 'PROCESSING',
                'total_rows'    => count($rows),
                'processed_rows'=> 0,
                'failed_rows'   => 0,
                'imported_by'   => $importedBy,
            ]);

            $processed = 0;
            $failed    = 0;
            $errors    = [];

            foreach ($rows as $idx => $row) {
                try {
                    $this->importRow($row, $batch->id, $importedBy);
                    $processed++;
                } catch (\Throwable $e) {
                    $failed++;
                    $errors[] = "Row {$idx}: " . $e->getMessage();
                }
            }

            $status = $failed === 0 ? 'COMPLETED' : ($processed === 0 ? 'FAILED' : 'PARTIAL');

            $batch->update([
                'status'         => $status,
                'processed_rows' => $processed,
                'failed_rows'    => $failed,
                'error_summary'  => $errors ? implode("\n", $errors) : null,
            ]);

            // Attempt auto-match after import
            $this->matcher->matchBatch($batch->id);

            return $batch->fresh();
        });
    }

    private function importRow(array $row, int $batchId, int $importedBy): void
    {
        // Layers 2 & 3 duplicate check
        $flagData = $this->duplicateDetector->detect($row, '', $batchId);

        $transaction = DsiTransaction::create([
            'batch_id'            => $batchId,
            'ref_no'              => $row['ref_no'],
            'reservation_id'      => $row['reservation_id'] ?? null,
            'transaction_date'    => $row['transaction_date'],
            'guest_name'          => $row['guest_name'] ?? null,
            'amount'              => $row['amount'],
            'product_description' => $row['product_description'] ?? null,
            'raw_data'            => $row['raw_data'] ?? $row,
            'is_locked'           => false,
        ]);

        if ($flagData) {
            $this->duplicateDetector->flag($transaction->id, $flagData, $importedBy);
        }

        // Create line items if provided
        $lineItems = $row['line_items'] ?? [];
        foreach ($lineItems as $i => $li) {
            DsiLineItem::create([
                'dsi_transaction_id' => $transaction->id,
                'description'        => $li['description'] ?? $row['product_description'] ?? null,
                'quantity'           => $li['quantity'] ?? 1,
                'unit_price'         => $li['unit_price'] ?? $row['amount'],
                'amount'             => $li['amount'] ?? $row['amount'],
                'sort_order'         => $li['sort_order'] ?? ($i + 1),
            ]);
        }
    }
}
