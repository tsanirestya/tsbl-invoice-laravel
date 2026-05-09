<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dsi_import_batches')) {
            return;
        }

        Schema::create('dsi_import_batches', function (Blueprint $table) {
            $table->increments('id');
            $table->string('batch_ref', 100)->unique();
            $table->string('file_name', 255)->nullable();
            $table->string('file_hash', 64)->unique()->nullable()->comment('SHA-256 of uploaded file — layer 1 duplicate detection');
            $table->enum('source', ['CSV', 'API'])->default('CSV');
            $table->enum('status', ['PENDING', 'PROCESSING', 'COMPLETED', 'FAILED', 'PARTIAL'])->default('PENDING');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->text('error_summary')->nullable();
            $table->unsignedInteger('imported_by')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('imported_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dsi_import_batches');
    }
};
