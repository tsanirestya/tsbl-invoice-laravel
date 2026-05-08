<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreditClassSeeder extends Seeder
{
    public function run(): void
    {
        $classes = [
            [
                'name'        => 'Entry',
                'color'       => 'secondary',
                'min_limit'   => 0,
                'max_limit'   => 5000000.00,
                'description' => 'Kredit limit 0 – 5.000.000',
                'sort_order'  => 1,
            ],
            [
                'name'        => 'Standard',
                'color'       => 'primary',
                'min_limit'   => 5000001,
                'max_limit'   => 30000000.00,
                'description' => 'Kredit limit 5.000.001 – 30.000.000',
                'sort_order'  => 2,
            ],
            [
                'name'        => 'Premium',
                'color'       => 'warning',
                'min_limit'   => 30000001,
                'max_limit'   => null,
                'description' => 'Kredit limit > 30.000.000, tidak terbatas',
                'sort_order'  => 3,
            ],
        ];

        foreach ($classes as $class) {
            $exists = DB::table('credit_classes')->where('name', $class['name'])->exists();
            if (!$exists) {
                DB::table('credit_classes')->insert(array_merge($class, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }
}
