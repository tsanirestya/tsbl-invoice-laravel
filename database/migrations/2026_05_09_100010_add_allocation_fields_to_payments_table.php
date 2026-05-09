<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'amount_allocated')) {
                $table->decimal('amount_allocated', 15, 2)->default(0)
                    ->after('amount')
                    ->comment('Sum of payment_allocations.amount_allocated for this payment');
            }

            if (!Schema::hasColumn('payments', 'amount_unallocated')) {
                $table->decimal('amount_unallocated', 15, 2)->default(0)
                    ->after('amount_allocated')
                    ->comment('amount - amount_allocated; updated by PaymentAllocatorService');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            foreach (['amount_allocated', 'amount_unallocated'] as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
