<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Event;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\User;
use App\Payments\MidtransGateway;
use App\Payments\PaymentGateway;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class CheckoutPaymentMethodTest extends TestCase
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
    }

    private function withBuyerData(): void
    {
        $this->order->update(['buyer_name' => 'Budi', 'buyer_email' => 'b@contoh.com', 'attendees' => [['name' => 'Budi', 'gender' => 'L']]]);
    }

    public function test_step_three_requires_step_two_first(): void
    {
        $this->actingAs($this->buyer)->get(route('checkout.payment', $this->order))->assertRedirect(route('checkout.details', $this->order));
    }

    public function test_lists_method_groups_and_summary(): void
    {
        $this->withBuyerData();

        $response = $this->actingAs($this->buyer)->get(route('checkout.payment', $this->order))->assertOk()
            ->assertSee('aria-current="step"', false)
            ->assertSee('Batas Waktu Tersisa')
            ->assertSee('Rp185.000');
        foreach (PaymentMethod::cases() as $method) {
            $response->assertSee($method->label());
        }
    }

    public function test_method_is_required_and_charge_is_created_once(): void
    {
        $this->withBuyerData();
        $this->mock(PaymentGateway::class, fn (MockInterface $m) => $m->shouldReceive('createCharge')->once()->andReturn('tok-1'));
        $this->actingAs($this->buyer);

        $this->post(route('checkout.payment.store', $this->order), [])->assertSessionHasErrors('payment_method');
        $this->post(route('checkout.payment.store', $this->order), ['payment_method' => 'e-wallet'])->assertRedirect(route('checkout.pay', $this->order));
        // A second submit (refresh / back) must not create another gateway transaction.
        $this->post(route('checkout.payment.store', $this->order), ['payment_method' => 'card'])->assertRedirect(route('checkout.pay', $this->order));

        $this->assertSame(PaymentMethod::EWallet, $this->order->refresh()->payment_method);
        $this->assertSame('tok-1', $this->order->snap_token);
    }

    public function test_midtrans_receives_enabled_payments_for_the_chosen_group(): void
    {
        $this->order->update(['payment_method' => PaymentMethod::Qris]);

        $params = (new MidtransGateway('SB-Mid-server-test', false))->snapParams($this->order->refresh());

        $this->assertSame(['other_qris'], $params['enabled_payments']);
        $this->assertSame(['gopay', 'shopeepay'], MidtransGateway::enabledPayments(PaymentMethod::EWallet));
    }
}
