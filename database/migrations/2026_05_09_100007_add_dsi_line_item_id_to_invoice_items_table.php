<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_items', 'dsi_line_item_id')) {
                $table->unsignedInteger('dsi_line_item_id')->nullable()
                    ->after('invoice_id')
                    ->comment('FK to dsi_line_items — links invoice line to DSI source');
                $table->index('dsi_line_item_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_items', 'dsi_line_item_id')) {
                $table->dropIndex(['dsi_line_item_id']);
                $table->dropColumn('dsi_line_item_id');
            }
        });
    }
};
