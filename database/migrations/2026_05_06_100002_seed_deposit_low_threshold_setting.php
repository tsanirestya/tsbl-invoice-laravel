<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('settings')->where('key', 'deposit_low_threshold')->exists();
        if (!$exists) {
            DB::table('settings')->insert([
                'key'   => 'deposit_low_threshold',
                'value' => '1000000',
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'deposit_low_threshold')->delete();
    }
};
