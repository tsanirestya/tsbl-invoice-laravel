<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('reservations', 'guest_country')) {
                $table->string('guest_country', 100)->nullable()->after('guest_name');
            }
            if (!Schema::hasColumn('reservations', 'reservation_type')) {
                $table->enum('reservation_type', ['PARTNER', 'INTERNAL', 'SELF_SERVICE'])->default('INTERNAL')->after('status');
            }
            if (!Schema::hasColumn('reservations', 'payment_method')) {
                $table->enum('payment_method', ['TRANSFER_GROSS', 'TRANSFER_NETT', 'ON_THE_SPOT'])->nullable()->after('reservation_type');
            }
            if (!Schema::hasColumn('reservations', 'payment_channel')) {
                $table->enum('payment_channel', ['CASH', 'DEBIT', 'CREDIT'])->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('reservations', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('notes');
            }
            if (!Schema::hasColumn('reservations', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('reservations', 'location_name')) {
                $table->string('location_name')->nullable()->after('longitude');
            }
            if (!Schema::hasColumn('reservations', 'is_danger_zone')) {
                $table->boolean('is_danger_zone')->default(false)->after('location_name');
            }
            if (!Schema::hasColumn('reservations', 'customer_origin')) {
                $table->enum('customer_origin', ['HOTEL', 'TRAVEL_AGENT', 'WALK_IN', 'ONLINE_AD', 'OTHER'])->nullable()->after('is_danger_zone');
            }
            if (!Schema::hasColumn('reservations', 'customer_origin_detail')) {
                $table->text('customer_origin_detail')->nullable()->after('customer_origin');
            }
            if (!Schema::hasColumn('reservations', 'is_spot_check')) {
                $table->boolean('is_spot_check')->default(false)->after('customer_origin_detail');
            }
            if (!Schema::hasColumn('reservations', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('is_spot_check');
            }
            if (!Schema::hasColumn('reservations', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn('reservations', 'qr_token')) {
                $table->string('qr_token', 100)->nullable()->after('user_agent');
            }
            
            // Adjust status enum if needed
            // $table->enum('status', ['PENDING', 'CONFIRMED', 'CANCELLED', 'NO_SHOW', 'COMPLETED'])->default('PENDING')->change();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn([
                'guest_country', 'reservation_type', 'payment_method', 'payment_channel',
                'latitude', 'longitude', 'location_name', 'is_danger_zone',
                'customer_origin', 'customer_origin_detail', 'is_spot_check',
                'ip_address', 'user_agent', 'qr_token'
            ]);
        });
    }
};
