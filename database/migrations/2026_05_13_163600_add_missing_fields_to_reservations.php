<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('reservations', 'total_amount')) {
                $table->decimal('total_amount', 15, 2)->default(0)->after('notes');
            }
            if (!Schema::hasColumn('reservations', 'room_key_photo')) {
                $table->string('room_key_photo')->nullable()->after('total_amount');
            }
            if (!Schema::hasColumn('reservations', 'partner_name_input')) {
                $table->string('partner_name_input')->nullable()->after('room_key_photo');
            }
            if (!Schema::hasColumn('reservations', 'fraud_score_snapshot')) {
                $table->integer('fraud_score_snapshot')->default(0)->after('partner_name_input');
            }
            if (!Schema::hasColumn('reservations', 'device_fingerprint')) {
                $table->string('device_fingerprint')->nullable()->after('fraud_score_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn([
                'total_amount', 'room_key_photo', 'partner_name_input',
                'fraud_score_snapshot', 'device_fingerprint'
            ]);
        });
    }
};
