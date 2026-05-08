<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('partners', 'credit_class_id')) {
            return;
        }

        Schema::table('partners', function (Blueprint $table) {
            $table->foreignId('credit_class_id')
                ->nullable()
                ->after('limit_credit')
                ->constrained('credit_classes')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('partners', 'credit_class_id')) {
            return;
        }

        Schema::table('partners', function (Blueprint $table) {
            $table->dropForeign(['credit_class_id']);
            $table->dropColumn('credit_class_id');
        });
    }
};
