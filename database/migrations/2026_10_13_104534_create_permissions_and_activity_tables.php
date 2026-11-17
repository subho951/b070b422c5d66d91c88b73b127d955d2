<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('module', 60);
            $table->boolean('can_view')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_update')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'module']);
        });
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('subject_type', 120);
            $table->unsignedBigInteger('subject_id');
            $table->string('event', 80);
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subject_type', 'subject_id', 'created_at']);
            $table->index(['event', 'created_at']);
        });
        Schema::create('activity_log_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_log_id')->constrained()->restrictOnDelete();
            $table->string('field', 100);
            $table->longText('old_value')->nullable();
            $table->longText('new_value')->nullable();
            $table->unique(['activity_log_id', 'field']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log_changes');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('user_permissions');
    }
};
