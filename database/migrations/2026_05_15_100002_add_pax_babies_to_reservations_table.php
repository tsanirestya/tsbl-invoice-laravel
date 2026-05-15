<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('reservations', 'pax_babies')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->tinyInteger('pax_babies')->default(0)->after('pax_kids');
            });
        }
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('pax_babies');
        });
    }
};
