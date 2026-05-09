<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class InvoiceNumberGeneratorService
{
    // Prefix per type: INV/PROFORMA/CN/DN/CANCEL
    private const PREFIXES = [
        'PROFORMA'     => 'PRF',
        'FINAL'        => 'INV',
        'CREDIT_NOTE'  => 'CN',
        'DEBIT_NOTE'   => 'DN',
        'CANCELLATION' => 'VOID',
    ];

    /**
     * Generate next invoice number atomically for given type + current month.
     * Uses DB advisory lock to prevent race conditions.
     *
     * Format: {PREFIX}/{YYYY}/{MM}/{NNNN}
     * Example: INV/2026/05/0001
     */
    public function generate(string $invoiceType): string
    {
        $prefix = self::PREFIXES[$invoiceType] ?? 'INV';
        $year   = now()->format('Y');
        $month  = now()->format('m');
        $like   = "{$prefix}/{$year}/{$month}/%";

        return DB::transaction(function () use ($prefix, $year, $month, $like) {
            // Lock key is deterministic per prefix+month — prevents two threads generating same number
            DB::statement("SELECT GET_LOCK(?, 5)", ["{$prefix}_{$year}_{$month}"]);

            $last = Invoice::where('invoice_no', 'like', $like)
                ->lockForUpdate()
                ->orderByRaw("CAST(SUBSTRING_INDEX(invoice_no, '/', -1) AS UNSIGNED) DESC")
                ->value('invoice_no');

            $next = $last
                ? (int) explode('/', $last)[3] + 1
                : 1;

            $seq = str_pad($next, 4, '0', STR_PAD_LEFT);

            DB::statement("SELECT RELEASE_LOCK(?)", ["{$prefix}_{$year}_{$month}"]);

            return "{$prefix}/{$year}/{$month}/{$seq}";
        });
    }
}
