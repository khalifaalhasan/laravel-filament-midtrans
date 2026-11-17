<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\PaymentNotification;
use App\Models\TicketType;
use App\Payments\MidtransGateway;
use App\Payments\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MidtransWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'SB-Mid-server-test';

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(PaymentGateway::class, new MidtransGateway(self::KEY, false));

        $ticket = TicketType::factory()->create(['price' => 150000, 'quota' => 10]);
        $ticket->forceFill(['sold' => 2])->save();
        $this->order = Order::factory()->withItem($ticket, 2)->create();
    }

    private function notify(string $status, array $overrides = [])
    {
        $p = [
            'order_id' => $this->order->code,
            'status_code' => '200',
            'gross_amount' => $this->order->total.'.00',
            'transaction_status' => $status,
            'transaction_id' => 'tx-1',
            'payment_type' => 'qris',
            ...$overrides,
        ];
        $p['signature_key'] ??= hash('sha512', $p['order_id'].$p['status_code'].$p['gross_amount'].self::KEY);

        return $this->postJson(route('webhooks.midtrans'), $p);
    }

    public function test_settlement_marks_order_paid(): void
    {
        $this->notify('settlement')->assertOk();

        $this->order->refresh();
        $this->assertSame(OrderStatus::Paid, $this->order->status);
        $this->assertNotNull($this->order->paid_at);
        $this->assertSame('qris', $this->order->payment_type);
        $this->assertSame('tx-1', $this->order->gateway_transaction_id);
    }

    public function test_wrong_signature_is_rejected(): void
    {
        $this->notify('settlement', ['signature_key' => str_repeat('0', 128)])->assertForbidden();

        $this->assertSame(OrderStatus::Pending, $this->order->refresh()->status);
        $this->assertSame(0, PaymentNotification::count());
    }

    public function test_duplicate_notification_is_processed_once(): void
    {
        $this->notify('expire')->assertOk();
        $this->notify('expire')->assertOk()->assertJson(['message' => 'Duplicate ignored']);

        $this->assertSame(OrderStatus::Expired, $this->order->refresh()->status);
        $this->assertSame(0, $this->order->items->first()->ticketType->refresh()->sold);
        $this->assertSame(1, PaymentNotification::count());
    }

    public function test_pending_then_settlement_both_recorded(): void
    {
        $this->notify('pending')->assertOk();
        $this->notify('settlement')->assertOk();

        $this->assertSame(OrderStatus::Paid, $this->order->refresh()->status);
        $this->assertSame(2, PaymentNotification::count());
    }

    public function test_amount_mismatch_does_not_mark_paid(): void
    {
        $this->notify('settlement', ['gross_amount' => '1000.00'])->assertOk();

        $this->assertSame(OrderStatus::Pending, $this->order->refresh()->status);
    }

    public function test_late_settlement_does_not_revive_expired_order(): void
    {
        $this->notify('expire', ['transaction_id' => 'tx-0']);
        $this->notify('settlement')->assertOk();

        $this->assertSame(OrderStatus::Expired, $this->order->refresh()->status);
        $this->assertSame(0, $this->order->items->first()->ticketType->refresh()->sold);
    }
}
