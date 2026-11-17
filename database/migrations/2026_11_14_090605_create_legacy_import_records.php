<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_import_records', function (Blueprint $table): void {
            $table->id();
            $table->string('source_database', 100);
            $table->string('source_table', 100);
            $table->string('source_key', 100);
            $table->string('target_table', 100)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->longText('encrypted_payload');
            $table->timestamps();
            $table->unique(['source_database', 'source_table', 'source_key'], 'legacy_source_unique');
        });
        Schema::create('legacy_customer_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legacy_import_record_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('company_reference', 100)->nullable();
            $table->string('type', 20);
            $table->decimal('amount', 20, 3);
            $table->decimal('balance_due', 20, 3);
            $table->date('transaction_date');
            $table->string('invoice_reference')->nullable();
            $table->string('payment_method', 100)->nullable();
            $table->string('external_reference')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_customer_transactions');
        Schema::dropIfExists('legacy_import_records');
    }
};
