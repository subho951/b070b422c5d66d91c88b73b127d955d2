<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WebsiteSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('howvanta.services') as $slug => [$title, $description]) {
            DB::table('services')->insertOrIgnore(['slug' => $slug, 'title' => $title, 'description' => $description, 'content' => $description, 'status' => 'published', 'published_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        }
        $pages = [
            'home' => ["Engineering What's Next.", 'We build custom software, AI-powered applications and cloud-ready platforms for growing businesses.'],
            'about' => [config('company-content.pages.about.title'), config('company-content.pages.about.description')],
            'contact' => ["Let's build what's next.", 'Have a product idea or a workflow to improve? Tell us what you are trying to achieve. We can discuss a practical next step.'],
            'services' => ['Your next challenge. Our engineering.', 'Practical technology services. One connected approach. From the first sketch to ongoing support, we build technology around your business.'],
            'portfolio' => ['Projects built around real needs.', 'Explore our work on ALFA Network and E School, from member networking to school administration.'],
            'testimonials' => [config('company-content.pages.testimonials.title'), config('company-content.pages.testimonials.description')],
        ];
        foreach ($pages as $slug => [$title, $description]) {
            DB::table('pages')->insertOrIgnore(['slug' => $slug, 'title' => $title, 'description' => $description, 'status' => 'published', 'published_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (['maintenance_enabled' => '0', 'maintenance_message' => 'We are making a few improvements. Please check back soon or contact us directly.', 'contact_email' => 'info@howvanta.com', 'contact_phone' => '+91 62893 39520', 'contact_location' => 'Howrah, West Bengal, India'] as $key => $value) {
            DB::table('site_settings')->insertOrIgnore(['key' => $key, 'value' => $value, 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->call(FrontendContentSeeder::class);
        $this->call(FeaturedProjectSeeder::class);
    }
}
