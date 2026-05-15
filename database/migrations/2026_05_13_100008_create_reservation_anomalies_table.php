<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reservation_anomalies')) return;

        Schema::create('reservation_anomalies', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('reservation_id');
            $table->string('anomaly_type', 50);
            $table->enum('severity', ['WARNING', 'CRITICAL']);
            $table->text('detail');
            $table->integer('score_impact')->default(0);
            $table->boolean('is_resolved')->default(false);
            $table->unsignedInteger('resolved_by')->nullable();
            $table->datetime('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->enum('resolution_type', ['CLEARED', 'CONFIRMED_FRAUD', 'FALSE_POSITIVE'])->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['reservation_id', 'anomaly_type']);
            $table->foreign('reservation_id')->references('id')->on('reservations')->cascadeOnDelete();
            $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_anomalies');
    }
};
