<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// One order can now hold several ticket types. Existing single-type orders are moved into order_items.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price'); // whole rupiah, copied at reservation time
            $table->unsignedBigInteger('subtotal');
            $table->timestamps();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('ticket_type_id')->nullable()->after('event_id')->constrained();
        });

        DB::table('orders')->orderBy('id')->each(function (object $order) {
            DB::table('order_items')->insert([
                'order_id' => $order->id,
                'ticket_type_id' => $order->ticket_type_id,
                'quantity' => $order->quantity,
                'unit_price' => $order->unit_price,
                'subtotal' => $order->unit_price * $order->quantity,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
            ]);
            DB::table('tickets')->where('order_id', $order->id)->update(['ticket_type_id' => $order->ticket_type_id]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ticket_type_id');
            $table->dropColumn(['quantity', 'unit_price']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('ticket_type_id')->nullable()->constrained();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_price')->default(0);
        });

        DB::table('order_items')->orderBy('id')->each(function (object $item) {
            DB::table('orders')->where('id', $item->order_id)->update([
                'ticket_type_id' => $item->ticket_type_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
            ]);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ticket_type_id');
        });
        Schema::dropIfExists('order_items');
    }
};
