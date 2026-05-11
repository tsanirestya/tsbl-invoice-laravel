<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dsi_line_items')) {
            return;
        }

        Schema::create('dsi_line_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('dsi_transaction_id');
            $table->string('description', 500)->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->datetime('created_at')->nullable();

            $table->index('dsi_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dsi_line_items');
    }
};
