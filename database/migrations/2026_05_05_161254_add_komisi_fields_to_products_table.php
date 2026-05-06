<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'partner_type')) {
                $table->string('partner_type', 10)->nullable()->after('product_name');
            }
            if (!Schema::hasColumn('products', 'dsi_code')) {
                $table->string('dsi_code', 250)->nullable()->after('partner_type');
            }
            if (!Schema::hasColumn('products', 'publish_rate')) {
                $table->decimal('publish_rate', 15, 2)->default(0)->after('default_price');
            }
            if (!Schema::hasColumn('products', 'komisi')) {
                $table->decimal('komisi', 15, 2)->default(0)->after('publish_rate');
            }
            if (!Schema::hasColumn('products', 'nett_price')) {
                $table->decimal('nett_price', 15, 2)->default(0)->after('komisi');
            }
            if (!Schema::hasColumn('products', 'unit_price_dsi')) {
                $table->decimal('unit_price_dsi', 15, 2)->default(0)->after('nett_price');
            }
            if (!Schema::hasColumn('products', 'payment_mode')) {
                $table->string('payment_mode', 10)->nullable()->after('unit_price_dsi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'partner_type', 'dsi_code',
                'publish_rate', 'komisi', 'nett_price', 'unit_price_dsi', 'payment_mode',
            ]);
        });
    }
};
