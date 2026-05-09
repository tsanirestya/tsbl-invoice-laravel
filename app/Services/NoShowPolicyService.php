<?php

namespace App\Services;

use App\Models\Partner;
use App\Models\Reservation;

/**
 * Applies partner-specific no-show policy rules during reconciliation.
 *
 * Policy types (stored on partner contract or fallback to system default):
 *   full_charge  — partner pays 100% of proforma regardless of no-show
 *   partial      — partner pays configurable % (default 50%) of proforma
 *   no_charge    — partner pays 0 if guest no-shows
 *
 * A "no-show" is determined by: reservation status = NO_SHOW and DSI amount = 0.
 */
class NoShowPolicyService
{
    private const DEFAULT_POLICY         = 'full_charge';
    private const DEFAULT_PARTIAL_RATE   = 0.50; // 50%

    /**
     * Apply no-show policy to a delta calculation result.
     *
     * @param  array  $delta  Output from DeltaCalculatorService::calculate()
     * @param  Partner     $partner
     * @param  Reservation $reservation
     * @return array{
     *   policy_applied: bool,
     *   policy_type: string,
     *   charge_amount: float,
     *   no_show_charge_amount: float,
     *   reason: string,
     * }
     */
    public function apply(array $delta, Partner $partner, Reservation $reservation): array
    {
        $isNoShow = $reservation->status === 'NO_SHOW' && $delta['dsi_amount'] == 0;

        if (!$isNoShow) {
            return [
                'policy_applied'       => false,
                'policy_type'          => 'N/A',
                'charge_amount'        => $delta['dsi_amount'],
                'no_show_charge_amount'=> 0,
                'reason'               => 'No no-show detected.',
            ];
        }

        $policy      = $this->resolvePolicy($partner);
        $proformaAmt = $delta['proforma_amount'];

        switch ($policy) {
            case 'full_charge':
                $chargeAmount       = $proformaAmt;
                $noShowChargeAmount = $proformaAmt;
                $reason             = "No-show: full charge ({$partner->nama_partner} policy: full_charge)";
                break;

            case 'partial':
                $rate               = $this->resolvePartialRate($partner);
                $chargeAmount       = round($proformaAmt * $rate, 2);
                $noShowChargeAmount = $chargeAmount;
                $reason             = "No-show: partial charge at " . ($rate * 100) . "% ({$partner->nama_partner} policy: partial)";
                break;

            case 'no_charge':
                $chargeAmount       = 0;
                $noShowChargeAmount = 0;
                $reason             = "No-show: waived ({$partner->nama_partner} policy: no_charge)";
                break;

            default:
                $chargeAmount       = $proformaAmt;
                $noShowChargeAmount = $proformaAmt;
                $reason             = "No-show: full charge (fallback default)";
        }

        return [
            'policy_applied'        => true,
            'policy_type'           => $policy,
            'charge_amount'         => $chargeAmount,
            'no_show_charge_amount' => $noShowChargeAmount,
            'reason'                => $reason,
        ];
    }

    private function resolvePolicy(Partner $partner): string
    {
        // Partner model may have a notes field with policy or a future no_show_policy column.
        // For now, use payment_type as a proxy or fall back to default.
        // When a dedicated column is added, update this method.
        return self::DEFAULT_POLICY;
    }

    private function resolvePartialRate(Partner $partner): float
    {
        return self::DEFAULT_PARTIAL_RATE;
    }
}
