<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add ADMISSION to users.user_status enum
        DB::statement("ALTER TABLE users MODIFY COLUMN user_status ENUM('ADMIN','FINANCE','SALES','VIEWER','ADMISSION') NOT NULL DEFAULT 'VIEWER'");

        // 2. Add REDEEMED to reservations.status enum
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('PENDING','CONFIRMED','REDEEMED','COMPLETED','CANCELLED','NO_SHOW') NOT NULL DEFAULT 'PENDING'");

        // 3. Add admission columns to reservations
        if (!Schema::hasColumn('reservations', 'redeemed_at')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dateTime('redeemed_at')->nullable()->after('status');
                $table->unsignedBigInteger('redeemed_by')->nullable()->after('redeemed_at');
                $table->enum('transaction_match', ['MATCH', 'MISMATCH', 'PENDING_CHECK'])->default('PENDING_CHECK')->after('redeemed_by');
                $table->text('transaction_notes')->nullable()->after('transaction_match');
                $table->json('actual_items')->nullable()->after('transaction_notes');
            });
        }

        // 4. Insert admission visit date tolerance setting
        DB::table('settings')->insertOrIgnore([
            'key'   => 'admission_visit_date_tolerance_days',
            'value' => '0',
        ]);
    }

    public function down(): void
    {
        // Remove admission columns
        if (Schema::hasColumn('reservations', 'redeemed_at')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropColumn(['redeemed_at', 'redeemed_by', 'transaction_match', 'transaction_notes', 'actual_items']);
            });
        }

        // Revert enums
        DB::statement("ALTER TABLE users MODIFY COLUMN user_status ENUM('ADMIN','FINANCE','SALES','VIEWER') NOT NULL DEFAULT 'VIEWER'");
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('PENDING','CONFIRMED','COMPLETED','CANCELLED','NO_SHOW') NOT NULL DEFAULT 'PENDING'");

        DB::table('settings')->where('key', 'admission_visit_date_tolerance_days')->delete();
    }
};
