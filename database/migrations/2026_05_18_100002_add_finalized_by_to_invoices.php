<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'finalized_by')) {
                $table->unsignedInteger('finalized_by')->nullable()->after('is_finalized');
                $table->foreign('finalized_by')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('invoices', 'finalized_by_signature')) {
                // Snapshot base64 signature dari Finance Manager saat finalize
                $table->longText('finalized_by_signature')->nullable()->after('finalized_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'finalized_by_signature')) {
                $table->dropColumn('finalized_by_signature');
            }
            if (Schema::hasColumn('invoices', 'finalized_by')) {
                $table->dropForeign(['finalized_by']);
                $table->dropColumn('finalized_by');
            }
        });
    }
};
