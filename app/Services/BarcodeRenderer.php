<?php

namespace App\Services;

/**
 * Pure-PHP Code 39 barcode SVG generator.
 * No external library needed. DomPDF renders inline SVG natively.
 *
 * Code 39: 9 elements per character (5 bars + 4 spaces, alternating),
 * 3 wide elements per character. Wide = 3×narrow.
 */
class BarcodeRenderer
{
    /**
     * Code 39 encoding table.
     * 9-bit string: positions 0,2,4,6,8 = bars; 1,3,5,7 = spaces.
     * '1' = wide (3×), '0' = narrow (1×).
     */
    private const CODE39 = [
        '0' => '000110100', '1' => '100100001', '2' => '001100001',
        '3' => '101100000', '4' => '000101001', '5' => '100101000',
        '6' => '001101000', '7' => '000100101', '8' => '100100100',
        '9' => '001100100',
        'A' => '100000101', 'B' => '001000101', 'C' => '101000100',
        'D' => '000010101', 'E' => '100010100', 'F' => '001010100',
        'G' => '000001101', 'H' => '100001100', 'I' => '001001100',
        'J' => '000011100', 'K' => '100000011', 'L' => '001000011',
        'M' => '101000010', 'N' => '000010011', 'O' => '100010010',
        'P' => '001010010', 'Q' => '000001011', 'R' => '100001010',
        'S' => '001001010', 'T' => '000011010', 'U' => '110000001',
        'V' => '011000001', 'W' => '111000000', 'X' => '010010001',
        'Y' => '010100001', 'Z' => '010000101',
        '-' => '010000011', '.' => '110000010', ' ' => '011000010',
        '$' => '010101000', '/' => '010100010', '+' => '010001010',
        '%' => '000101010',
        '*' => '010010100', // start / stop
    ];

    /**
     * Generate a Code 39 barcode as an inline SVG string.
     *
     * @param  string $text      Text to encode (auto-uppercased, unsupported chars skipped)
     * @param  int    $barWidth  Narrow element width in px (default 2)
     * @param  int    $barHeight Bar height in px (default 60)
     * @param  bool   $showText  Print human-readable text below the bars
     * @param  bool   $asImageTag If true, returns an <img> tag with base64 data URI
     */
    public static function code39(
        string $text,
        int $barWidth  = 2,
        int $barHeight = 60,
        bool $showText = true,
        bool $asImageTag = false
    ): string {
        $text   = strtoupper($text);
        $narrow = max(1, $barWidth);
        $wide   = $narrow * 3;
        $gap    = $narrow;          // inter-character gap (1 narrow space)
        $quiet  = $narrow * 8;      // quiet zone each side

        // Encode: wrap text in start/stop '*'
        $sequence = '*' . $text . '*';
        $chars    = str_split($sequence);

        // ── Calculate total SVG width ─────────────────────────────────────
        $totalWidth = $quiet * 2;
        foreach ($chars as $i => $char) {
            if (!isset(self::CODE39[$char])) continue;
            foreach (str_split(self::CODE39[$char]) as $bit) {
                $totalWidth += ($bit === '1') ? $wide : $narrow;
            }
            if ($i < count($chars) - 1) {
                $totalWidth += $gap;
            }
        }

        $svgHeight = $showText ? $barHeight + 16 : $barHeight;

        // ── Draw bars ─────────────────────────────────────────────────────
        $rects = '';
        $x     = $quiet;

        foreach ($chars as $ci => $char) {
            if (!isset(self::CODE39[$char])) continue;
            $pattern = str_split(self::CODE39[$char]);

            foreach ($pattern as $j => $bit) {
                $w = ($bit === '1') ? $wide : $narrow;
                if ($j % 2 === 0) { // even index = bar
                    $rects .= '<rect x="' . $x . '" y="0" width="' . $w . '" height="' . $barHeight . '" fill="#1e293b"/>';
                }
                $x += $w;
            }

            if ($ci < count($chars) - 1) {
                $x += $gap;
            }
        }

        // ── Human-readable text ───────────────────────────────────────────
        $textEl = '';
        if ($showText) {
            $cx      = (int) round($totalWidth / 2);
            $ty      = $barHeight + 13;
            $escaped = htmlspecialchars($text, ENT_XML1);
            $textEl  = '<text x="' . $cx . '" y="' . $ty . '" '
                . 'font-family="monospace" font-size="10" '
                . 'text-anchor="middle" fill="#1e293b">' . $escaped . '</text>';
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg"'
            . ' width="' . $totalWidth . '"'
            . ' height="' . $svgHeight . '"'
            . ' viewBox="0 0 ' . $totalWidth . ' ' . $svgHeight . '">'
            . $rects . $textEl
            . '</svg>';

        if ($asImageTag) {
            return '<img src="data:image/svg+xml;base64,' . base64_encode($svg) . '" style="width:100%; height:auto; display:block;" />';
        }

        return $svg;
    }
}
