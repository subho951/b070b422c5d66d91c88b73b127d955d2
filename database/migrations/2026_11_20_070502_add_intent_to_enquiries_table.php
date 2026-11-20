<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enquiries', function (Blueprint $table): void {
            $table->string('intent', 20)->nullable();
        });

        $hero = DB::table('website_sections')->where('key', 'home.hero')->first();
        if ($hero) {
            $data = json_decode($hero->data, true, 512, JSON_THROW_ON_ERROR);
            foreach ($data['items'] ?? [] as $index => $item) {
                if (($item['title'] ?? null) === 'Request a Consultation' && ($item['url'] ?? null) === '/contact') {
                    $data['items'][$index]['url'] = '/contact?intent=consultation#enquiry';
                    DB::table('website_sections')->where('id', $hero->id)->where('data', $hero->data)->update([
                        'data' => json_encode($data, JSON_THROW_ON_ERROR),
                        'updated_at' => now(),
                    ]);
                    break;
                }
            }
        }

        DB::table('site_settings')->where('key', 'branding.service_button_label')->where('value', 'Discuss your requirements')->update([
            'value' => 'Request a Quote',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table): void {
            $table->dropColumn('intent');
        });
    }
};
