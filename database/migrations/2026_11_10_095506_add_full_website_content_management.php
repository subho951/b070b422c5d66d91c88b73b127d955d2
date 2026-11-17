<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_sections', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 120)->unique();
            $table->string('scope', 40)->index();
            $table->json('data');
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::table('pages', function (Blueprint $table): void {
            $table->string('eyebrow')->nullable();
        });
        Schema::table('testimonials', function (Blueprint $table): void {
            $table->foreignId('featured_image_id')->nullable()->constrained('media_assets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('featured_image_id');
        });
        Schema::table('pages', function (Blueprint $table): void {
            $table->dropColumn('eyebrow');
        });
        Schema::dropIfExists('website_sections');
    }
};
