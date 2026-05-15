<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add columns (guarded — safe to re-run)
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'parents_name')) {
                $table->string('parents_name', 255)->nullable()->after('product_name');
            }
            if (!Schema::hasColumn('products', 'pax_type')) {
                $table->enum('pax_type', ['ADULT', 'CHILD', 'BUNDLE', 'TICKET'])->nullable()->after('parents_name');
            }
            if (!Schema::hasColumn('products', 'bundle_adult_count')) {
                $table->tinyInteger('bundle_adult_count')->default(0)->after('pax_type');
            }
            if (!Schema::hasColumn('products', 'bundle_child_count')) {
                $table->tinyInteger('bundle_child_count')->default(0)->after('bundle_adult_count');
            }
        });

        // Add indexes — use try/catch so duplicate-index errors don't blow up
        try {
            Schema::table('products', function (Blueprint $table) {
                $table->index('parents_name', 'products_parents_name_idx');
            });
        } catch (\Exception $e) {
            // index already exists — skip
        }

        try {
            Schema::table('products', function (Blueprint $table) {
                $table->index(
                    ['parents_name', 'pax_type', 'sub_payment_mode', 'market_type'],
                    'products_pax_lookup_idx'
                );
            });
        } catch (\Exception $e) {
            // index already exists — skip
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop indexes first, then columns
            try { $table->dropIndex('products_pax_lookup_idx'); } catch (\Exception $e) {}
            try { $table->dropIndex('products_parents_name_idx'); } catch (\Exception $e) {}
            $table->dropColumn(['parents_name', 'pax_type', 'bundle_adult_count', 'bundle_child_count']);
        });
    }
};
