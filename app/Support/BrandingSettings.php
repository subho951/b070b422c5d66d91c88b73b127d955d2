<?php

namespace App\Support;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BrandingSettings
{
    /** @return array<string, mixed> */
    public function validated(Request $request): array
    {
        $rules = [];
        foreach (config('website.branding') as $key => $default) {
            $rules[$key] = str_ends_with($key, '_url') ? $this->linkRules() : ['required', 'string', 'max:1000'];
        }
        foreach (['light', 'dark', 'favicon'] as $variant) {
            $rules['logo_'.$variant] = ['nullable', 'integer', Rule::exists('media_assets', 'id')];
        }

        return $request->validate($rules);
    }

    /** @param array<string, mixed> $data */
    public function save(array $data): void
    {
        DB::transaction(function () use ($data): void {
            foreach ($data as $key => $value) {
                $values = ['group' => 'branding', 'updated_at' => now()];
                if (str_starts_with($key, 'logo_')) {
                    $values['media_asset_id'] = $value;
                } else {
                    $values['value'] = $value;
                }
                DB::table('site_settings')->updateOrInsert(['key' => 'branding.'.$key], $values);
            }
        });
    }

    /** @return array<int, mixed> */
    public function linkRules(): array
    {
        return ['nullable', 'string', 'max:2048', function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || str_contains($value, '\\') || preg_match('/[\x00-\x20]/', $value)
                || (! preg_match('~^/(?!/)~', $value) && ! filter_var($value, FILTER_VALIDATE_URL))) {
                $fail('Use a local path such as /contact or a full HTTPS website address.');
            } elseif (! str_starts_with($value, '/') && ! preg_match('~^https?://~i', $value)) {
                $fail('Only HTTP and HTTPS links are allowed.');
            }
        }];
    }
}
