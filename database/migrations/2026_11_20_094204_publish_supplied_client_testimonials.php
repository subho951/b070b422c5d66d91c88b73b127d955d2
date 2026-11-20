<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table): void {
            $table->string('source_key', 100)->nullable()->unique();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->date('reviewed_on')->nullable();
            $table->string('service_label')->nullable();
            $table->boolean('show_on_homepage')->default(true);
        });

        $copy = config('company-content.pages.testimonials');
        DB::table('pages')->where('slug', 'testimonials')->where('description', 'like', '%Google Business profile%')
            ->update(['description' => $copy['description'], 'updated_at' => now()]);

        foreach (['privacy.body' => 'Google services and external content', 'terms.body' => 'Testimonials and Google reviews'] as $key => $previousTitle) {
            $record = DB::table('website_sections')->where('key', $key)->first();
            if (! $record) {
                continue;
            }

            $data = json_decode($record->data, true, 512, JSON_THROW_ON_ERROR);
            $replacementTitle = $key === 'terms.body' ? 'Client testimonials' : $previousTitle;
            $replacement = collect(config('company-content.sections')[$key]['items'])->firstWhere('title', $replacementTitle);
            foreach ($data['items'] ?? [] as $index => $item) {
                if (($item['title'] ?? null) === $previousTitle) {
                    $data['items'][$index] = $replacement;
                    DB::table('website_sections')->where('id', $record->id)->update([
                        'data' => json_encode($data, JSON_THROW_ON_ERROR),
                        'updated_at' => now(),
                    ]);
                    break;
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table): void {
            $table->dropUnique(['source_key']);
            $table->dropColumn(['source_key', 'rating', 'reviewed_on', 'service_label', 'show_on_homepage']);
        });
    }
};
