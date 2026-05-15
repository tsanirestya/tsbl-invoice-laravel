<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('daily_qr_codes')) return;

        Schema::create('daily_qr_codes', function (Blueprint $table) {
            $table->increments('id');
            $table->date('date')->unique();
            $table->string('token', 64)->unique();
            $table->string('qr_image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('generated_by');
            $table->timestamps();

            $table->foreign('generated_by')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_qr_codes');
    }
};
