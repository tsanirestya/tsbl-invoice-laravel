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
        if (!Schema::hasColumn('reservation_items', 'product_id')) return;

        Schema::table('reservation_items', function (Blueprint $table) {
            // Make nullable to support baby items (price=0, no product) for audit trail
            $table->unsignedInteger('product_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('reservation_items', function (Blueprint $table) {
            $table->unsignedInteger('product_id')->nullable(false)->change();
        });
    }
};
