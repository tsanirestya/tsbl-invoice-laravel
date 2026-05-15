<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_partner_checks')) return;

        Schema::create('employee_partner_checks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('partner_id');
            $table->unsignedInteger('user_id');
            $table->enum('match_type', ['PHONE', 'EMAIL', 'BANK_ACCOUNT', 'ADDRESS', 'NAME']);
            $table->text('match_detail');
            $table->boolean('is_reviewed')->default(false);
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->datetime('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['partner_id', 'user_id', 'match_type']);
            $table->foreign('partner_id')->references('id')->on('partners')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_partner_checks');
    }
};
