<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Canonical form agreed on 2026-05-15
    private const CANONICAL = 'PLAY & FLY PACKAGE';

    // All known non-canonical variants for this activity
    private const VARIANTS = [
        'PLAY AND FLY PACKAGE',
        'PLAY& FLY PACKAGE',
    ];

    public function up(): void
    {
        DB::table('products')
            ->whereIn('parents_name', self::VARIANTS)
            ->update(['parents_name' => self::CANONICAL]);
    }

    public function down(): void
    {
        // Restore originals by matching product_name pattern
        DB::table('products')
            ->where('parents_name', self::CANONICAL)
            ->where('product_name', 'like', 'Play and Fly%')
            ->update(['parents_name' => 'PLAY AND FLY PACKAGE']);

        DB::table('products')
            ->where('parents_name', self::CANONICAL)
            ->where('product_name', 'like', 'PLAY& FLY%')
            ->update(['parents_name' => 'PLAY& FLY PACKAGE']);
    }
};
