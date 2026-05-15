<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('reservations', 'booking_pass_type')) {
                $table->enum('booking_pass_type', ['DEFAULT', 'CUSTOM'])->default('DEFAULT')->after('payment_channel');
            }
            if (!Schema::hasColumn('reservations', 'booking_pass_template_id')) {
                $table->unsignedInteger('booking_pass_template_id')->nullable()->after('booking_pass_type');
            }
            if (!Schema::hasColumn('reservations', 'booking_pass_file')) {
                $table->string('booking_pass_file')->nullable()->after('booking_pass_template_id');
            }
            if (!Schema::hasColumn('reservations', 'booking_pass_data')) {
                $table->json('booking_pass_data')->nullable()->after('booking_pass_file');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['booking_pass_type', 'booking_pass_template_id', 'booking_pass_file', 'booking_pass_data']);
        });
    }
};
