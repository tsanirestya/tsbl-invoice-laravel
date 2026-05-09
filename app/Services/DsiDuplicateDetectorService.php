<?php

namespace App\Services;

use App\Models\DsiDuplicateFlag;
use App\Models\DsiImportBatch;
use App\Models\DsiTransaction;

/**
 * 3-layer duplicate detection for incoming DSI transactions.
 *
 * Layer 1 — File hash: entire file already imported (batch level)
 * Layer 2 — Ref no:    exact ref_no match in DB (idempotency key)
 * Layer 3 — Business:  same reservation_id + transaction_date + amount (semantic duplicate)
 */
class DsiDuplicateDetectorService
{
    /**
     * Layer 1: Check if a batch with this file hash already exists.
     */
    public function isBatchDuplicate(string $fileHash): bool
    {
        return DsiImportBatch::where('file_hash', $fileHash)
            ->where('status', '!=', 'FAILED')
            ->exists();
    }

    /**
     * Layer 2: Check if a transaction with this ref_no is already in DB.
     * Returns the existing transaction or null.
     */
    public function findByRefNo(string $refNo): ?DsiTransaction
    {
        return DsiTransaction::where('ref_no', $refNo)->first();
    }

    /**
     * Layer 3: Find semantic duplicates — same reservation + date + amount.
     * Returns matching transaction or null.
     */
    public function findSemanticDuplicate(
        ?string $reservationId,
        string $transactionDate,
        float $amount
    ): ?DsiTransaction {
        if (!$reservationId) {
            return null;
        }

        return DsiTransaction::where('reservation_id', $reservationId)
            ->where('transaction_date', $transactionDate)
            ->whereRaw('ABS(amount - ?) < 0.01', [$amount])
            ->first();
    }

    /**
     * Run all 3 layers against a single row. Returns array of flag data if suspect found.
     * Returns null if clean.
     */
    public function detect(array $row, string $fileHash, int $batchId): ?array
    {
        // Layer 2
        $byRef = $this->findByRefNo($row['ref_no'] ?? '');
        if ($byRef) {
            return [
                'suspected_duplicate_of' => $byRef->id,
                'detection_layer'        => 'REF_NO',
                'detection_reason'       => "ref_no '{$row['ref_no']}' already exists in transaction #{$byRef->id}",
            ];
        }

        // Layer 3
        $semantic = $this->findSemanticDuplicate(
            $row['reservation_id'] ?? null,
            $row['transaction_date'] ?? '',
            (float) ($row['amount'] ?? 0)
        );
        if ($semantic) {
            return [
                'suspected_duplicate_of' => $semantic->id,
                'detection_layer'        => 'SEMANTIC',
                'detection_reason'       => "Same reservation_id, date, and amount as transaction #{$semantic->id}",
            ];
        }

        return null;
    }

    /**
     * Persist a duplicate flag record for a transaction.
     */
    public function flag(int $transactionId, array $flagData, int $createdBy): DsiDuplicateFlag
    {
        return DsiDuplicateFlag::create(array_merge($flagData, [
            'dsi_transaction_id' => $transactionId,
            'status'             => 'PENDING',
        ]));
    }
}
