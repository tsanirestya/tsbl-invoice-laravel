<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('booking_pass_templates')) return;

        Schema::table('booking_pass_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('booking_pass_templates', 'qr_type')) {
                $table->enum('qr_type', ['qr', 'barcode'])->default('qr')->after('field_mapping');
            }
            if (!Schema::hasColumn('booking_pass_templates', 'template_type')) {
                $table->enum('template_type', ['self_service', 'internal', 'partner'])->nullable()->after('qr_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_pass_templates', function (Blueprint $table) {
            $table->dropColumn(['qr_type', 'template_type']);
        });
    }
};
