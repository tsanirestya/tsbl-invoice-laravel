<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('reservations', 'pax_adults')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->unsignedTinyInteger('pax_adults')->default(1)->after('guest_country');
            });
        }
        if (!Schema::hasColumn('reservations', 'pax_kids')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->unsignedTinyInteger('pax_kids')->default(0)->after('pax_adults');
            });
        }
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['pax_adults', 'pax_kids']);
        });
    }
};
