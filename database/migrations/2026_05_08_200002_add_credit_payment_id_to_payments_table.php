<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments') || Schema::hasColumn('payments', 'credit_payment_id')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedInteger('credit_payment_id')->nullable()->after('created_by');
            $table->index('credit_payment_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('payments', 'credit_payment_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('credit_payment_id');
            });
        }
    }
};
