<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            // Drop wrong constraint from previous F-024 attempt (1 row = 1 invoice was incorrect)
            if (Schema::hasColumn('invoices', 'import_row_id')) {
                $indexes = collect(
                    DB::select("SHOW INDEX FROM invoices WHERE Key_name = 'invoices_import_row_id_unique'")
                );
                if ($indexes->isNotEmpty()) {
                    // Must drop FK first — MySQL forbids dropping an index used by a FK constraint
                    $fks = collect(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME='invoices' AND COLUMN_NAME='import_row_id' AND REFERENCED_TABLE_NAME IS NOT NULL AND TABLE_SCHEMA=DATABASE()"));
                    foreach ($fks as $fk) {
                        $table->dropForeign($fk->CONSTRAINT_NAME);
                    }
                    $table->dropUnique(['import_row_id']);
                    // Re-add FK without the unique constraint
                    $fks2 = collect(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME='invoices' AND COLUMN_NAME='import_row_id' AND REFERENCED_TABLE_NAME IS NOT NULL AND TABLE_SCHEMA=DATABASE()"));
                    if ($fks2->isEmpty() && Schema::hasColumn('invoices', 'import_row_id')) {
                        $table->foreign('import_row_id')->references('id')->on('transaction_import_rows')->nullOnDelete();
                    }
                }
            }

            // Correct fix: 1 dsi_transaction_no = 1 invoice
            // NULL values are exempt — manual invoices without DSI transaction remain unrestricted
            $indexes = collect(
                DB::select("SHOW INDEX FROM invoices WHERE Key_name = 'invoices_dsi_transaction_no_unique'")
            );
            if ($indexes->isEmpty()) {
                $table->unique('dsi_transaction_no');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            $indexes = collect(
                DB::select("SHOW INDEX FROM invoices WHERE Key_name = 'invoices_dsi_transaction_no_unique'")
            );
            if ($indexes->isNotEmpty()) {
                $table->dropUnique(['dsi_transaction_no']);
            }
        });
    }
};
