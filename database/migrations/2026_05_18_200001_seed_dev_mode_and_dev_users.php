<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Dev mode setting ───────────────────────────────────────────────
        if (!DB::table('settings')->where('key', 'dev_mode_enabled')->exists()) {
            DB::table('settings')->insert(['key' => 'dev_mode_enabled', 'value' => '0']);
        }

        // ── 2. Dev users (one per role) ───────────────────────────────────────
        $devUsers = [
            ['ADMIN',             'dev.admin@tsbl.dev',      'Dev Admin'],
            ['IT',                'dev.it@tsbl.dev',         'Dev IT'],
            ['BUSDEV_HO',         'dev.busdev@tsbl.dev',     'Dev Busdev HO'],
            ['FINANCE_STAFF',     'dev.finstaff@tsbl.dev',   'Dev Finance Staff'],
            ['FINANCE_MANAGER',   'dev.finmanager@tsbl.dev', 'Dev Finance Manager'],
            ['BPM',               'dev.bpm@tsbl.dev',        'Dev BPM'],
            ['RESERVATION_STAFF', 'dev.reservation@tsbl.dev','Dev Reservation Staff'],
            ['ADMISSION',         'dev.admission@tsbl.dev',  'Dev Admission'],
        ];

        $password = Hash::make('admin123');
        $now      = now();

        foreach ($devUsers as [$role, $email, $name]) {
            DB::table('users')->updateOrInsert(
                ['email' => $email],
                [
                    'full_name'                => $name,
                    'email'                    => $email,
                    'password'                 => $password,
                    'user_status'              => $role,
                    'is_active'                => 1,
                    'password_change_required' => 0,
                    'position_name'            => 'Dev Account',
                    'created_at'               => $now,
                    'updated_at'               => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'dev_mode_enabled')->delete();
        DB::table('users')->where('email', 'like', '%@tsbl.dev')->delete();
    }
};
