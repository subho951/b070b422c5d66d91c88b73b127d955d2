<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('follow_up_at')->nullable()->index();
            $table->foreignId('converted_customer_id')->nullable()->constrained('customers')->restrictOnDelete();
            $table->foreignId('converted_project_id')->nullable()->constrained('projects')->restrictOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->softDeletes();
        });
        Schema::create('enquiry_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enquiry_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['enquiry_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiry_notes');
        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropConstrainedForeignId('converted_customer_id');
            $table->dropConstrainedForeignId('converted_project_id');
            $table->dropIndex(['follow_up_at']);
            $table->dropColumn(['follow_up_at', 'converted_at', 'deleted_at']);
        });
    }
};
