<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'payment_method')) {
                $table->string('payment_method', 30)->nullable()->after('notes')
                      ->comment('transfer_nett, transfer_gross, ots_nett, ots_gross');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
    }
};
