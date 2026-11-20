<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientTestimonialsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (config('client-testimonials') as $index => [$key, $name, $title, $service, $feedback, $existingReview]) {
                if (DB::table('testimonials')->where('source_key', $key)->exists()
                    || DB::table('testimonials')->where('name', $name)->where('description', $feedback)->exists()) {
                    continue;
                }

                DB::table('testimonials')->insertOrIgnore([
                    'source_key' => $key,
                    'name' => $name,
                    'title' => $title,
                    'service_label' => $service,
                    'description' => $feedback,
                    'rating' => $existingReview ? 5 : null,
                    'reviewed_on' => $existingReview ? '2026-11-13' : null,
                    'consent_confirmed' => $existingReview,
                    'status' => $existingReview ? 'published' : 'draft',
                    'published_at' => $existingReview ? now() : null,
                    'show_on_homepage' => $existingReview,
                    'sort_order' => ($index + 1) * 10,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
