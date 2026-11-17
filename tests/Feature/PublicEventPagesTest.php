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
            ->assertSee('Event Terdekat')
            ->assertSee('Lihat Lainnya')
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
            ->assertSee("Rp500.000")
            ->assertSee('Sisa 15 tiket');
    }

    public function test_draft_and_closed_event_detail_is_not_found(): void
    {
        $this->get(route('events.show', Event::factory()->create()))->assertNotFound();
        $this->get(route('events.show', Event::factory()->create(['status' => EventStatus::Closed])))->assertNotFound();
    }

    public function test_home_hero_shows_only_featured_events_with_banner(): void
    {
        Event::factory()->published()->create(['name' => 'Featured Gala', 'is_featured' => true, 'banner_path' => 'banners/gala.svg']);
        Event::factory()->published()->create(['name' => 'No Banner Show', 'is_featured' => true]);
        Event::factory()->create(['name' => 'Draft Featured', 'is_featured' => true, 'banner_path' => 'banners/draft.svg']);

        $this->get('/')
            ->assertOk()
            ->assertSee('aria-roledescription="carousel"', false)
            ->assertSee('gala.svg', false)
            ->assertDontSee('draft.svg', false);
    }

    public function test_date_range_label(): void
    {
        $oneDay = Event::factory()->make(['starts_at' => '2026-12-13 14:00', 'ends_at' => '2026-12-13 23:00']);
        $sameMonth = Event::factory()->make(['starts_at' => '2026-12-04 14:00', 'ends_at' => '2026-12-05 23:00']);
        $crossMonth = Event::factory()->make(['starts_at' => '2026-11-28 14:00', 'ends_at' => '2026-12-02 23:00']);

        $this->assertSame('13 Desember 2026', $oneDay->dateRangeLabel());
        $this->assertSame('04 - 05 Desember 2026', $sameMonth->dateRangeLabel());
        $this->assertSame('28 November - 02 Desember 2026', $crossMonth->dateRangeLabel());
    }

    public function test_starting_price_skips_sold_out_ticket_types(): void
    {
        $event = Event::factory()->published()->create();
        TicketType::factory()->for($event)->create(['price' => 50000, 'quota' => 10])->forceFill(['sold' => 10])->save();
        TicketType::factory()->for($event)->create(['price' => 90000, 'quota' => 10]);
        $soldOut = Event::factory()->published()->create();
        TicketType::factory()->for($soldOut)->create(['price' => 10000, 'quota' => 1])->forceFill(['sold' => 1])->save();

        $this->assertSame(90000, (int) Event::withStartingPrice()->find($event->id)->min_price);
        $this->assertNull(Event::withStartingPrice()->find($soldOut->id)->min_price);
    }

    public function test_recommended_section_lists_featured_first(): void
    {
        Event::factory()->published()->create(['name' => 'Soon Regular', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHours(3)]);
        Event::factory()->published()->create(['name' => 'Later Featured', 'is_featured' => true, 'starts_at' => now()->addMonth(), 'ends_at' => now()->addMonth()->addHours(3)]);

        $this->get('/')->assertSeeInOrder(['Rekomendasi Event', 'Later Featured', 'Soon Regular']);
    }

    public function test_best_sellers_ranked_by_paid_tickets(): void
    {
        $service = app(\App\Services\OrderService::class);
        $sell = function (string $name, int $qty) use ($service) {
            $event = Event::factory()->published()->create(['name' => $name, 'poster_path' => 'posters/x.svg']);
            $order = $service->reserve(\App\Models\User::factory()->create(), [TicketType::factory()->for($event)->create(['quota' => 50])->id => $qty]);
            $service->transition($order, \App\Enums\OrderStatus::Paid);
        };
        $sell('Small Hit', 1);
        $sell('Big Hit', 5);
        Event::factory()->published()->create(['name' => 'No Sales', 'poster_path' => 'posters/y.svg']);

        $this->get('/')
            ->assertSee('Laris Manis')
            ->assertViewHas('bestSellers', fn ($events) => $events->pluck('name')->all() === ['Big Hit', 'Small Hit']);
    }

    public function test_home_lists_all_categories(): void
    {
        $response = $this->get('/')->assertSee('Kategori Event');

        foreach (\App\Enums\EventCategory::cases() as $category) {
            $response->assertSee($category->getLabel());
        }
    }

    public function test_home_lists_all_regions(): void
    {
        $response = $this->get('/')->assertSee('Temukan Event Menarik di Kotamu!');

        foreach (\App\Enums\Region::cases() as $region) {
            $response->assertSee('Event di '.$region->getLabel());
        }
    }

    public function test_detail_page_matches_reference_sections(): void
    {
        $event = Event::factory()->published()->create([
            'category' => \App\Enums\EventCategory::Musik,
            'organizer_name' => 'Fafest Org',
            'instagram_url' => 'https://instagram.com/fafest',
            'description' => '<p><strong>Seru</strong></p><script>alert(1)</script>',
            'terms' => '<ol><li>Satu tiket satu orang</li></ol>',
            'facilities' => [\App\Enums\Facility::Toilet, \App\Enums\Facility::Medis],
        ]);
        TicketType::factory()->for($event)->create(['price' => 90000]);
        TicketType::factory()->for($event)->create(['price' => 185000]);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Musik')
            ->assertSee('Fafest Org')
            ->assertSee('https://instagram.com/fafest', false)
            ->assertSeeInOrder(['Deskripsi', 'Syarat &amp; Ketentuan', 'Fasilitas'], false)
            ->assertSee('<strong>Seru</strong>', false)
            ->assertDontSee('<script>alert(1)', false)
            ->assertSee('Satu tiket satu orang')
            ->assertSee('Toilet')
            ->assertSee('Harga mulai dari')
            ->assertSee('Rp90.000')
            ->assertSee('Detail Acara');
    }

    public function test_order_button_disabled_when_nothing_buyable(): void
    {
        $event = Event::factory()->published()->create();
        TicketType::factory()->for($event)->create(['quota' => 1])->forceFill(['sold' => 1])->save();

        $this->get(route('events.show', $event))
            ->assertSee('Tiket habis')
            ->assertSee('aria-disabled="true"', false);
    }
}
