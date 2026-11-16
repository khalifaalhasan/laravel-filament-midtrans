<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicEventPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_published_upcoming_events(): void
    {
        Event::factory()->published()->create(['name' => 'Visible Event']);
        Event::factory()->create(['name' => 'Draft Event']);
        Event::factory()->create(['name' => 'Closed Event', 'status' => EventStatus::Closed]);
        Event::factory()->published()->create(['name' => 'Past Event', 'starts_at' => now()->subWeek(), 'ends_at' => now()->subWeek()->addHour()]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Visible Event')
            ->assertDontSee('Draft Event')
            ->assertDontSee('Closed Event')
            ->assertDontSee('Past Event');
    }

    public function test_detail_shows_tickets_with_remaining_quota(): void
    {
        $event = Event::factory()->published()->create();
        TicketType::factory()->for($event)->create(['name' => 'VIP', 'price' => 500000, 'quota' => 20])
            ->forceFill(['sold' => 5])->save();

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('VIP')
            ->assertSee("Rp\u{a0}500.000")
            ->assertSee('Sisa 15 tiket');
    }

    public function test_draft_and_closed_event_detail_is_not_found(): void
    {
        $this->get(route('events.show', Event::factory()->create()))->assertNotFound();
        $this->get(route('events.show', Event::factory()->create(['status' => EventStatus::Closed])))->assertNotFound();
    }
}
