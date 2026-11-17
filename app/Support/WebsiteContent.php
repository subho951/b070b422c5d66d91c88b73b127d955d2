<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WebsiteContent
{
    /** @return array<string, mixed> */
    public function section(string $key): array
    {
        $definition = config('website.sections')[$key] ?? null;
        if ($definition === null) {
            return ['_visible' => false];
        }
        $record = $this->records()->firstWhere('key', $key);
        $defaults = array_map(fn (array $field): mixed => $field['default'], $definition['fields']);

        return array_replace($defaults, $record ? json_decode($record->data, true, 512, JSON_THROW_ON_ERROR) : [], ['_visible' => $record ? (bool) $record->is_visible : true]);
    }

    /** @return Collection<int, object> */
    private function records(): Collection
    {
        return request()->attributes->get('website_sections_cache') ?? tap(DB::table('website_sections')->orderBy('sort_order')->orderBy('id')->get(), fn (Collection $records) => request()->attributes->set('website_sections_cache', $records));
    }

    /** @return Collection<int, object> */
    public function sections(string $scope): Collection
    {
        return $this->records()->where('scope', $scope)->where('is_visible', true)->values();
    }

    public function branding(string $key): string
    {
        $settings = request()->attributes->get('website_branding_cache');
        if ($settings === null) {
            $settings = DB::table('site_settings')->where('group', 'branding')->get()->keyBy('key');
            request()->attributes->set('website_branding_cache', $settings);
        }

        return (string) ($settings->get('branding.'.$key)?->value ?? config('website.branding.'.$key, ''));
    }

    public function image(?int $id): ?object
    {
        if (! $id) {
            return null;
        }
        $images = request()->attributes->get('website_media_cache');
        if ($images === null) {
            $images = DB::table('media_assets')->get()->keyBy('id');
            request()->attributes->set('website_media_cache', $images);
        }

        return $images->get($id);
    }

    public function logo(string $variant): string
    {
        $id = DB::table('site_settings')->where('key', 'branding.logo_'.$variant)->value('media_asset_id');
        if ($this->image($id ? (int) $id : null)) {
            return route('media.show', $id);
        }

        return asset(match ($variant) {
            'light' => 'brand/Howvanta-logo-primary-no-tagline.svg',
            'dark' => 'brand/Howvanta-logo-reversed-no-tagline.svg',
            default => 'favicon.svg',
        });
    }

    public function url(?string $value): string
    {
        if (is_string($value) && str_starts_with($value, '/') && ! str_starts_with($value, '//')) {
            return url($value);
        }

        return is_string($value) && preg_match('~^https?://~i', $value) ? $value : '#';
    }
}
