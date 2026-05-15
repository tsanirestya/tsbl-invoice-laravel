<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $products = DB::table('products')->get(['id', 'product_name']);

        foreach ($products as $p) {
            // Split on first " - " to get parent name and variant descriptor
            $parts  = explode(' - ', $p->product_name, 2);
            $parent = strtoupper(trim($parts[0]));
            $type   = isset($parts[1]) ? trim($parts[1]) : '';

            // Bundle detection: "2A&2C", "2 Adult & 2 Child", "Adult/Child", "2 ADULT" etc.
            $isBundle = preg_match('/\d+\s*A\s*[&\s]\s*\d+\s*C/i', $type)
                     || preg_match('/\d+\s*ADULT/i', $type)
                     || preg_match('/ADULT\s*[\/&]\s*(AND\s*)?CHILD/i', $type);

            $adultCount = 0;
            $childCount = 0;

            if ($isBundle) {
                $paxType = 'BUNDLE';

                // Try "2A&2C" or "2 A & 2 C" pattern
                if (preg_match('/(\d+)\s*A\s*[&\s]+\s*(\d+)\s*C/i', $type, $m)) {
                    $adultCount = (int) $m[1];
                    $childCount = (int) $m[2];
                } else {
                    // Try separate adult/child counts
                    if (preg_match('/(\d+)\s*ADULT/i', $type, $m)) {
                        $adultCount = (int) $m[1];
                    }
                    if (preg_match('/(\d+)\s*CHILD/i', $type, $m)) {
                        $childCount = (int) $m[1];
                    }
                }
            } else {
                $hasAdult = stripos($type, 'adult') !== false;
                $hasChild = stripos($type, 'child') !== false;
                $paxType  = $hasAdult ? 'ADULT' : ($hasChild ? 'CHILD' : 'TICKET');
            }

            DB::table('products')->where('id', $p->id)->update([
                'parents_name'       => $parent,
                'pax_type'           => $paxType,
                'bundle_adult_count' => $adultCount,
                'bundle_child_count' => $childCount,
            ]);
        }
    }

    public function down(): void
    {
        // Reset populated columns to null
        DB::table('products')->update([
            'parents_name'       => null,
            'pax_type'           => null,
            'bundle_adult_count' => 0,
            'bundle_child_count' => 0,
        ]);
    }
};
