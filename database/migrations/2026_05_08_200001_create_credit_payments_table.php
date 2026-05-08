<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('credit_payments')) {
            return;
        }

        Schema::create('credit_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('partner_id');
            $table->string('batch_no', 30)->unique();
            $table->date('payment_date');
            $table->decimal('total_received', 15, 2);
            $table->decimal('total_allocated', 15, 2)->default(0);
            $table->decimal('excess_to_deposit', 15, 2)->default(0);
            $table->unsignedInteger('deposit_transaction_id')->nullable();
            $table->string('payment_method', 50);
            $table->string('reference_no', 100)->nullable();
            $table->string('proof_file')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_voided')->default(false);
            $table->datetime('voided_at')->nullable();
            $table->unsignedInteger('voided_by')->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamps();

            $table->index('partner_id');
            $table->index('batch_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_payments');
    }
};
