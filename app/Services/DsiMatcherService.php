<?php

namespace App\Services;

use App\Models\DsiTransaction;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

/**
 * Matches a DsiTransaction to a Reservation by ref_no / guest_name / date heuristics.
 * Sets reservation_id + matched_at on the transaction when a confident match is found.
 */
class DsiMatcherService
{
    /**
     * Attempt to match transaction to a reservation.
     * Returns the matched Reservation or null if no confident match.
     */
    public function match(DsiTransaction $transaction): ?Reservation
    {
        // Already matched
        if ($transaction->reservation_id) {
            return $transaction->reservation;
        }

        $reservation = $this->matchByRefNo($transaction->ref_no)
            ?? $this->matchByGuestAndDate($transaction->guest_name, $transaction->transaction_date);

        if ($reservation) {
            DB::transaction(function () use ($transaction, $reservation) {
                $transaction->update([
                    'reservation_id' => $reservation->id,
                    'matched_at'     => now(),
                ]);
            });
        }

        return $reservation;
    }

    /**
     * Attempt match on all UNMATCHED transactions in a batch.
     * Returns count of newly matched transactions.
     */
    public function matchBatch(int $batchId): int
    {
        $unmatched = DsiTransaction::where('batch_id', $batchId)
            ->whereNull('reservation_id')
            ->get();

        $count = 0;
        foreach ($unmatched as $txn) {
            if ($this->match($txn)) {
                $count++;
            }
        }

        return $count;
    }

    private function matchByRefNo(string $refNo): ?Reservation
    {
        // reservation_no often appears in ref_no (e.g., "RES-2026-00123" in "TSBL-RES-2026-00123-PAYMENT")
        return Reservation::where('reservation_no', $refNo)
            ->orWhere(function ($q) use ($refNo) {
                $q->whereRaw('? LIKE CONCAT("%", reservation_no, "%")', [$refNo]);
            })
            ->first();
    }

    private function matchByGuestAndDate(string $guestName, mixed $transactionDate): ?Reservation
    {
        if (!$guestName || !$transactionDate) {
            return null;
        }

        $date = is_string($transactionDate) ? $transactionDate : $transactionDate->toDateString();

        // Fuzzy: guest_name exact + visit_date within 1 day of transaction_date
        return Reservation::whereRaw('LOWER(guest_name) = LOWER(?)', [$guestName])
            ->whereBetween('visit_date', [
                date('Y-m-d', strtotime($date . ' -1 day')),
                date('Y-m-d', strtotime($date . ' +1 day')),
            ])
            ->whereIn('status', ['CONFIRMED', 'CHECKED_IN'])
            ->first();
    }
}
