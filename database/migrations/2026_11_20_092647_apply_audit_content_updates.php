<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (config('portfolio-catalog.legacy') as $previousSlug => [$slug, $title]) {
                $record = DB::table('portfolio_projects')->where('slug', $previousSlug)->whereNull('deleted_at')->lockForUpdate()->first();
                if (! $record || DB::table('portfolio_projects')->where('slug', $slug)->exists()) {
                    continue;
                }

                DB::table('portfolio_projects')->where('id', $record->id)->update([
                    'slug' => $slug,
                    'title' => mb_strtoupper($record->title) === $record->title ? $title : $record->title,
                    'updated_at' => now(),
                ]);
            }

            DB::table('pages')->where('slug', 'portfolio')
                ->where('description', 'Explore our work on ALFA Network and E School, from member networking to school administration.')
                ->update(['description' => config('portfolio-catalog.intro_description'), 'updated_at' => now()]);
            DB::table('pages')->where('slug', 'home')
                ->where('description', 'We help growing businesses in India and worldwide replace manual workflows, connect their operations and build web and mobile applications, with ongoing support after launch.')
                ->update(['description' => config('website.home_page.description'), 'updated_at' => now()]);
            DB::table('site_settings')->where('key', 'branding.header_button_label')->where('value', "Let's talk")
                ->update(['value' => 'Start a Project', 'updated_at' => now()]);

            $this->updateSection('home.hero', function (array $data): array {
                foreach ($data['items'] ?? [] as $index => $item) {
                    if (($item['title'] ?? null) === 'Request a Consultation') {
                        $data['items'][$index]['title'] = 'Start a Project';
                        if (($item['url'] ?? null) === '/contact?intent=consultation#enquiry') {
                            $data['items'][$index]['url'] = '/contact#enquiry';
                        }
                    }
                }

                return $data;
            });
            $this->updateSection('shared.cta', function (array $data): array {
                if (($data['button_label'] ?? null) === 'Tell us what you have in mind') {
                    $data['button_label'] = 'Start a Project';
                }

                return $data;
            });
            $this->updateSection('shared.header_nav', function (array $data): array {
                if (! in_array('/engagement', array_column($data['items'] ?? [], 'url'), true)) {
                    $data['items'][] = ['title' => 'How we work', 'url' => '/engagement', 'text' => ''];
                }

                return $data;
            });
        });
    }

    public function down(): void {}

    private function updateSection(string $key, Closure $update): void
    {
        $section = DB::table('website_sections')->where('key', $key)->lockForUpdate()->first();
        if ($section) {
            $data = json_decode($section->data, true, 512, JSON_THROW_ON_ERROR);
            $updated = $update($data);
            if ($updated !== $data) {
                DB::table('website_sections')->where('id', $section->id)->update([
                    'data' => json_encode($updated, JSON_THROW_ON_ERROR),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
