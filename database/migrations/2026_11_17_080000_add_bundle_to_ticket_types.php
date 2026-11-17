<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_types', function (Blueprint $table) {
            $table->unsignedInteger('bundle_size')->default(1); // e-tickets (and seats) per purchased unit
            $table->text('description')->nullable();
        });

        // Snapshot so later edits to a ticket type never change what an existing order releases or issues.
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('bundle_size')->default(1)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('bundle_size'));
        Schema::table('ticket_types', fn (Blueprint $table) => $table->dropColumn(['bundle_size', 'description']));
    }
};
