<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\User;
use App\Payments\PaymentGateway;
use App\Payments\PaymentGatewayException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private TicketType $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ticket = TicketType::factory()->for(Event::factory()->published())->create(['price' => 150000, 'quota' => 10]);
    }

    public function test_checkout_reserves_and_shows_snap_payment(): void
    {
        $this->mock(PaymentGateway::class, function (MockInterface $m) {
            $m->shouldReceive('createCharge')->once()->andReturn('snap-token-123');
            $m->shouldReceive('getStatus')->andReturnNull();
        });
        config(['midtrans.client_key' => 'SB-Mid-client-abc']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('checkout.store', $this->ticket->event), [
            'ticket_type_id' => $this->ticket->id,
            'quantity' => 2,
        ]);

        $order = Order::sole();
        $response->assertRedirect(route('orders.show', $order));
        $this->assertSame('snap-token-123', $order->snap_token);
        $this->assertSame(300000, $order->total);

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('snap-token-123')
            ->assertSee('SB-Mid-client-abc')
            ->assertSee('app.sandbox.midtrans.com/snap/snap.js');
    }

    public function test_guest_must_log_in(): void
    {
        $this->post(route('checkout.store', $this->ticket->event), ['ticket_type_id' => $this->ticket->id, 'quantity' => 1])
            ->assertRedirect(route('login'));
    }

    public function test_ticket_from_another_event_is_rejected(): void
    {
        $other = Event::factory()->published()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('checkout.store', $other), ['ticket_type_id' => $this->ticket->id, 'quantity' => 1])
            ->assertNotFound();
    }

    public function test_quantity_is_capped(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('checkout.store', $this->ticket->event), ['ticket_type_id' => $this->ticket->id, 'quantity' => 6])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(0, Order::count());
    }

    public function test_gateway_failure_cancels_order_and_releases_quota(): void
    {
        $this->mock(PaymentGateway::class, fn (MockInterface $m) => $m->shouldReceive('createCharge')->andThrow(new PaymentGatewayException('down')));

        $this->actingAs(User::factory()->create())
            ->post(route('checkout.store', $this->ticket->event), ['ticket_type_id' => $this->ticket->id, 'quantity' => 1])
            ->assertSessionHasErrors('payment');

        $this->assertSame(OrderStatus::Cancelled, Order::sole()->status);
        $this->assertSame(0, $this->ticket->refresh()->sold);
    }

    public function test_other_user_cannot_view_order(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->create())->get(route('orders.show', $order))->assertForbidden();
    }

    public function test_server_key_never_reaches_the_page(): void
    {
        config(['midtrans.server_key' => 'SB-Mid-server-SECRET']);
        $order = Order::factory()->create(['snap_token' => 'tok']);

        $this->actingAs($order->user)->get(route('orders.show', $order))->assertDontSee('SB-Mid-server-SECRET');
    }
}
