<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'password_change_required')) {
                $table->boolean('password_change_required')->default(false)->after('password');
            }
            if (!Schema::hasColumn('users', 'reset_requested_at')) {
                $table->timestamp('reset_requested_at')->nullable()->after('password_change_required');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['password_change_required', 'reset_requested_at']);
        });
    }
};
