<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('credit_balances')) {
            return;
        }

        Schema::create('credit_balances', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('partner_id')->unique()->comment('One credit balance ledger per partner');
            $table->decimal('balance', 15, 2)->default(0)->comment('Current overpayment credit available');
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->index('partner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_balances');
    }
};
