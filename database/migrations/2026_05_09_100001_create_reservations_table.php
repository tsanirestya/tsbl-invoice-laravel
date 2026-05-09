<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reservations')) {
            return;
        }

        Schema::create('reservations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('reservation_no', 50)->unique();
            $table->unsignedInteger('partner_id');
            $table->string('guest_name', 200)->nullable();
            $table->date('visit_date')->nullable();
            $table->enum('booking_source', ['DIRECT', 'OTA', 'AGENT', 'WALK_IN'])->default('DIRECT');
            $table->enum('status', [
                'PENDING', 'CONFIRMED', 'CANCELLED', 'CHECKED_IN', 'CHECKED_OUT', 'NO_SHOW',
            ])->default('PENDING');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('partner_id');
            $table->index('status');
            $table->index('visit_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
