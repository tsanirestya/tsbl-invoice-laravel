<?php

namespace App\Helpers;

class Terbilang
{
    private static array $ones = [
        '', 'satu', 'dua', 'tiga', 'empat', 'lima',
        'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh',
        'sebelas', 'dua belas', 'tiga belas', 'empat belas', 'lima belas',
        'enam belas', 'tujuh belas', 'delapan belas', 'sembilan belas',
    ];

    private static array $tens = [
        '', '', 'dua puluh', 'tiga puluh', 'empat puluh', 'lima puluh',
        'enam puluh', 'tujuh puluh', 'delapan puluh', 'sembilan puluh',
    ];

    public static function convert(float|int $amount): string
    {
        $amount = (int) round(abs($amount));

        if ($amount === 0) {
            return 'nol rupiah';
        }

        return trim(self::spell($amount)) . ' rupiah';
    }

    private static function spell(int $n): string
    {
        if ($n < 20) {
            return self::$ones[$n];
        }

        if ($n < 100) {
            $unit = $n % 10;
            return self::$tens[(int) ($n / 10)] . ($unit ? ' ' . self::$ones[$unit] : '');
        }

        if ($n < 1_000) {
            $hundreds = (int) ($n / 100);
            $rest     = $n % 100;
            $prefix   = $hundreds === 1 ? 'seratus' : self::$ones[$hundreds] . ' ratus';
            return $prefix . ($rest ? ' ' . self::spell($rest) : '');
        }

        if ($n < 1_000_000) {
            $thousands = (int) ($n / 1_000);
            $rest      = $n % 1_000;
            $prefix    = $thousands === 1 ? 'seribu' : self::spell($thousands) . ' ribu';
            return $prefix . ($rest ? ' ' . self::spell($rest) : '');
        }

        if ($n < 1_000_000_000) {
            $millions = (int) ($n / 1_000_000);
            $rest     = $n % 1_000_000;
            return self::spell($millions) . ' juta' . ($rest ? ' ' . self::spell($rest) : '');
        }

        $billions = (int) ($n / 1_000_000_000);
        $rest     = $n % 1_000_000_000;
        return self::spell($billions) . ' miliar' . ($rest ? ' ' . self::spell($rest) : '');
    }
}
