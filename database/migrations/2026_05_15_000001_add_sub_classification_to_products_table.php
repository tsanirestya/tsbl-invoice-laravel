<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'sub_market_type')) {
                $table->string('sub_market_type', 20)->nullable()->after('market_type')
                    ->comment('child, adult');
            }
            if (!Schema::hasColumn('products', 'sub_payment_mode')) {
                $table->string('sub_payment_mode', 20)->nullable()->after('sub_market_type')
                    ->comment('NETT, GROSS');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['sub_market_type', 'sub_payment_mode']);
        });
    }
};
