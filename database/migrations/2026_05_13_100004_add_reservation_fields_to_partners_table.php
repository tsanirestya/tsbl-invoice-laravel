<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            if (!Schema::hasColumn('partners', 'reservation_token')) {
                $table->string('reservation_token', 64)->unique()->nullable()->after('notes');
            }
            if (!Schema::hasColumn('partners', 'reservation_token_expires_at')) {
                $table->datetime('reservation_token_expires_at')->nullable()->after('reservation_token');
            }
            if (!Schema::hasColumn('partners', 'known_devices')) {
                $table->json('known_devices')->nullable()->after('reservation_token_expires_at');
            }
            if (!Schema::hasColumn('partners', 'max_devices')) {
                $table->integer('max_devices')->default(3)->after('known_devices');
            }
            if (!Schema::hasColumn('partners', 'fraud_score')) {
                $table->integer('fraud_score')->default(0)->after('max_devices');
            }
            if (!Schema::hasColumn('partners', 'reservation_suspended')) {
                $table->boolean('reservation_suspended')->default(false)->after('fraud_score');
            }
            if (!Schema::hasColumn('partners', 'reservation_suspended_reason')) {
                $table->text('reservation_suspended_reason')->nullable()->after('reservation_suspended');
            }
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn([
                'reservation_token', 'reservation_token_expires_at',
                'known_devices', 'max_devices', 'fraud_score',
                'reservation_suspended', 'reservation_suspended_reason',
            ]);
        });
    }
};
