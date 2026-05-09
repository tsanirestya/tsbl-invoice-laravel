<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'invoice_type')) {
                $table->enum('invoice_type', [
                    'PROFORMA', 'FINAL', 'CREDIT_NOTE', 'DEBIT_NOTE', 'CANCELLATION',
                ])->default('PROFORMA')->after('invoice_no');
            }

            if (!Schema::hasColumn('invoices', 'parent_invoice_id')) {
                $table->unsignedInteger('parent_invoice_id')->nullable()
                    ->after('invoice_type')
                    ->comment('For CN/DN: references the FINAL invoice');
            }

            if (!Schema::hasColumn('invoices', 'replaces_invoice_id')) {
                $table->unsignedInteger('replaces_invoice_id')->nullable()
                    ->after('parent_invoice_id')
                    ->comment('For CANCELLATION: references voided invoice');
            }

            if (!Schema::hasColumn('invoices', 'delta_amount')) {
                $table->decimal('delta_amount', 15, 2)->nullable()
                    ->after('replaces_invoice_id')
                    ->comment('Proforma vs DSI delta — populated by DeltaCalculatorService');
            }

            if (!Schema::hasColumn('invoices', 'source_type')) {
                $table->string('source_type', 100)->nullable()
                    ->after('delta_amount')
                    ->comment('Polymorphic source: reservation, reconciliation, etc.');
            }

            if (!Schema::hasColumn('invoices', 'source_id')) {
                $table->unsignedInteger('source_id')->nullable()->after('source_type');
            }

            if (!Schema::hasColumn('invoices', 'is_locked')) {
                $table->boolean('is_locked')->default(false)
                    ->after('source_id')
                    ->comment('Locked invoices cannot be modified — enforced by InvoiceObserver');
            }

            if (!Schema::hasColumn('invoices', 'lock_reason')) {
                $table->string('lock_reason', 255)->nullable()->after('is_locked');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $columns = [
                'invoice_type', 'parent_invoice_id', 'replaces_invoice_id',
                'delta_amount', 'source_type', 'source_id', 'is_locked', 'lock_reason',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('invoices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
