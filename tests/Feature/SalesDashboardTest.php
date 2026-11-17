<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Widgets\EventSales;
use App\Filament\Widgets\SalesOverview;
use App\Models\Event;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Event $eventA;

    private Event $eventB;

    protected function setUp(): void
    {
        parent::setUp();
        $service = app(OrderService::class);

        $this->eventA = Event::factory()->published()->create(['name' => 'Alpha Expo']);
        $this->eventB = Event::factory()->published()->create(['name' => 'Beta Fest']);

        // Event A: 2 paid tickets (1 checked in), 1 refunded order, 1 pending order.
        $a = TicketType::factory()->for($this->eventA)->create(['price' => 100000]);
        $paid = Order::factory()->for($a)->create(['quantity' => 2]);
        $service->transition($paid, OrderStatus::Paid);
        $paid->tickets()->first()->update(['checked_in_at' => now()]);
        $refunded = Order::factory()->for($a)->create();
        $service->transition($refunded, OrderStatus::Paid);
        $service->transition($refunded, OrderStatus::Refunded, [OrderStatus::Paid]);
        Order::factory()->for($a)->create();

        // Event B: 1 paid ticket.
        $b = TicketType::factory()->for($this->eventB)->create(['price' => 50000]);
        $service->transition(Order::factory()->for($b)->create(), OrderStatus::Paid);
    }

    public function test_totals_count_only_paid_orders(): void
    {
        $a = Event::withSalesTotals()->find($this->eventA->id);

        $this->assertSame(200000, (int) $a->revenue);
        $this->assertSame(2, $a->tickets_sold);
        $this->assertSame(1, $a->checked_in);
    }

    public function test_ceo_sees_aggregate_across_events(): void
    {
        $this->actingAs(User::factory()->withRole('ceo')->create());

        Livewire::test(SalesOverview::class)
            ->assertSee("Rp\u{a0}250.000")
            ->assertSee('3');
    }

    public function test_project_officer_sees_only_own_event(): void
    {
        $po = User::factory()->withRole('user')->create();
        $this->eventB->officers()->attach($po);
        $this->actingAs($po);

        Livewire::test(SalesOverview::class)->assertSee("Rp\u{a0}50.000")->assertDontSee("Rp\u{a0}250.000");
        Livewire::test(EventSales::class)
            ->assertCanSeeTableRecords([$this->eventB])
            ->assertCanNotSeeTableRecords([$this->eventA]);
    }
}
