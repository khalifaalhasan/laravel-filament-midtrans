<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Checkout step 2: buyer data on the order; one holder per e-ticket (personal data, shown masked to staff).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('buyer_name')->nullable();
            $table->string('buyer_id_type', 10)->nullable();
            $table->string('buyer_id_number', 20)->nullable();
            $table->string('buyer_email')->nullable();
            $table->string('buyer_phone', 20)->nullable();
            $table->json('attendees')->nullable(); // one entry per seat, copied to tickets when paid
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->string('holder_name')->nullable();
            $table->string('holder_id_type', 10)->nullable();
            $table->string('holder_id_number', 20)->nullable();
            $table->string('holder_email')->nullable();
            $table->string('holder_gender', 1)->nullable();
            $table->string('holder_phone', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', fn (Blueprint $table) => $table->dropColumn(['holder_name', 'holder_id_type', 'holder_id_number', 'holder_email', 'holder_gender', 'holder_phone']));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['buyer_name', 'buyer_id_type', 'buyer_id_number', 'buyer_email', 'buyer_phone', 'attendees']));
    }
};
