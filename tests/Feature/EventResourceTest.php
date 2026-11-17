<?php

namespace Tests\Feature;

use App\Enums\EventCategory;
use App\Enums\EventStatus;
use App\Enums\Region;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\Pages\ListEvents;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_operation_can_create_and_publish_event(): void
    {
        $this->actingAs(User::factory()->withRole('operation')->create());

        Livewire::test(CreateEvent::class)
            ->fillForm([
                'name' => 'Demo Fest',
                'slug' => 'demo-fest',
                'location' => 'Bandung',
                'status' => EventStatus::Published,
                'category' => EventCategory::Musik,
                'region' => Region::JawaBarat,
                'starts_at' => now()->addMonth(),
                'ends_at' => now()->addMonth()->addHours(5),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(EventStatus::Published, Event::firstWhere('slug', 'demo-fest')->status);
        $this->assertSame(Region::JawaBarat, Event::firstWhere('slug', 'demo-fest')->region);
    }

    public function test_ends_at_must_be_after_starts_at(): void
    {
        $this->actingAs(User::factory()->withRole('ceo')->create());

        Livewire::test(CreateEvent::class)
            ->fillForm([
                'name' => 'Bad',
                'slug' => 'bad',
                'location' => 'X',
                'starts_at' => now()->addMonth(),
                'ends_at' => now()->addWeek(),
            ])
            ->call('create')
            ->assertHasFormErrors(['ends_at']);
    }

    public function test_project_officer_only_lists_and_edits_own_event(): void
    {
        [$mine, $other] = Event::factory()->count(2)->create();
        $po = User::factory()->withRole('user')->create();
        $mine->officers()->attach($po);
        $this->actingAs($po);

        Livewire::test(ListEvents::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other]);

        $this->get(EditEvent::getUrl(['record' => $mine]))->assertOk();
        $this->get(EditEvent::getUrl(['record' => $other]))->assertNotFound();
        $this->get(CreateEvent::getUrl())->assertForbidden();
    }
}
