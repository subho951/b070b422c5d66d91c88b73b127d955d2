<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'contact_location'],
            ['value' => '18/2/7 Binod Bihari Aditya Lane, Baksata, Howrah 711110, West Bengal, India', 'updated_at' => now()],
        );
    }

    public function down(): void {}
};
