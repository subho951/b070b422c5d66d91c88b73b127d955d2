<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enquiries', function (Blueprint $table): void {
            $table->json('first_touch_attribution')->nullable();
            $table->json('last_touch_attribution')->nullable();
            $table->string('lead_source', 32)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table): void {
            $table->dropIndex(['lead_source']);
            $table->dropColumn(['first_touch_attribution', 'last_touch_attribution', 'lead_source']);
        });
    }
};
