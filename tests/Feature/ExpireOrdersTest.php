<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_expires_overdue_pending_orders_and_releases_quota(): void
    {
        $ticket = TicketType::factory()->create(['quota' => 10]);
        $ticket->forceFill(['sold' => 4])->save();

        $overdue = Order::factory()->withItem($ticket, 2)->create(['expires_at' => now()->subMinutes(10)]);
        $inGrace = Order::factory()->withItem($ticket, 1)->create(['expires_at' => now()->subMinutes(2)]);
        $paid = Order::factory()->withItem($ticket, 1)->create(['expires_at' => now()->subMinutes(10), 'status' => OrderStatus::Paid]);

        $this->artisan('orders:expire')->expectsOutput('Expired 1 order(s).')->assertSuccessful();

        $this->assertSame(OrderStatus::Expired, $overdue->refresh()->status);
        $this->assertSame(OrderStatus::Pending, $inGrace->refresh()->status);
        $this->assertSame(OrderStatus::Paid, $paid->refresh()->status);
        $this->assertSame(2, $ticket->refresh()->sold);

        $this->artisan('orders:expire')->expectsOutput('Expired 0 order(s).');
        $this->assertSame(2, $ticket->refresh()->sold);
    }

    public function test_command_is_scheduled(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('orders:expire');
    }
}
