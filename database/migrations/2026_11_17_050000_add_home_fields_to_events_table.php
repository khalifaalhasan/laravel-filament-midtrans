<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('category')->default('musik')->index();
            $table->string('region')->default('jabodetabek')->index();
            $table->string('organizer_name')->nullable();
            $table->string('organizer_logo_path')->nullable();
            $table->string('banner_path')->nullable(); // wide image for the home hero carousel
            $table->boolean('is_featured')->default(false)->index();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropIndex(['region']);
            $table->dropIndex(['is_featured']);
            $table->dropColumn(['category', 'region', 'organizer_name', 'organizer_logo_path', 'banner_path', 'is_featured']);
        });
    }
};
