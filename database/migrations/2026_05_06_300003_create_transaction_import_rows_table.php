<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('transaction_import_rows')) {
            Schema::create('transaction_import_rows', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('import_id');
                $table->foreign('import_id')->references('id')->on('transaction_imports')->cascadeOnDelete();
                $table->uuid('uuid_key')->unique();
                $table->unsignedInteger('row_index');
                // Raw columns from Excel
                $table->string('transaction_no')->nullable();
                $table->date('date')->nullable();
                $table->string('ticket_type')->nullable();
                $table->string('ticket_name')->nullable();
                $table->string('transaction_type')->nullable();
                $table->time('time')->nullable();
                $table->string('cashier')->nullable();
                $table->string('payment_method')->nullable();
                $table->string('payment_details')->nullable();
                $table->decimal('unit_price', 15, 2)->nullable();
                $table->unsignedInteger('qty')->default(1);
                $table->decimal('total_amount', 15, 2)->nullable();
                $table->text('remark')->nullable();
                $table->string('country')->nullable();
                $table->string('nationality')->nullable();
                // Matching
                $table->unsignedInteger('matched_product_id')->nullable();
                $table->foreign('matched_product_id')->references('id')->on('products');
                $table->enum('match_method', ['exact', 'alias', 'fuzzy', 'none'])->nullable();
                // Pricing
                $table->decimal('publish_rate', 15, 2)->nullable();
                $table->decimal('nett_price', 15, 2)->nullable();
                $table->decimal('komisi_rate', 15, 2)->nullable();
                $table->decimal('komisi_amount', 15, 2)->nullable();
                // Status
                $table->enum('status', ['pending', 'valid', 'anomaly', 'rejected'])->default('pending');
                $table->boolean('is_approved')->default(false);
                $table->unsignedInteger('approved_by')->nullable();
                $table->foreign('approved_by')->references('id')->on('users');
                $table->timestamp('approved_at')->nullable();
                $table->text('override_reason')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_import_rows');
    }
};
