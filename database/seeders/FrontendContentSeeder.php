<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FrontendContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('website.sections') as $key => $definition) {
            $data = array_map(fn (array $field): mixed => $field['default'], $definition['fields']);
            DB::table('website_sections')->insertOrIgnore(['key' => $key, 'scope' => $definition['scope'], 'data' => json_encode($data, JSON_THROW_ON_ERROR), 'sort_order' => array_search($key, array_keys(config('website.sections')), true), 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (config('website.branding') as $key => $value) {
            DB::table('site_settings')->insertOrIgnore(['key' => 'branding.'.$key, 'group' => 'branding', 'value' => $value, 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (['engagement' => ['Your ambition. The right partnership.', "Choose an engagement that fits your stage, team, and goals. We'll agree scope, communication, ownership, and commercial terms before work begins."], 'privacy' => [config('company-content.pages.privacy.title'), config('company-content.pages.privacy.description')], 'terms' => [config('company-content.pages.terms.title'), config('company-content.pages.terms.description')]] as $slug => [$title, $description]) {
            DB::table('pages')->insertOrIgnore(['slug' => $slug, 'title' => $title, 'description' => $description, 'status' => 'published', 'published_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
