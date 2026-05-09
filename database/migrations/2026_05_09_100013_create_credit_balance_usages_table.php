<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('credit_balance_usages')) {
            return;
        }

        Schema::create('credit_balance_usages', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('credit_balance_id');
            $table->unsignedInteger('invoice_id')->nullable()->comment('Invoice credit was applied to');
            $table->unsignedInteger('payment_id')->nullable()->comment('Payment that generated the credit');
            $table->enum('type', ['CREDIT', 'DEBIT'])->comment('CREDIT=added to balance, DEBIT=consumed from balance');
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->datetime('created_at')->nullable();

            $table->index('credit_balance_id');
            $table->index('invoice_id');
            $table->index('payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_balance_usages');
    }
};
