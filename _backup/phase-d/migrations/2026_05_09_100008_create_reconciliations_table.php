<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reconciliations')) {
            return;
        }

        Schema::create('reconciliations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('reservation_id');
            $table->unsignedInteger('proforma_invoice_id');
            $table->unsignedInteger('dsi_transaction_id')->nullable();
            $table->enum('status', [
                'PENDING_REVIEW', 'APPROVED', 'DISPUTED', 'REJECTED',
            ])->default('PENDING_REVIEW');
            $table->decimal('proforma_amount', 15, 2)->default(0);
            $table->decimal('dsi_amount', 15, 2)->default(0);
            $table->decimal('delta_amount', 15, 2)->default(0)->comment('dsi_amount - proforma_amount');
            $table->text('delta_reason')->nullable();
            $table->boolean('no_show_policy_applied')->default(false);
            $table->decimal('no_show_charge_amount', 15, 2)->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('dispute_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('final_invoice_id')->nullable()->comment('Populated after approval generates FINAL invoice');
            $table->timestamps();

            $table->index('reservation_id');
            $table->index('status');
            $table->index('proforma_invoice_id');
            $table->index('dsi_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliations');
    }
};
