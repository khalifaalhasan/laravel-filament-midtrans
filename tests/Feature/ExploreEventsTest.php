<?php

namespace Tests\Feature;

use App\Enums\EventCategory;
use App\Enums\Region;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExploreEventsTest extends TestCase
{
    use RefreshDatabase;

    private function event(string $name, array $attrs = []): Event
    {
        return Event::factory()->published()->create(['name' => $name, 'category' => EventCategory::Musik, 'region' => Region::Jabodetabek, ...$attrs]);
    }

    public function test_lists_upcoming_published_events(): void
    {
        $this->event('Visible Jazz');
        Event::factory()->create(['name' => 'Hidden Draft']);

        $this->get(route('events.explore'))->assertOk()->assertSee('Visible Jazz')->assertDontSee('Hidden Draft')->assertSee('1 event ditemukan');
    }

    public function test_filters_by_search_category_and_region(): void
    {
        $this->event('Jazz Malam');
        $this->event('Jazz Pagi', ['region' => Region::Sumatera]);
        $this->event('Pameran Jazz', ['category' => EventCategory::Pameran]);

        $this->get(route('events.explore', ['q' => 'jazz', 'kategori' => 'musik', 'wilayah' => 'jabodetabek']))
            ->assertSee('Jazz Malam')->assertDontSee('Jazz Pagi')->assertDontSee('Pameran Jazz');
    }

    public function test_date_range_matches_overlapping_events(): void
    {
        $this->event('Desember Fest', ['starts_at' => '2026-12-10 10:00', 'ends_at' => '2026-12-12 22:00']);
        $this->event('Januari Fest', ['starts_at' => '2027-01-10 10:00', 'ends_at' => '2027-01-10 22:00']);

        $this->get(route('events.explore', ['dari' => '2026-12-11', 'sampai' => '2026-12-31']))
            ->assertSee('Desember Fest')->assertDontSee('Januari Fest');
    }

    public function test_search_treats_wildcards_literally(): void
    {
        $this->event('Diskon 100% Fest');
        $this->event('Biasa Fest');

        $this->get(route('events.explore', ['q' => '%']))->assertSee('Diskon 100% Fest')->assertDontSee('Biasa Fest');
    }

    public function test_empty_result_message_and_query_string_kept_in_pagination(): void
    {
        $this->get(route('events.explore', ['q' => 'tidak ada']))->assertSee('Event tidak ditemukan');

        foreach (range(1, 13) as $i) {
            $this->event("Rock {$i}");
        }
        // Checked on the paginator: earlier Livewire tests swap the global pagination view.
        $this->get(route('events.explore', ['q' => 'rock']))
            ->assertViewHas('events', fn ($events) => $events->total() === 13 && str_contains($events->nextPageUrl(), 'q=rock'));
    }

    public function test_home_links_now_point_to_explore(): void
    {
        $this->get('/')
            ->assertSee(route('events.explore', ['kategori' => 'musik']), false)
            ->assertSee(route('events.explore', ['wilayah' => 'sumatera']), false);
    }
}
