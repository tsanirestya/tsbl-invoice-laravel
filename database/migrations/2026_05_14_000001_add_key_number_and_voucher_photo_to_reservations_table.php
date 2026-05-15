<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('reservations', 'key_number')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->string('key_number')->nullable()->after('room_key_photo');
            });
        }

        if (!Schema::hasColumn('reservations', 'voucher_photo')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->string('voucher_photo')->nullable()->after('key_number');
            });
        }
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['key_number', 'voucher_photo']);
        });
    }
};
