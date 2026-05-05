<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('partners')) {
            return;
        }

        Schema::create('partners', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('partner_type', ['HOTEL', 'TRAVEL', 'TOURDESK']);
            $table->string('nama_partner', 200);
            $table->string('category', 100)->nullable();
            $table->string('channel', 100)->nullable();
            $table->string('nama_pt', 200)->nullable();
            $table->string('pic_tsbl', 150)->nullable();
            $table->string('pic_partner', 150)->nullable();
            $table->string('pic_partner_phone', 30)->nullable();
            $table->string('pic_partner_email', 150)->nullable();
            $table->text('address')->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_no', 50)->nullable();
            $table->string('bank_account_name', 150)->nullable();
            $table->string('npwp', 30)->nullable();
            $table->string('payment_type', 50)->nullable();
            $table->integer('payment_due_days')->default(14);
            $table->decimal('limit_credit', 15, 2)->default(0);
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            $table->string('doc_akta_pendirian')->nullable();
            $table->string('doc_akta_perubahan')->nullable();
            $table->string('doc_surat_kuasa')->nullable();
            $table->string('doc_ktp')->nullable();
            $table->string('doc_nib')->nullable();
            $table->string('doc_npwp')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->datetime('created_at')->nullable();
            $table->datetime('updated_at')->nullable();
            $table->index('partner_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
