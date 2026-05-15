<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reservations')) return;

        Schema::create('reservations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('reservation_no', 25)->unique(); // RES-YYYYMMDD-XXXX
            $table->unsignedInteger('partner_id')->nullable();
            $table->string('guest_name');
            $table->string('guest_country', 100)->nullable();
            $table->date('visit_date');
            $table->enum('status', ['PENDING', 'CONFIRMED', 'CANCELLED', 'NO_SHOW', 'COMPLETED'])->default('PENDING');
            $table->enum('reservation_type', ['PARTNER', 'INTERNAL', 'SELF_SERVICE']);
            $table->enum('payment_method', ['TRANSFER_GROSS', 'TRANSFER_NETT', 'ON_THE_SPOT'])->nullable();
            $table->enum('payment_channel', ['CASH', 'DEBIT', 'CREDIT'])->nullable();
            $table->enum('booking_pass_type', ['DEFAULT', 'CUSTOM'])->default('DEFAULT');
            $table->unsignedInteger('booking_pass_template_id')->nullable();
            $table->string('booking_pass_file')->nullable();
            $table->json('booking_pass_data')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('location_name')->nullable();
            $table->boolean('is_danger_zone')->default(false);
            $table->string('room_key_photo')->nullable();
            $table->string('partner_name_input')->nullable();
            $table->enum('customer_origin', ['HOTEL', 'TRAVEL_AGENT', 'WALK_IN', 'ONLINE_AD', 'OTHER'])->nullable();
            $table->text('customer_origin_detail')->nullable();
            $table->boolean('is_spot_check')->default(false);
            $table->integer('fraud_score_snapshot')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_fingerprint')->nullable();
            $table->string('qr_token', 100)->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('partner_id')->references('id')->on('partners')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
