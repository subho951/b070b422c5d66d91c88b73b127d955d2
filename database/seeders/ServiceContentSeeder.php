<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ServiceContentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (config('service-content') as $slug => $definition) {
                $service = DB::table('services')->where('slug', $slug)->whereNull('deleted_at')->first();
                if (! $service) {
                    continue;
                }
                $updates = [];
                if (trim($service->content ?? '') === '' || trim($service->content) === trim($service->description)) {
                    $updates['content'] = implode("\n\n", $definition['paragraphs']);
                }
                if (! $service->featured_image_id) {
                    $updates['featured_image_id'] = $this->cover($slug, $service->title, $definition['alt']);
                }
                if ($updates) {
                    DB::table('services')->where('id', $service->id)->update($updates + ['updated_at' => now()]);
                }
            }
        });
    }

    private function cover(string $slug, string $title, string $alt): int
    {
        $source = resource_path('images/services/'.$slug.'.webp');
        if (! is_file($source)) {
            throw new RuntimeException('Missing service cover: '.$slug);
        }
        $path = 'images/services/'.$slug.'.webp';
        $disk = Storage::disk('public');
        if (! $disk->exists($path) && ! $disk->put($path, file_get_contents($source))) {
            throw new RuntimeException('Cannot save service cover: '.$slug);
        }
        $existing = DB::table('media_assets')->where('disk', 'public')->where('path', $path)->value('id');
        if ($existing) {
            return (int) $existing;
        }
        $dimensions = getimagesize($source);

        return DB::table('media_assets')->insertGetId([
            'disk' => 'public', 'path' => $path, 'original_name' => $slug.'.webp',
            'title' => $title.' service cover', 'alt_text' => $alt,
            'mime_type' => 'image/webp', 'size_bytes' => $disk->size($path),
            'width' => $dimensions[0], 'height' => $dimensions[1],
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
