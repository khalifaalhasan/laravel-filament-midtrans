<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderReservationTest extends TestCase
{
    use RefreshDatabase;

    private function ticket(array $attrs = []): TicketType
    {
        return TicketType::factory()->for(Event::factory()->published())->create(['price' => 150000, 'quota' => 3, ...$attrs]);
    }

    public function test_reserve_creates_pending_order_and_holds_quota(): void
    {
        $ticket = $this->ticket();

        $order = app(OrderService::class)->reserve(User::factory()->create(), [$ticket->id => 2]);

        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(300000, $order->total);
        $this->assertSame($ticket->event_id, $order->event_id);
        $this->assertTrue($order->expires_at->isFuture());
        $this->assertSame(2, $ticket->refresh()->sold);
    }

    public function test_cannot_oversell(): void
    {
        $ticket = $this->ticket();
        $service = app(OrderService::class);
        $service->reserve(User::factory()->create(), [$ticket->id => 2]);

        try {
            $service->reserve(User::factory()->create(), [$ticket->id => 2]);
            $this->fail('Expected oversell to be rejected');
        } catch (ValidationException) {
        }

        $service->reserve(User::factory()->create(), [$ticket->id => 1]);
        $this->assertSame(3, $ticket->refresh()->sold);
        $this->assertSame(2, \App\Models\OrderItem::where('ticket_type_id', $ticket->id)->count());
    }

    public function test_cannot_reserve_ticket_not_on_sale(): void
    {
        $this->expectException(ValidationException::class);

        app(OrderService::class)->reserve(User::factory()->create(), [$this->ticket(['sale_ends_at' => now()->subMinute()])->id => 1]);
    }

    public function test_release_returns_quota_exactly_once(): void
    {
        $ticket = $this->ticket();
        $service = app(OrderService::class);
        $order = $service->reserve(User::factory()->create(), [$ticket->id => 2]);

        $this->assertTrue($service->transition($order, OrderStatus::Expired));
        $this->assertFalse($service->transition($order, OrderStatus::Expired));
        $this->assertFalse($service->transition($order, OrderStatus::Paid));

        $this->assertSame(OrderStatus::Expired, $order->status);
        $this->assertSame(0, $ticket->refresh()->sold);
    }

    public function test_paid_order_keeps_quota(): void
    {
        $ticket = $this->ticket();
        $service = app(OrderService::class);
        $order = $service->reserve(User::factory()->create(), [$ticket->id => 1]);

        $this->assertTrue($service->transition($order, OrderStatus::Paid, attributes: ['paid_at' => now()]));
        $this->assertSame(1, $ticket->refresh()->sold);
        $this->assertNotNull($order->paid_at);
    }

    public function test_reserves_several_ticket_types_in_one_order(): void
    {
        $event = Event::factory()->published()->create();
        $regular = TicketType::factory()->for($event)->create(['price' => 100000, 'quota' => 10]);
        $vip = TicketType::factory()->for($event)->create(['price' => 250000, 'quota' => 10]);

        $order = app(OrderService::class)->reserve(User::factory()->create(), [$regular->id => 2, $vip->id => 1]);

        $this->assertSame(450000, $order->total);
        $this->assertCount(2, $order->items);
        $this->assertSame([2, 1], [$regular->refresh()->sold, $vip->refresh()->sold]);
    }

    public function test_one_sold_out_line_releases_everything(): void
    {
        $event = Event::factory()->published()->create();
        $open = TicketType::factory()->for($event)->create(['quota' => 10]);
        $full = TicketType::factory()->for($event)->create(['quota' => 1]);
        $full->forceFill(['sold' => 1])->save();

        try {
            app(OrderService::class)->reserve(User::factory()->create(), [$open->id => 2, $full->id => 1]);
            $this->fail('Expected sold-out line to fail the whole order');
        } catch (ValidationException) {
        }

        $this->assertSame(0, $open->refresh()->sold);
        $this->assertSame(0, \App\Models\Order::count());
    }

    public function test_rejects_mixed_events_and_too_many_tickets(): void
    {
        $a = TicketType::factory()->for(Event::factory()->published())->create();
        $b = TicketType::factory()->for(Event::factory()->published())->create();
        $service = app(OrderService::class);

        foreach ([[$a->id => 1, $b->id => 1], [$a->id => \App\Models\Order::MAX_TICKETS + 1], []] as $lines) {
            try {
                $service->reserve(User::factory()->create(), $lines);
                $this->fail('Expected rejection for '.json_encode($lines));
            } catch (ValidationException) {
            }
        }

        $this->assertSame([0, 0], [$a->refresh()->sold, $b->refresh()->sold]);
    }

    public function test_expiring_multi_line_order_releases_each_line_and_paid_issues_typed_tickets(): void
    {
        $event = Event::factory()->published()->create();
        $regular = TicketType::factory()->for($event)->create(['quota' => 10]);
        $vip = TicketType::factory()->for($event)->create(['quota' => 10]);
        $service = app(OrderService::class);

        $expired = $service->reserve(User::factory()->create(), [$regular->id => 2, $vip->id => 1]);
        $service->transition($expired, OrderStatus::Expired);
        $this->assertSame([0, 0], [$regular->refresh()->sold, $vip->refresh()->sold]);

        $paid = $service->reserve(User::factory()->create(), [$regular->id => 2, $vip->id => 1]);
        $service->transition($paid, OrderStatus::Paid);
        $this->assertSame([2, 1], [$paid->tickets()->where('ticket_type_id', $regular->id)->count(), $paid->tickets()->where('ticket_type_id', $vip->id)->count()]);
    }

    public function test_bundle_uses_seats_and_issues_one_ticket_per_seat(): void
    {
        $event = Event::factory()->published()->create();
        $bundle = TicketType::factory()->for($event)->create(['price' => 600000, 'quota' => 10, 'bundle_size' => 4]);
        $service = app(OrderService::class);

        $order = $service->reserve(User::factory()->create(), [$bundle->id => 2]);

        $this->assertSame(1200000, $order->total);
        $this->assertSame(8, $bundle->refresh()->sold);
        $this->assertSame(0, $bundle->availableUnits()); // 2 seats left < 4

        // A later edit to the type must not change what this order releases or issues.
        $bundle->update(['bundle_size' => 2]);
        $service->transition($order, OrderStatus::Paid);
        $this->assertCount(8, $order->tickets);
    }

    public function test_bundle_that_does_not_fit_is_rejected(): void
    {
        $bundle = TicketType::factory()->for(Event::factory()->published())->create(['quota' => 10, 'bundle_size' => 6]);
        $service = app(OrderService::class);
        $service->reserve(User::factory()->create(), [$bundle->id => 1]);

        $this->expectException(ValidationException::class);
        $service->reserve(User::factory()->create(), [$bundle->id => 1]);
    }

    public function test_expiring_bundle_releases_all_seats(): void
    {
        $bundle = TicketType::factory()->for(Event::factory()->published())->create(['quota' => 10, 'bundle_size' => 3]);
        $service = app(OrderService::class);

        $service->transition($service->reserve(User::factory()->create(), [$bundle->id => 3]), OrderStatus::Expired);

        $this->assertSame(0, $bundle->refresh()->sold);
    }
}
