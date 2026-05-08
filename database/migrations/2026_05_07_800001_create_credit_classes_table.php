<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('credit_classes')) {
            return;
        }

        Schema::create('credit_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('color', 20)->default('secondary'); // Bootstrap color
            $table->decimal('min_limit', 15, 2)->default(0);
            $table->decimal('max_limit', 15, 2)->nullable(); // null = unlimited
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_classes');
    }
};
