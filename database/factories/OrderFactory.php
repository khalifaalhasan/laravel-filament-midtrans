<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Orders get one default ticket line unless withItem() adds lines. `total` always matches the lines.
 *
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'ORD-'.Str::upper(Str::ulid()),
            'user_id' => User::factory(),
            'event_id' => Event::factory(),
            'total' => 0,
            'expires_at' => now()->addMinutes(Order::RESERVATION_MINUTES),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Order $order) {
            // has() children are created before this callback, so only orders without lines get the default one.
            if (! $order->items()->exists()) {
                $order->items()->save(OrderItem::factory()->make(['ticket_type_id' => TicketType::factory()->for($order->event)]));
            }
            $order->update(['total' => $order->items()->sum('subtotal')]);
        });
    }

    public function withItem(TicketType $ticketType, int $quantity = 1): static
    {
        return $this->state(['event_id' => $ticketType->event_id])
            ->has(OrderItem::factory()->state(['ticket_type_id' => $ticketType->id, 'quantity' => $quantity]), 'items');
    }
}
