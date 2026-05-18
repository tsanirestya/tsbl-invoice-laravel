<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Expand enum to include BOTH old and new values so existing data stays valid
        DB::statement("ALTER TABLE users MODIFY COLUMN user_status ENUM(
            'ADMIN','FINANCE','SALES','VIEWER','ADMISSION',
            'IT','BUSDEV_HO','FINANCE_STAFF','FINANCE_MANAGER','BPM','RESERVATION_STAFF'
        ) NOT NULL DEFAULT 'FINANCE_STAFF'");

        // Step 2: Map old roles → new roles
        DB::table('users')->where('user_status', 'FINANCE')->update(['user_status' => 'FINANCE_MANAGER']);
        DB::table('users')->where('user_status', 'SALES')->update(['user_status' => 'BPM']);
        DB::table('users')->where('user_status', 'VIEWER')->update(['user_status' => 'FINANCE_STAFF']);

        // Step 3: Remove old enum values — only new 8 remain
        DB::statement("ALTER TABLE users MODIFY COLUMN user_status ENUM(
            'ADMIN',
            'IT',
            'BUSDEV_HO',
            'FINANCE_STAFF',
            'FINANCE_MANAGER',
            'BPM',
            'RESERVATION_STAFF',
            'ADMISSION'
        ) NOT NULL DEFAULT 'FINANCE_STAFF'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN user_status ENUM(
            'ADMIN',
            'FINANCE',
            'SALES',
            'VIEWER',
            'ADMISSION'
        ) NOT NULL DEFAULT 'VIEWER'");
    }
};
