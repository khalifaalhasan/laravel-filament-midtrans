<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// order_id sent to the gateway for the current payment attempt. Null = the order code (first attempt).
// Changing the payment method starts a new attempt with a new reference ("ORD-…-XXXX").
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('gateway_reference', 50)->nullable()->unique()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['gateway_reference']);
            $table->dropColumn('gateway_reference');
        });
    }
};
