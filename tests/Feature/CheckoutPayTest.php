<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Event;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\User;
use App\Payments\PaymentGateway;
use App\Payments\PaymentUpdate;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class CheckoutPayTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buyer = User::factory()->create();
        $ticket = TicketType::factory()->for(Event::factory()->published())->create(['price' => 185000]);
        $this->order = app(OrderService::class)->reserve($this->buyer, [$ticket->id => 1]);
        $this->order->update(['buyer_name' => 'Budi', 'payment_method' => PaymentMethod::Qris, 'snap_token' => 'snap-tok']);
    }

    private function gatewayReports(?OrderStatus $status): void
    {
        $this->mock(PaymentGateway::class, fn (MockInterface $m) => $m->shouldReceive('getStatus')
            ->andReturn($status ? new PaymentUpdate($this->order->code, $status, $this->order->total, 'tx', $status->value) : null));
    }

    public function test_pay_page_shows_summary_method_and_snap(): void
    {
        $this->gatewayReports(null);
        config(['midtrans.client_key' => 'SB-Mid-client-xyz']);

        $this->actingAs($this->buyer)->get(route('checkout.pay', $this->order))
            ->assertOk()
            ->assertSee('aria-current="step"', false)
            ->assertSee('Bayar Sekarang')
            ->assertSee('QRIS')
            ->assertSee('Rp185.000')
            ->assertSee('Batas Waktu Tersisa')
            ->assertSee('SB-Mid-client-xyz')
            ->assertSee('snap-tok');
    }

    public function test_status_endpoint_asks_the_gateway_and_returns_redirect_when_paid(): void
    {
        $this->gatewayReports(OrderStatus::Paid);

        $this->actingAs($this->buyer)->getJson(route('checkout.status', $this->order))
            ->assertOk()
            ->assertJson(['status' => 'paid', 'label' => 'Lunas', 'redirect' => route('checkout.success', $this->order)]);

        $this->assertSame(OrderStatus::Paid, $this->order->refresh()->status);
        $this->assertCount(1, $this->order->tickets);
    }

    public function test_status_stays_pending_without_gateway_confirmation(): void
    {
        $this->gatewayReports(null);

        $this->actingAs($this->buyer)->getJson(route('checkout.status', $this->order).'?transaction_status=settlement')
            ->assertJson(['status' => 'pending', 'redirect' => null]);
    }

    public function test_paid_order_goes_to_e_ticket_and_refresh_never_creates_a_new_charge(): void
    {
        $this->mock(PaymentGateway::class, function (MockInterface $m) {
            $m->shouldReceive('getStatus')->andReturn(new PaymentUpdate($this->order->code, OrderStatus::Paid, $this->order->total, 'tx', 'settlement'));
            $m->shouldNotReceive('createCharge');
        });

        $this->actingAs($this->buyer)->get(route('checkout.pay', $this->order))->assertRedirect(route('checkout.success', $this->order));
        $this->post(route('checkout.payment.store', $this->order), ['payment_method' => 'card'])->assertRedirect(route('checkout.success', $this->order));
    }

    public function test_without_charge_goes_back_to_step_three_and_others_are_forbidden(): void
    {
        $this->gatewayReports(null);
        $this->order->update(['snap_token' => null]);

        $this->actingAs($this->buyer)->get(route('checkout.pay', $this->order))->assertRedirect(route('checkout.payment', $this->order));

        $this->actingAs(User::factory()->create())->getJson(route('checkout.status', $this->order))->assertForbidden();
    }

    public function test_order_page_links_back_to_payment_while_awaiting(): void
    {
        $this->gatewayReports(null);

        $this->actingAs($this->buyer)->get(route('orders.show', $this->order))
            ->assertSee('Lanjutkan pembayaran')
            ->assertSee(route('checkout.pay', $this->order), false);
    }

    public function test_success_page_stays_with_summary_and_links(): void
    {
        $this->gatewayReports(null);
        app(OrderService::class)->transition($this->order, OrderStatus::Paid, attributes: ['paid_at' => now()]);

        $this->actingAs($this->buyer)->get(route('checkout.success', $this->order))
            ->assertOk()
            ->assertSee('Pembayaran Berhasil!')
            ->assertSee('1 e-ticket')
            ->assertSee('Rp185.000')
            ->assertSee(route('orders.show', $this->order), false)
            ->assertDontSee('http-equiv="refresh"', false);
    }

    public function test_success_page_only_for_paid_orders_of_the_buyer(): void
    {
        $this->gatewayReports(null);

        $this->actingAs($this->buyer)->get(route('checkout.success', $this->order))->assertRedirect(route('orders.show', $this->order));
        $this->actingAs(User::factory()->create())->get(route('checkout.success', $this->order))->assertForbidden();
    }
}
