<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketType>
 */
class TicketTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->randomElement(['Regular', 'VIP', 'Early Bird']),
            'price' => fake()->randomElement([50_000, 100_000, 250_000]),
            'quota' => 100,
        ];
    }
}
