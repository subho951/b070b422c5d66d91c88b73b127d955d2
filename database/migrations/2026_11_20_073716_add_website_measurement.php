<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_contact_clicks', function (Blueprint $table): void {
            $table->date('day');
            $table->string('action', 20);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->primary(['day', 'action']);
        });

        Schema::table('enquiries', function (Blueprint $table): void {
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('consultation_booked_at')->nullable();
            $table->timestamp('proposal_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table): void {
            $table->dropColumn(['qualified_at', 'consultation_booked_at', 'proposal_sent_at']);
        });
        Schema::dropIfExists('website_contact_clicks');
    }
};
