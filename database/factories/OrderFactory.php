<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'ORD-'.Str::upper(Str::ulid()),
            'user_id' => User::factory(),
            'ticket_type_id' => TicketType::factory(),
            'event_id' => fn (array $attrs) => TicketType::find($attrs['ticket_type_id'])->event_id,
            'quantity' => 1,
            'unit_price' => fn (array $attrs) => TicketType::find($attrs['ticket_type_id'])->price,
            'total' => fn (array $attrs) => $attrs['unit_price'] * $attrs['quantity'],
            'expires_at' => now()->addMinutes(Order::RESERVATION_MINUTES),
        ];
    }
}
