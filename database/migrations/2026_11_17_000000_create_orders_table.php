<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // sent to the gateway as order_id
            $table->foreignId('user_id')->constrained();
            $table->foreignId('event_id')->constrained();
            $table->foreignId('ticket_type_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price'); // whole rupiah, copied at reservation time
            $table->unsignedBigInteger('total');
            $table->string('status')->default('pending')->index();
            $table->string('snap_token')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->dateTime('expires_at')->index();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
