<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyTicketsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_only_own_orders(): void
    {
        $user = User::factory()->create();
        $mine = Order::factory()->for($user)->create();
        $theirs = Order::factory()->create();

        $this->actingAs($user)->get(route('my-tickets'))
            ->assertOk()
            ->assertSee($mine->event->name)
            ->assertDontSee($theirs->event->name);
    }

    public function test_paid_order_page_shows_qr_tickets_to_owner_only(): void
    {
        $order = Order::factory()->create(['quantity' => 2, 'snap_token' => 'tok']);
        app(OrderService::class)->transition($order, OrderStatus::Paid);
        $code = $order->tickets()->first()->code;

        $this->actingAs($order->user)->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee($code)
            ->assertSee('<svg', false);

        $this->actingAs(User::factory()->withRole('user')->create())
            ->get(route('orders.show', $order))
            ->assertForbidden();
    }

    public function test_guest_is_redirected(): void
    {
        $this->get(route('my-tickets'))->assertRedirect(route('login'));
    }
}
