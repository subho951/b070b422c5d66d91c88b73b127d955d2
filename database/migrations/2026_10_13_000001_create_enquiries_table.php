<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->string('role', 20)->default('staff')->index());
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email');
            $table->string('company', 180);
            $table->string('country', 100);
            $table->string('phone', 40)->nullable();
            $table->string('project_type', 100);
            $table->string('budget', 100)->nullable();
            $table->string('timeline', 100)->nullable();
            $table->text('description');
            $table->boolean('privacy_consent');
            $table->string('status', 20)->default('new')->index();
            $table->timestamps();
            $table->index(['created_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
    }
};
