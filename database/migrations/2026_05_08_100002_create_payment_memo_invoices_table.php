<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_memo_invoices')) return;

        Schema::create('payment_memo_invoices', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('payment_memo_id');
            $table->unsignedInteger('invoice_id');
            $table->decimal('grand_total', 15, 2);     // snapshot saat memo dibuat
            $table->decimal('sisa_tagihan', 15, 2);    // snapshot sisa bayar saat memo dibuat
            $table->timestamps();

            $table->foreign('payment_memo_id')->references('id')->on('payment_memos')->cascadeOnDelete();
            $table->foreign('invoice_id')->references('id')->on('invoices')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_memo_invoices');
    }
};
