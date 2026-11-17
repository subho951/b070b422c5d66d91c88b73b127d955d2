<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class MediaUsage
{
    public function isInUse(int $id): bool
    {
        foreach (['pages', 'services', 'portfolio_projects', 'testimonials'] as $table) {
            $query = DB::table($table)->where('featured_image_id', $id);
            if ($table !== 'testimonials') {
                $query->orWhere('og_image_id', $id);
            }
            if ($query->exists()) {
                return true;
            }
        }
        if (DB::table('site_settings')->where('media_asset_id', $id)->exists()) {
            return true;
        }
        foreach (['website_sections', 'office_records'] as $table) {
            foreach (DB::table($table)->select('data')->cursor() as $record) {
                if ($this->referencesImage(json_decode($record->data, true) ?? [], $id)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @param array<string|int, mixed> $data */
    private function referencesImage(array $data, int $id): bool
    {
        foreach ($data as $key => $value) {
            if (is_array($value) && $this->referencesImage($value, $id)) {
                return true;
            }
            if (is_string($key) && ($key === 'image_id' || str_ends_with($key, '_image_id')) && is_scalar($value) && (int) $value === $id) {
                return true;
            }
        }

        return false;
    }
}
