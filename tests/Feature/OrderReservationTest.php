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

        $order = app(OrderService::class)->reserve(User::factory()->create(), $ticket, 2);

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
        $service->reserve(User::factory()->create(), $ticket, 2);

        try {
            $service->reserve(User::factory()->create(), $ticket, 2);
            $this->fail('Expected oversell to be rejected');
        } catch (ValidationException) {
        }

        $service->reserve(User::factory()->create(), $ticket, 1);
        $this->assertSame(3, $ticket->refresh()->sold);
        $this->assertSame(2, $ticket->orders()->count());
    }

    public function test_cannot_reserve_ticket_not_on_sale(): void
    {
        $this->expectException(ValidationException::class);

        app(OrderService::class)->reserve(User::factory()->create(), $this->ticket(['sale_ends_at' => now()->subMinute()]), 1);
    }

    public function test_release_returns_quota_exactly_once(): void
    {
        $ticket = $this->ticket();
        $service = app(OrderService::class);
        $order = $service->reserve(User::factory()->create(), $ticket, 2);

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
        $order = $service->reserve(User::factory()->create(), $ticket, 1);

        $this->assertTrue($service->transition($order, OrderStatus::Paid, attributes: ['paid_at' => now()]));
        $this->assertSame(1, $ticket->refresh()->sold);
        $this->assertNotNull($order->paid_at);
    }
}
