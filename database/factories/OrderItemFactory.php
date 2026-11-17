<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_type_id' => TicketType::factory(),
            'quantity' => 1,
            'unit_price' => fn (array $attrs) => TicketType::find($attrs['ticket_type_id'])->price,
            'subtotal' => fn (array $attrs) => $attrs['unit_price'] * $attrs['quantity'],
        ];
    }
}
