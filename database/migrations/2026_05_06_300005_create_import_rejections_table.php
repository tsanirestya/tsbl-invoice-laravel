<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('import_rejections')) {
            Schema::create('import_rejections', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('import_id');
                $table->foreign('import_id')->references('id')->on('transaction_imports')->cascadeOnDelete();
                $table->unsignedInteger('row_index');
                $table->json('raw_data');
                $table->enum('rejection_reason', ['INVALID_TICKET_TYPE', 'NAME_PREFIX_MISMATCH', 'EMPTY_ROW']);
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('import_rejections');
    }
};
