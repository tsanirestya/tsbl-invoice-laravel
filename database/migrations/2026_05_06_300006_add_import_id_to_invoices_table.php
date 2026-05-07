<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('invoices', 'import_row_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedInteger('import_row_id')->nullable()->after('dsi_transaction_no');
                $table->foreign('import_row_id')->references('id')->on('transaction_import_rows')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'import_row_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropForeign(['import_row_id']);
                $table->dropColumn('import_row_id');
            });
        }
    }
};
