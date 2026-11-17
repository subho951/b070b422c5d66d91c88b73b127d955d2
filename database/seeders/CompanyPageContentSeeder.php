<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanyPageContentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $version = DB::table('site_settings')->where('key', 'content.company_pages_version')->exists();
            foreach (config('company-content.pages') as $slug => $copy) {
                $page = DB::table('pages')->where('slug', $slug)->first();
                if (! $page) {
                    DB::table('pages')->insert($copy + ['slug' => $slug, 'status' => 'published', 'published_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
                } elseif (! $version) {
                    DB::table('pages')->where('id', $page->id)->update($copy + ['updated_at' => now()]);
                }
            }
            foreach (array_keys(config('company-content.sections')) as $key) {
                $definition = config('website.sections')[$key];
                $defaults = array_map(fn (array $field): mixed => $field['default'], $definition['fields']);
                $record = DB::table('website_sections')->where('key', $key)->first();
                if (! $record) {
                    DB::table('website_sections')->insert(['key' => $key, 'scope' => $definition['scope'], 'data' => json_encode($defaults, JSON_THROW_ON_ERROR), 'is_visible' => true, 'sort_order' => $key === 'about.story' ? max(0, (int) (DB::table('website_sections')->where('scope', 'about')->min('sort_order') ?? 1) - 1) : array_search($key, array_keys(config('website.sections')), true), 'created_at' => now(), 'updated_at' => now()]);
                } elseif (! $version) {
                    $data = json_decode($record->data, true, 512, JSON_THROW_ON_ERROR);
                    $data['items'] = $defaults['items'];
                    DB::table('website_sections')->where('id', $record->id)->update(['data' => json_encode(array_replace($defaults, $data), JSON_THROW_ON_ERROR), 'updated_at' => now()]);
                }
            }
            DB::table('site_settings')->insertOrIgnore(['key' => 'content.company_pages_version', 'group' => 'content', 'value' => '2026-11-17.1', 'created_at' => now(), 'updated_at' => now()]);
        });
    }
}
