<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dsi_duplicate_flags')) {
            return;
        }

        Schema::create('dsi_duplicate_flags', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('dsi_transaction_id');
            $table->unsignedInteger('suspected_duplicate_of')->comment('FK to dsi_transactions.id');
            $table->enum('detection_layer', ['FILE_HASH', 'REF_NO', 'BUSINESS_LOGIC']);
            $table->enum('status', ['PENDING', 'CONFIRMED_DUPLICATE', 'FALSE_POSITIVE'])->default('PENDING');
            $table->text('detection_reason')->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index('dsi_transaction_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dsi_duplicate_flags');
    }
};
