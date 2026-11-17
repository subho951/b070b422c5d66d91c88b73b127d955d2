<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->char('code', 3)->primary();
            $table->string('name', 80);
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        $timestamp = now();
        foreach (['INR' => ['Indian Rupee', 2], 'USD' => ['US Dollar', 2], 'GBP' => ['Pound Sterling', 2], 'AED' => ['UAE Dirham', 2], 'KWD' => ['Kuwaiti Dinar', 3]] as $code => [$name, $precision]) {
            DB::table('currencies')->insert(['code' => $code, 'name' => $name, 'decimal_places' => $precision, 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp]);
        }
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('company');
            $table->string('name', 180)->index();
            $table->string('contact_person', 120)->nullable();
            $table->string('email')->unique();
            $table->string('phone', 40)->nullable();
            $table->string('country', 100);
            $table->text('billing_address')->nullable();
            $table->string('tax_identifier', 100)->nullable();
            $table->char('billing_currency', 3)->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('billing_currency')->references('code')->on('currencies')->restrictOnDelete();
        });
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 60)->unique();
            $table->string('title')->index();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->text('description')->nullable();
            $table->string('status', 30)->default('planned')->index();
            $table->string('priority', 20)->default('normal');
            $table->date('start_date')->nullable();
            $table->date('target_completion_date')->nullable()->index();
            $table->decimal('agreed_budget', 20, 3)->nullable();
            $table->char('currency', 3);
            $table->text('internal_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('currency')->references('code')->on('currencies')->restrictOnDelete();
            $table->unique(['id', 'customer_id']);
            $table->index(['customer_id', 'status']);
        });
        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 30)->default('pending');
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['project_id', 'status', 'due_date']);
        });
        Schema::create('project_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['project_id', 'created_at']);
        });
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 20, 3);
            $table->char('currency', 3);
            $table->date('expense_date')->index();
            $table->string('vendor', 180)->nullable();
            $table->string('reference', 100)->nullable()->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('currency')->references('code')->on('currencies')->restrictOnDelete();
            $table->index(['currency', 'expense_date']);
            $table->index(['expense_category_id', 'expense_date']);
        });
        Schema::create('expense_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->restrictOnDelete();
            $table->string('disk', 40)->default('local');
            $table->string('path', 500);
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['expense_receipts', 'expenses', 'expense_categories', 'project_status_history', 'project_milestones', 'projects', 'customers', 'currencies'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
