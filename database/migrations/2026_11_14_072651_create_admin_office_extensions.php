<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_records', function (Blueprint $table): void {
            $table->id();
            $table->string('module', 40)->index();
            $table->string('name', 180);
            $table->json('data');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('wallet_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->char('currency', 3);
            $table->foreign('currency')->references('code')->on('currencies')->restrictOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('reference', 100)->unique();
            $table->string('direction', 10);
            $table->decimal('amount', 20, 3);
            $table->date('transaction_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['customer_id', 'currency']);
        });
        Schema::table('customers', function (Blueprint $table): void {
            $table->date('renewal_date')->nullable();
            $table->decimal('renewal_amount', 20, 3)->nullable();
            $table->string('domain_provider')->nullable();
            $table->string('server_provider')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn(['renewal_date', 'renewal_amount', 'domain_provider', 'server_provider']);
        });
        Schema::dropIfExists('wallet_entries');
        Schema::dropIfExists('office_records');
    }
};
