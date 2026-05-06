<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductImportSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('data awal/SOURCE DATA KOMISI.xlsx');
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();

        $inserted = 0;
        $skipped  = 0;

        for ($row = 2; $row <= $highestRow; $row++) {
            $dsiCode = trim((string) $sheet->getCell("B$row")->getValue());

            if (empty($dsiCode)) continue;

            if (DB::table('products')->where('dsi_code', $dsiCode)->exists()) {
                $skipped++;
                continue;
            }

            $partnerType  = trim((string) $sheet->getCell("A$row")->getCalculatedValue());
            $publishRate  = $this->toDecimal($sheet->getCell("C$row")->getValue());
            $komisi       = $this->toDecimal($sheet->getCell("D$row")->getValue());
            $nettPrice    = $this->toDecimal($sheet->getCell("E$row")->getValue());
            $productName  = trim((string) $sheet->getCell("F$row")->getValue());
            $unitPriceDsi = $this->toDecimal($sheet->getCell("G$row")->getValue());
            $paymentMode  = trim((string) $sheet->getCell("H$row")->getValue()) ?: null;

            DB::table('products')->insert([
                'product_name'   => $productName ?: $dsiCode,
                'partner_type'   => $partnerType ?: null,
                'dsi_code'       => $dsiCode,
                'default_price'  => $nettPrice,
                'publish_rate'   => $publishRate,
                'komisi'         => $komisi,
                'nett_price'     => $nettPrice,
                'unit_price_dsi' => $unitPriceDsi,
                'unit'           => 'Pax',
                'payment_mode'   => $paymentMode,
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            $inserted++;
        }

        $this->command->info("PRODUCTS: $inserted imported, $skipped skipped (already exists).");
    }

    private function toDecimal(mixed $val): float
    {
        $v = str_replace(['.', ','], ['', '.'], (string) $val);
        return is_numeric($v) ? (float) $v : 0;
    }
}
