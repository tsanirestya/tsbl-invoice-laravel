<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoices')) {
            return;
        }

        Schema::create('invoices', function (Blueprint $table) {
            $table->increments('id');
            $table->string('invoice_no', 50)->unique();
            $table->unsignedInteger('partner_id');
            $table->string('guest_name', 200)->nullable();
            $table->date('visit_date')->nullable();
            $table->string('booking_pass_no', 100)->nullable();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('dsi_transaction_no', 100)->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('deposit', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->text('terbilang')->nullable();
            $table->enum('payment_status', ['UNPAID', 'PARTIAL', 'PAID', 'OVERDUE'])->default('UNPAID');
            $table->text('notes')->nullable();
            $table->string('pdf_path')->nullable();
            $table->boolean('is_finalized')->default(false);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->datetime('created_at')->nullable();
            $table->datetime('updated_at')->nullable();
            $table->index('partner_id');
            $table->index('payment_status');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
