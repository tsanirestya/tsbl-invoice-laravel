<?php

namespace App\Services;

use App\Models\DsiTransaction;
use App\Models\Invoice;

/**
 * Computes the delta between proforma invoice amount and DSI transaction amount.
 *
 * Delta > 0: DSI charged more than proforma  → Debit Note territory
 * Delta < 0: DSI charged less than proforma  → Credit Note territory
 * Delta = 0: Perfect match                   → Final invoice = Proforma amount
 */
class DeltaCalculatorService
{
    /**
     * Calculate delta and return structured result.
     *
     * @return array{
     *   proforma_amount: float,
     *   dsi_amount: float,
     *   delta_amount: float,
     *   delta_type: 'ZERO'|'OVER'|'UNDER',
     *   requires_adjustment: bool,
     * }
     */
    public function calculate(Invoice $proforma, DsiTransaction $dsi): array
    {
        $proformaAmount = (float) $proforma->grand_total;
        $dsiAmount      = (float) $dsi->amount;
        $delta          = round($dsiAmount - $proformaAmount, 2);

        if (abs($delta) < 0.01) {
            $deltaType = 'ZERO';
        } elseif ($delta > 0) {
            $deltaType = 'OVER';  // DSI > Proforma → partner owes more
        } else {
            $deltaType = 'UNDER'; // DSI < Proforma → partner over-paid on proforma
        }

        return [
            'proforma_amount'     => $proformaAmount,
            'dsi_amount'          => $dsiAmount,
            'delta_amount'        => $delta,
            'delta_type'          => $deltaType,
            'requires_adjustment' => $deltaType !== 'ZERO',
        ];
    }

    /**
     * Calculate using raw amounts (used when models not yet persisted).
     */
    public function calculateRaw(float $proformaAmount, float $dsiAmount): array
    {
        $delta = round($dsiAmount - $proformaAmount, 2);

        if (abs($delta) < 0.01) {
            $deltaType = 'ZERO';
        } elseif ($delta > 0) {
            $deltaType = 'OVER';
        } else {
            $deltaType = 'UNDER';
        }

        return [
            'proforma_amount'     => $proformaAmount,
            'dsi_amount'          => $dsiAmount,
            'delta_amount'        => $delta,
            'delta_type'          => $deltaType,
            'requires_adjustment' => $deltaType !== 'ZERO',
        ];
    }
}
