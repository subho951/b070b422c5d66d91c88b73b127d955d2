<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (DB::table('website_sections')->select('id', 'data')->get() as $record) {
                $data = json_decode($record->data, true, 512, JSON_THROW_ON_ERROR);
                if (! is_string($data['eyebrow'] ?? null)) {
                    continue;
                }
                $label = preg_replace('/^\s*\d{1,2}\s*\/\s*/u', '', $data['eyebrow']);
                if ($label !== $data['eyebrow']) {
                    $data['eyebrow'] = $label;
                    DB::table('website_sections')->where('id', $record->id)->update(['data' => json_encode($data, JSON_THROW_ON_ERROR), 'updated_at' => now()]);
                }
            }
            foreach (DB::table('pages')->whereNotNull('eyebrow')->select('id', 'eyebrow')->get() as $page) {
                $label = preg_replace('/^\s*\d{1,2}\s*\/\s*/u', '', $page->eyebrow);
                if ($label !== $page->eyebrow) {
                    DB::table('pages')->where('id', $page->id)->update(['eyebrow' => $label, 'updated_at' => now()]);
                }
            }
        });
    }

    /**
     * Keep label copy changes when rolling back without restoring old numbering.
     */
    public function down(): void {}
};
