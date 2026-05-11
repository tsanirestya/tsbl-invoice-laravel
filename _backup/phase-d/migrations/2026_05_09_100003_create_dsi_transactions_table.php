<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dsi_transactions')) {
            return;
        }

        Schema::create('dsi_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('batch_id');
            $table->string('ref_no', 100)->comment('Layer 2 duplicate detection — unique per source');
            $table->unsignedInteger('reservation_id')->nullable()->comment('Populated by DsiMatcherService');
            $table->date('transaction_date');
            $table->string('guest_name', 200)->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('product_description', 500)->nullable();
            $table->json('raw_data')->nullable()->comment('Original import payload preserved for audit');
            $table->boolean('is_locked')->default(false)->comment('True after reconciliation — no updates allowed');
            $table->timestamp('matched_at')->nullable();
            $table->datetime('created_at')->nullable();

            $table->index('batch_id');
            $table->index('reservation_id');
            $table->index(['ref_no', 'batch_id']);
            $table->index('is_locked');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dsi_transactions');
    }
};
