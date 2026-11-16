<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $startsAt = fake()->dateTimeBetween('+1 week', '+3 months');

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'location' => fake()->city(),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+4 hours'),
            'status' => EventStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => EventStatus::Published]);
    }
}
