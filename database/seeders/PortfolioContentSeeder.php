<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PortfolioContentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (config('portfolio-content') as $slug => $copy) {
                $record = DB::table('portfolio_projects')->whereIn('slug', [$slug, config('portfolio-catalog.legacy')[$slug][0]])->whereNull('deleted_at')->first();
                if (! $record) {
                    continue;
                }
                $updates = [];
                foreach ($copy + ['seo_description' => $copy['description']] as $field => $value) {
                    if (trim((string) ($record->$field ?? '')) === '') {
                        $updates[$field] = $value;
                    }
                }
                if ($updates !== []) {
                    DB::table('portfolio_projects')->where('id', $record->id)->update($updates + ['updated_at' => now()]);
                }
            }
        });
    }
}
