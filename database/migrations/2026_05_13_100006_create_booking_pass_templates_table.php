<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('booking_pass_templates')) return;

        Schema::create('booking_pass_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('partner_id')->nullable(); // null = default for all
            $table->string('template_name');
            $table->string('template_file')->nullable();
            $table->json('field_mapping')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by');
            $table->timestamps();

            $table->foreign('partner_id')->references('id')->on('partners')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_pass_templates');
    }
};
