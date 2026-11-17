<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->string('prefix', 30);
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
        });
        DB::table('document_sequences')->insert(['key' => 'invoice', 'prefix' => 'INV-', 'last_number' => 0, 'created_at' => now(), 'updated_at' => now()]);
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 60)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->date('issue_date')->index();
            $table->date('due_date')->index();
            $table->char('currency', 3);
            $table->unsignedTinyInteger('currency_decimal_places')->default(2);
            $table->string('customer_name', 180);
            $table->string('customer_email');
            $table->text('billing_address');
            $table->string('billing_country', 100);
            $table->string('customer_tax_identifier', 100)->nullable();
            $table->string('seller_name', 180);
            $table->text('seller_address');
            $table->string('seller_email')->nullable();
            $table->string('seller_tax_identifier', 100)->nullable();
            $table->string('tax_label', 60)->nullable();
            $table->decimal('subtotal', 20, 3)->default(0);
            $table->decimal('discount_total', 20, 3)->default(0);
            $table->decimal('tax_total', 20, 3)->default(0);
            $table->decimal('grand_total', 20, 3)->default(0);
            $table->string('status', 30)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->text('payment_terms')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->foreign('currency')->references('code')->on('currencies')->restrictOnDelete();
            $table->foreign(['project_id', 'customer_id'])->references(['id', 'customer_id'])->on('projects')->restrictOnDelete();
            $table->unique(['id', 'currency']);
            $table->index(['status', 'due_date']);
            $table->index(['customer_id', 'issue_date']);
            $table->index(['currency', 'issue_date']);
        });
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->text('description');
            $table->decimal('quantity', 12, 4)->default(1);
            $table->decimal('unit_price', 20, 6);
            $table->decimal('discount_amount', 20, 3)->default(0);
            $table->string('tax_label', 60)->nullable();
            $table->decimal('tax_rate', 7, 4)->default(0);
            $table->decimal('subtotal', 20, 3);
            $table->decimal('tax_amount', 20, 3)->default(0);
            $table->decimal('total', 20, 3);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['invoice_id', 'sort_order']);
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 100)->unique();
            $table->unsignedBigInteger('invoice_id');
            $table->char('currency', 3);
            $table->decimal('amount', 20, 3);
            $table->date('payment_date')->index();
            $table->string('method', 40);
            $table->string('external_reference', 180)->nullable();
            $table->string('status', 20)->default('recorded');
            $table->text('notes')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->foreign(['invoice_id', 'currency'])->references(['id', 'currency'])->on('invoices')->restrictOnDelete();
            $table->index(['invoice_id', 'status']);
            $table->index(['currency', 'payment_date']);
        });
        Schema::create('invoice_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['invoice_id', 'created_at']);
        });
        Schema::create('payment_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['payment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['payment_status_history', 'invoice_status_history', 'payments', 'invoice_items', 'invoices', 'document_sequences'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
