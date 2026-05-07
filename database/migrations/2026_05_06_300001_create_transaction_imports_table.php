<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('transaction_imports')) {
            Schema::create('transaction_imports', function (Blueprint $table) {
                $table->increments('id');
                $table->uuid('uuid')->unique();
                $table->string('filename');
                $table->string('original_filename');
                $table->unsignedInteger('uploaded_by');
                $table->foreign('uploaded_by')->references('id')->on('users');
                $table->timestamp('uploaded_at')->useCurrent();
                $table->enum('status', ['pending', 'processing', 'reviewed', 'done'])->default('pending');
                $table->unsignedInteger('total_rows')->default(0);
                $table->unsignedInteger('valid_rows')->default(0);
                $table->unsignedInteger('anomaly_rows')->default(0);
                $table->unsignedInteger('rejected_rows')->default(0);
                $table->timestamp('processed_at')->nullable();
                $table->unsignedInteger('reviewed_by')->nullable();
                $table->foreign('reviewed_by')->references('id')->on('users');
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_imports');
    }
};
