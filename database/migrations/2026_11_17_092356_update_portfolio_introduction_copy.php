<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $section = DB::table('website_sections')->where('key', 'home.portfolio')->first();
        if (! $section) {
            return;
        }
        $data = json_decode($section->data, true, 512, JSON_THROW_ON_ERROR);
        $data['description'] = 'Explore practical digital products built to connect people, simplify operations and support business growth.';
        DB::table('website_sections')->where('id', $section->id)->update([
            'data' => json_encode($data, JSON_THROW_ON_ERROR), 'updated_at' => now(),
        ]);
    }

    public function down(): void {}
};
