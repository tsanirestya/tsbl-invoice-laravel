<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $settings = [
        ['key' => 'credit_aging_bucket_1',  'value' => '30',  'label' => 'Credit Aging Bucket 1 (hari)'],
        ['key' => 'credit_aging_bucket_2',  'value' => '60',  'label' => 'Credit Aging Bucket 2 (hari)'],
        ['key' => 'credit_aging_bucket_3',  'value' => '90',  'label' => 'Credit Aging Bucket 3 (hari)'],
        ['key' => 'credit_aging_bucket_4',  'value' => '120', 'label' => 'Credit Aging Bucket 4 (hari)'],
        ['key' => 'credit_warning_threshold', 'value' => '80', 'label' => 'Credit Warning Threshold (%)'],
    ];

    public function up(): void
    {
        foreach ($this->settings as $setting) {
            $exists = DB::table('settings')->where('key', $setting['key'])->exists();
            if (!$exists) {
                DB::table('settings')->insert($setting);
            }
        }
    }

    public function down(): void
    {
        $keys = array_column($this->settings, 'key');
        DB::table('settings')->whereIn('key', $keys)->delete();
    }
};
