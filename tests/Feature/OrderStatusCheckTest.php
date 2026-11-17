<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Payments\PaymentGateway;
use App\Payments\PaymentGatewayException;
use App\Payments\PaymentUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class OrderStatusCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_order_is_synced_from_gateway_status(): void
    {
        $order = Order::factory()->create(['snap_token' => 'tok']);
        $this->mock(PaymentGateway::class, fn (MockInterface $m) => $m->shouldReceive('getStatus')->once()
            ->andReturn(new PaymentUpdate($order->code, OrderStatus::Paid, $order->total, 'tx-9', 'settlement', 'qris')));

        $this->actingAs($order->user)->get(route('orders.show', $order))->assertOk()->assertSee('Lunas');

        $this->assertSame(OrderStatus::Paid, $order->refresh()->status);
    }

    public function test_browser_query_params_cannot_mark_paid(): void
    {
        $order = Order::factory()->create(['snap_token' => 'tok']);
        $this->mock(PaymentGateway::class, fn (MockInterface $m) => $m->shouldReceive('getStatus')->andReturnNull());

        $this->actingAs($order->user)
            ->get(route('orders.show', $order).'?transaction_status=settlement&status_code=200')
            ->assertOk();

        $this->assertSame(OrderStatus::Pending, $order->refresh()->status);
    }

    public function test_gateway_error_still_renders_page(): void
    {
        $order = Order::factory()->create(['snap_token' => 'tok']);
        $this->mock(PaymentGateway::class, fn (MockInterface $m) => $m->shouldReceive('getStatus')->andThrow(new PaymentGatewayException('timeout')));

        $this->actingAs($order->user)->get(route('orders.show', $order))->assertOk()->assertSee('Menunggu pembayaran');
    }

    public function test_finished_order_does_not_call_gateway(): void
    {
        $order = Order::factory()->create(['snap_token' => 'tok', 'status' => OrderStatus::Paid]);
        $this->mock(PaymentGateway::class, fn (MockInterface $m) => $m->shouldNotReceive('getStatus'));

        $this->actingAs($order->user)->get(route('orders.show', $order))->assertOk();
    }
}
