<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolio_projects', function (Blueprint $table): void {
            $table->boolean('is_exclusive')->default(false);
        });
        DB::table('portfolio_projects')->whereIn('slug', ['alfa-network', 'e-school'])->update(['is_exclusive' => true]);
    }

    public function down(): void
    {
        Schema::table('portfolio_projects', function (Blueprint $table): void {
            $table->dropColumn('is_exclusive');
        });
    }
};
