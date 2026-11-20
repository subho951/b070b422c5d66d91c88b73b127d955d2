<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'title' => "Engineering What's Next.",
            'description' => 'We build custom software, AI-powered applications and cloud-ready platforms for growing businesses.',
        ] as $field => $previousValue) {
            DB::table('pages')->where('slug', 'home')->whereNull('deleted_at')->where($field, $previousValue)->update([
                $field => config('website.home_page.'.$field),
                'updated_at' => now(),
            ]);
        }

        $this->refreshDefaults('home.hero', [
            'items' => [
                ['title' => "Let's build what's next", 'text' => '', 'url' => '/contact'],
                ['title' => 'Explore services', 'text' => '', 'url' => '/services'],
            ],
        ]);
        $this->refreshDefaults('home.expertise', [
            'title' => "Real challenges.\nPractical solutions.",
            'description' => "One partner across your product's lifecycle. The right technology, chosen for the problem you need to solve.",
        ]);
        $this->refreshDefaults('home.why', [
            'title' => "Clear thinking.\nConsidered craft.\nLasting partnership.",
            'description' => 'Good software starts with understanding your business. We combine the clarity of a consultant with the craft of an engineering team.',
        ]);

        DB::transaction(function (): void {
            $sections = DB::table('website_sections')->whereIn('key', ['home.why', 'home.portfolio'])->lockForUpdate()->pluck('sort_order', 'key');
            if ((int) ($sections['home.why'] ?? -1) === 3 && (int) ($sections['home.portfolio'] ?? -1) === 4) {
                DB::table('website_sections')->where('key', 'home.portfolio')->update(['sort_order' => 3, 'updated_at' => now()]);
                DB::table('website_sections')->where('key', 'home.why')->update(['sort_order' => 4, 'updated_at' => now()]);
            }
        });
    }

    public function down(): void {}

    /** @param array<string, mixed> $previousDefaults */
    private function refreshDefaults(string $key, array $previousDefaults): void
    {
        $record = DB::table('website_sections')->where('key', $key)->first();
        if (! $record) {
            return;
        }

        $original = json_decode($record->data, true, 512, JSON_THROW_ON_ERROR);
        $data = $original;
        $fields = config('website.sections')[$key]['fields'];
        foreach ($previousDefaults as $field => $previousValue) {
            $value = $data[$field] ?? null;
            if (is_string($value)) {
                $value = str_replace("\r\n", "\n", $value);
            }

            if ($value === $previousValue) {
                $data[$field] = $fields[$field]['default'];
            }
        }

        if ($data !== $original) {
            DB::table('website_sections')->where('id', $record->id)->where('data', $record->data)->update([
                'data' => json_encode($data, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
        }
    }
};
