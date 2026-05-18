<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('commission_release_requests')) {
            return;
        }

        Schema::create('commission_release_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('reservation_payment_id');
            $table->enum('action', ['release', 'revoke']);
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedInteger('requested_by');
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->foreign('reservation_payment_id')
                ->references('id')->on('reservation_payments')
                ->cascadeOnDelete();
            $table->foreign('requested_by')
                ->references('id')->on('users')
                ->restrictOnDelete();
            $table->foreign('reviewed_by')
                ->references('id')->on('users')
                ->nullOnDelete();

            // Satu payment hanya boleh punya satu request pending pada satu waktu
            $table->index(['reservation_payment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_release_requests');
    }
};
