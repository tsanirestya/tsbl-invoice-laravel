<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_memos')) return;

        Schema::create('payment_memos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('memo_no', 30)->unique();
            $table->unsignedInteger('partner_id');
            $table->date('memo_date');
            $table->date('payment_deadline');
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamps();

            $table->foreign('partner_id')->references('id')->on('partners')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_memos');
    }
};
