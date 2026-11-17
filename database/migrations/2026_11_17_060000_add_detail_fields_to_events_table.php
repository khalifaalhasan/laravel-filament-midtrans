<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->longText('terms')->nullable(); // rich text HTML, sanitized on render
            $table->json('facilities')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('tiktok_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['terms', 'facilities', 'instagram_url', 'tiktok_url']);
        });
    }
};
