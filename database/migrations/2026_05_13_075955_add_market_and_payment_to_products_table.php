<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'market_type')) {
                $table->string('market_type', 20)->nullable()->after('category')->comment('Foreign, Domestic');
            }
            if (!Schema::hasColumn('products', 'payment_mode')) {
                $table->string('payment_mode', 20)->nullable()->after('market_type')->comment('TRANSFER, CASH, etc');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['market_type', 'payment_mode']);
        });
    }
};
