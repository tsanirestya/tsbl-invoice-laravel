<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'category')) {
                $table->string('category', 10)->nullable()->after('dsi_code');
            }
        });

        // Backfill existing products: 3 left chars of dsi_code
        // dsi_code = '0' → category = '0'; NULL → stays NULL
        DB::statement("
            UPDATE products
            SET category = CASE
                WHEN dsi_code IS NULL THEN NULL
                WHEN dsi_code = '0'   THEN '0'
                ELSE LEFT(dsi_code, 3)
            END
            WHERE category IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
