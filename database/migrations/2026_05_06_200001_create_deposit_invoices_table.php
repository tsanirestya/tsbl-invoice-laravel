<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deposit_invoices')) {
            return;
        }

        Schema::create('deposit_invoices', function (Blueprint $table) {
            $table->increments('id');
            $table->string('invoice_no', 50)->unique();
            $table->unsignedInteger('partner_id');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('amount', 15, 2);              // jumlah deposit yang diminta
            $table->string('terbilang', 500)->nullable();
            $table->enum('status', ['DRAFT', 'SENT', 'PAID', 'CANCELLED'])->default('DRAFT');
            $table->text('notes')->nullable();
            $table->string('pdf_path', 500)->nullable();
            $table->boolean('is_finalized')->default(false);
            // link ke partner_deposits ketika deposit sudah diterima (no FK constraint to avoid engine mismatch)
            $table->unsignedInteger('deposit_id')->nullable();
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('partner_id')->references('id')->on('partners')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_invoices');
    }
};
