<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketIssuingTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_order_issues_one_ticket_per_quantity_exactly_once(): void
    {
        $order = Order::factory()->create(['quantity' => 3]);
        $service = app(OrderService::class);

        $service->transition($order, OrderStatus::Paid);
        $service->transition($order, OrderStatus::Paid);

        $tickets = $order->tickets;
        $this->assertCount(3, $tickets);
        $this->assertCount(3, $tickets->pluck('code')->unique());
        $this->assertTrue($tickets->every(fn ($t) => $t->event_id === $order->event_id));
        $this->assertStringContainsString('<svg', $tickets->first()->qrSvg());
    }

    public function test_unpaid_orders_get_no_tickets(): void
    {
        $order = Order::factory()->create(['quantity' => 2]);

        app(OrderService::class)->transition($order, OrderStatus::Expired);

        $this->assertCount(0, $order->tickets);
    }
}
