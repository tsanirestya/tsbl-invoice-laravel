<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('import_anomalies')) {
            Schema::create('import_anomalies', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('import_row_id');
                $table->foreign('import_row_id')->references('id')->on('transaction_import_rows')->cascadeOnDelete();
                $table->enum('anomaly_type', [
                    'CATEGORY_MISMATCH',
                    'REVERSE_MISMATCH',
                    'PRODUCT_NOT_FOUND',
                    'PRICE_MISMATCH',
                    'SUSPICIOUS_PRICING',
                    'FUZZY_CANDIDATE',
                ]);
                $table->text('detail')->nullable();
                $table->enum('severity', ['warning', 'error'])->default('warning');
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('import_anomalies');
    }
};
