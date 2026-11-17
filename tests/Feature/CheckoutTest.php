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
            'quantities' => [$this->ticket->id => 2],
        ]);

        $order = Order::sole();
        $response->assertRedirect(route('checkout.details', $order));
        $this->assertSame(300000, $order->total);

        $this->post(route('checkout.details.store', $order), $this->detailsPayload(2))->assertRedirect(route('checkout.payment', $order));
        $this->post(route('checkout.payment.store', $order), ['payment_method' => 'qris'])->assertRedirect(route('orders.show', $order));
        $this->assertSame('snap-token-123', $order->refresh()->snap_token);
        $this->assertSame(\App\Enums\PaymentMethod::Qris, $order->payment_method);

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('snap-token-123')
            ->assertSee('SB-Mid-client-abc')
            ->assertSee('app.sandbox.midtrans.com/snap/snap.js');
    }

    private function detailsPayload(int $seats): array
    {
        $person = ['name' => 'Budi Santoso', 'id_type' => 'ktp', 'id_number' => '3201234567890001', 'email' => 'budi@contoh.com', 'phone' => '081234567890'];

        return ['buyer' => $person, 'attendees' => array_fill(0, $seats, $person + ['gender' => 'L'])];
    }

    public function test_guest_must_log_in(): void
    {
        $this->post(route('checkout.store', $this->ticket->event), ['quantities' => [$this->ticket->id => 1]])
            ->assertRedirect(route('login'));
    }

    public function test_ticket_from_another_event_is_rejected(): void
    {
        $other = Event::factory()->published()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('checkout.store', $other), ['quantities' => [$this->ticket->id => 1]])
            ->assertNotFound();
    }

    public function test_quantity_is_capped(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('checkout.store', $this->ticket->event), ['quantities' => [$this->ticket->id => 6]])
            ->assertSessionHasErrors('quantities.'.$this->ticket->id);

        $this->assertSame(0, Order::count());
    }

    public function test_gateway_failure_cancels_order_and_releases_quota(): void
    {
        $this->mock(PaymentGateway::class, fn (MockInterface $m) => $m->shouldReceive('createCharge')->andThrow(new PaymentGatewayException('down')));

        $this->actingAs(User::factory()->create())
            ->post(route('checkout.store', $this->ticket->event), ['quantities' => [$this->ticket->id => 1]]);
        $order = Order::sole();

        $this->post(route('checkout.details.store', $order), $this->detailsPayload(1));
        $this->from(route('checkout.payment', $order))
            ->post(route('checkout.payment.store', $order), ['payment_method' => 'card'])
            ->assertRedirect(route('checkout.payment', $order))
            ->assertSessionHasErrors('payment');

        // The buyer can retry while the reservation is still running.
        $this->assertSame(OrderStatus::Pending, $order->refresh()->status);
        $this->assertSame(1, $this->ticket->refresh()->sold);
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

    public function test_several_categories_in_one_order_and_total_limit(): void
    {
        $this->mock(PaymentGateway::class, function (MockInterface $m) {
            $m->shouldReceive('createCharge')->andReturn('tok');
            $m->shouldReceive('getStatus')->andReturnNull();
        });
        $vip = TicketType::factory()->for($this->ticket->event)->create(['price' => 400000, 'quota' => 10]);
        $this->actingAs(User::factory()->create());

        $this->post(route('checkout.store', $this->ticket->event), ['quantities' => [$this->ticket->id => 3, $vip->id => 3]])
            ->assertSessionHasErrors('quantities');
        $this->assertSame(0, Order::count());

        $this->post(route('checkout.store', $this->ticket->event), ['quantities' => [$this->ticket->id => 2, $vip->id => 1]]);
        $this->assertSame(700000, Order::sole()->total);
        $this->assertCount(2, Order::sole()->items);
    }

    public function test_step_one_lists_categories_with_status_and_bundle_badge(): void
    {
        $event = Event::factory()->published()->create();
        TicketType::factory()->for($event)->create(['name' => 'Normal - VIP', 'price' => 250000, 'quota' => 20]);
        TicketType::factory()->for($event)->create(['name' => 'Bundling 4 Tiket', 'price' => 640000, 'quota' => 20, 'bundle_size' => 4, 'description' => 'Paket hemat']);
        TicketType::factory()->for($event)->create(['name' => 'Early Bird', 'price' => 90000, 'quota' => 1])->forceFill(['sold' => 1])->save();

        $this->get(route('checkout.tickets', $event))
            ->assertOk()
            ->assertSee('Kategori Tiket')
            ->assertSeeInOrder(['Normal - VIP', 'Bundling 4 Tiket', 'Early Bird'])
            ->assertSee('Rp250.000')
            ->assertSee('Bundle')
            ->assertSee('Paket hemat')
            ->assertSee('Sold Out')
            ->assertSee('Rincian Pesanan')
            ->assertSee('Masuk untuk membeli')
            ->assertSee('aria-current="step"', false);
    }

    public function test_step_one_hidden_for_unpublished_event(): void
    {
        $this->get(route('checkout.tickets', Event::factory()->create()))->assertNotFound();
    }

    public function test_detail_page_order_button_goes_to_step_one(): void
    {
        $this->get(route('events.show', $this->ticket->event))->assertSee(route('checkout.tickets', $this->ticket->event), false);
    }
}
