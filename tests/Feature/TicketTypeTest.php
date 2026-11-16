<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\RelationManagers\TicketTypesRelationManager;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TicketTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_on_sale_only_when_published_and_inside_window(): void
    {
        $event = Event::factory()->published()->create();

        $this->assertTrue(TicketType::factory()->for($event)->create()->isOnSale());
        $this->assertFalse(TicketType::factory()->for($event)->create(['sale_starts_at' => now()->addDay()])->isOnSale());
        $this->assertFalse(TicketType::factory()->for($event)->create(['sale_ends_at' => now()->subMinute()])->isOnSale());
        $this->assertFalse(TicketType::factory()->for(Event::factory())->create()->isOnSale());

        $event->update(['status' => EventStatus::Closed]);
        $this->assertFalse(TicketType::factory()->for($event)->create()->isOnSale());
    }

    public function test_project_officer_can_add_ticket_type_with_integer_price(): void
    {
        $event = Event::factory()->create();
        $po = User::factory()->withRole('user')->create();
        $event->officers()->attach($po);
        $this->actingAs($po);

        Livewire::test(TicketTypesRelationManager::class, ['ownerRecord' => $event, 'pageClass' => EditEvent::class])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'name' => 'Regular',
                'price' => 150000,
                'quota' => 50,
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(150000, $event->ticketTypes()->first()->price);
    }

    public function test_price_rejects_decimals(): void
    {
        $event = Event::factory()->create();
        $this->actingAs(User::factory()->withRole('ceo')->create());

        Livewire::test(TicketTypesRelationManager::class, ['ownerRecord' => $event, 'pageClass' => EditEvent::class])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'name' => 'Regular',
                'price' => '1500.50',
                'quota' => 50,
            ])
            ->assertHasFormErrors(['price']);
    }

    public function test_sold_ticket_type_cannot_be_deleted(): void
    {
        $ceo = User::factory()->withRole('ceo')->create();
        $type = TicketType::factory()->create();
        $type->forceFill(['sold' => 1])->save();

        $this->assertFalse($ceo->can('delete', $type));
    }
}
