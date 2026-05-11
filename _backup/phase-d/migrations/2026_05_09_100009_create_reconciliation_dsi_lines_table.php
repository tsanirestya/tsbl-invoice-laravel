<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reconciliation_dsi_lines')) {
            return;
        }

        Schema::create('reconciliation_dsi_lines', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('reconciliation_id');
            $table->unsignedInteger('dsi_line_item_id');
            $table->datetime('created_at')->nullable();

            $table->index('reconciliation_id');
            $table->index('dsi_line_item_id');
            $table->unique(['reconciliation_id', 'dsi_line_item_id'], 'recon_dsi_line_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_dsi_lines');
    }
};
