<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_event_defaults_to_draft(): void
    {
        $event = Event::create([
            'name' => 'Demo',
            'slug' => 'demo',
            'location' => 'Jakarta',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(4),
        ]);

        $this->assertSame(EventStatus::Draft, $event->status);
    }

    public function test_published_scope_only_returns_published_events(): void
    {
        Event::factory()->create();
        $published = Event::factory()->published()->create();
        Event::factory()->create(['status' => EventStatus::Closed]);

        $this->assertEquals([$published->id], Event::published()->pluck('id')->all());
    }
}
