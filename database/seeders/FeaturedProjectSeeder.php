<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class FeaturedProjectSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            if (DB::table('site_settings')->where('key', 'content.featured_projects_version')->exists()) {
                return;
            }
            foreach (config('featured-projects') as $slug => $definition) {
                $path = 'images/portfolio/'.$slug.'.png';
                $source = resource_path($path);
                $disk = Storage::disk('public');
                if (! $disk->put($path, file_get_contents($source))) {
                    throw new RuntimeException('Cannot save project screenshot: '.$slug);
                }
                $dimensions = getimagesize($source);
                $mediaId = DB::table('media_assets')->where('disk', 'public')->where('path', $path)->value('id');
                if (! $mediaId) {
                    $mediaId = DB::table('media_assets')->insertGetId([
                        'disk' => 'public', 'path' => $path, 'original_name' => $slug.'.png',
                        'title' => $definition['title'].' project screenshot', 'alt_text' => $definition['alt'],
                        'mime_type' => 'image/png', 'size_bytes' => $disk->size($path),
                        'width' => $dimensions[0], 'height' => $dimensions[1],
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                $data = array_intersect_key($definition, array_flip(['title', 'category', 'description', 'content', 'challenge', 'solution', 'outcome']));
                DB::table('portfolio_projects')->updateOrInsert(['slug' => $slug], $data + [
                    'featured_image_id' => $mediaId, 'is_demonstration' => false, 'is_exclusive' => true, 'show_on_homepage' => true,
                    'status' => 'published', 'published_at' => now(), 'deleted_at' => null,
                    'sort_order' => array_search($slug, array_keys(config('featured-projects')), true),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table('portfolio_projects')->whereIn('slug', ['operations-platform', 'intelligent-knowledge'])->where('is_demonstration', true)->update(['status' => 'draft', 'deleted_at' => now(), 'updated_at' => now()]);
            $section = DB::table('website_sections')->where('key', 'home.portfolio')->first();
            if ($section) {
                $data = json_decode($section->data, true, 512, JSON_THROW_ON_ERROR);
                foreach (['eyebrow', 'title', 'description'] as $field) {
                    $data[$field] = config('website.sections')['home.portfolio']['fields'][$field]['default'];
                }
                DB::table('website_sections')->where('id', $section->id)->update(['data' => json_encode($data, JSON_THROW_ON_ERROR), 'updated_at' => now()]);
            }
            DB::table('pages')->where('slug', 'portfolio')->update([
                'title' => 'Projects built around real needs.',
                'description' => config('portfolio-catalog.intro_description'),
                'eyebrow' => 'SELECTED PROJECTS', 'updated_at' => now(),
            ]);
            DB::table('site_settings')->insert(['key' => 'content.featured_projects_version', 'group' => 'content', 'value' => '2026-11-17.1', 'created_at' => now(), 'updated_at' => now()]);
        });
    }
}
