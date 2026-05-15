<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reservation_payments')) return;

        Schema::create('reservation_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('reservation_id');
            $table->enum('payment_method', ['TRANSFER_GROSS', 'TRANSFER_NETT', 'ON_THE_SPOT']);
            $table->enum('payment_channel', ['CASH', 'DEBIT', 'CREDIT'])->nullable();
            $table->decimal('gross_amount', 15, 2)->default(0);
            $table->decimal('nett_amount', 15, 2)->default(0);
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->boolean('is_commission_eligible')->default(false);
            $table->enum('payment_status', ['PENDING', 'PAID', 'VERIFIED'])->default('PENDING');
            $table->string('proof_file')->nullable();
            $table->boolean('is_commission_held')->default(false);
            $table->text('commission_hold_reason')->nullable();
            $table->unsignedInteger('commission_released_by')->nullable();
            $table->datetime('commission_released_at')->nullable();
            $table->unsignedInteger('verified_by')->nullable();
            $table->datetime('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('reservation_id')->references('id')->on('reservations')->cascadeOnDelete();
            $table->foreign('commission_released_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_payments');
    }
};
