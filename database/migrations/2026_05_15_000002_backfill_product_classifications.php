<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill market_type, sub_market_type, sub_payment_mode from dsi_code keywords.
 *
 * market_type rules (priority: foreign first):
 *   foreign  — dsi_code contains: foreign, adult, child  OR matches \b\d+[AC]\b
 *   domestic — dsi_code contains: local, lokal, domestic, domestik
 *   foreign  — default for unmatched
 *
 * sub_market_type rules:
 *   child — dsi_code contains 'child' OR matches \b\d+C\b
 *   adult — everything else
 *
 * sub_payment_mode rules:
 *   NETT  — dsi_code contains 'net' (case-insensitive, covers NET & NETT)
 *   GROSS — everything else
 */
return new class extends Migration
{
    public function up(): void
    {
        $products = DB::table('products')->get(['id', 'dsi_code']);

        foreach ($products as $product) {
            $code  = $product->dsi_code ?? '';
            $lower = strtolower($code);

            DB::table('products')->where('id', $product->id)->update([
                'market_type'      => $this->resolveMarketType($code, $lower),
                'sub_market_type'  => $this->resolveSubMarketType($code, $lower),
                'sub_payment_mode' => $this->resolveSubPaymentMode($lower),
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally left blank — data restore requires manual intervention.
    }

    private function resolveMarketType(string $code, string $lower): string
    {
        $foreignKeywords  = ['foreign', 'adult', 'child'];
        $domesticKeywords = ['local', 'lokal', 'domestic', 'domestik'];

        foreach ($foreignKeywords as $kw) {
            if (str_contains($lower, $kw)) return 'foreign';
        }
        if (preg_match('/\b\d+[AC]\b/i', $code)) return 'foreign';

        foreach ($domesticKeywords as $kw) {
            if (str_contains($lower, $kw)) return 'domestic';
        }

        return 'foreign'; // default
    }

    private function resolveSubMarketType(string $code, string $lower): string
    {
        if (str_contains($lower, 'child')) return 'child';
        if (preg_match('/\b\d+C\b/i', $code)) return 'child';
        return 'adult';
    }

    private function resolveSubPaymentMode(string $lower): string
    {
        return str_contains($lower, 'net') ? 'NETT' : 'GROSS';
    }
};
