<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Payments\MidtransGateway;
use App\Payments\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MidtransGatewayTest extends TestCase
{
    use RefreshDatabase;

    private MidtransGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new MidtransGateway('SB-Mid-server-test', false);
    }

    private function payload(array $overrides = []): array
    {
        $p = [
            'order_id' => 'ORD-1',
            'status_code' => '200',
            'gross_amount' => '150000.00',
            'transaction_status' => 'settlement',
            'transaction_id' => 'tx-1',
            'payment_type' => 'bank_transfer',
            ...$overrides,
        ];
        $p['signature_key'] ??= hash('sha512', $p['order_id'].$p['status_code'].$p['gross_amount'].'SB-Mid-server-test');

        return $p;
    }

    public function test_container_resolves_midtrans_adapter(): void
    {
        $this->assertInstanceOf(MidtransGateway::class, app(PaymentGateway::class));
    }

    public function test_valid_signature_is_parsed(): void
    {
        $update = $this->gateway->verifyWebhook($this->payload());

        $this->assertSame('ORD-1', $update->orderCode);
        $this->assertSame(OrderStatus::Paid, $update->status);
        $this->assertSame(150000, $update->grossAmount);
        $this->assertSame('tx-1', $update->transactionId);
    }

    public function test_invalid_or_missing_signature_is_rejected(): void
    {
        $this->assertNull($this->gateway->verifyWebhook($this->payload(['signature_key' => str_repeat('a', 128)])));

        $tampered = $this->payload();
        $tampered['gross_amount'] = '1.00';
        $this->assertNull($this->gateway->verifyWebhook($tampered));

        $missing = $this->payload();
        unset($missing['signature_key']);
        $this->assertNull($this->gateway->verifyWebhook($missing));
    }

    public function test_status_mapping(): void
    {
        $this->assertSame(OrderStatus::Paid, MidtransGateway::mapStatus('capture', 'accept'));
        $this->assertSame(OrderStatus::Pending, MidtransGateway::mapStatus('capture', 'challenge'));
        $this->assertSame(OrderStatus::Pending, MidtransGateway::mapStatus('pending'));
        $this->assertSame(OrderStatus::Cancelled, MidtransGateway::mapStatus('deny'));
        $this->assertSame(OrderStatus::Expired, MidtransGateway::mapStatus('expire'));
        $this->assertSame(OrderStatus::Refunded, MidtransGateway::mapStatus('refund'));
    }

    public function test_snap_params_use_integer_amounts_and_matching_expiry(): void
    {
        $order = Order::factory()->create(['quantity' => 2]);
        $params = $this->gateway->snapParams($order);

        $this->assertSame($order->code, $params['transaction_details']['order_id']);
        $this->assertSame($order->total, $params['transaction_details']['gross_amount']);
        $this->assertSame($order->total, $params['item_details'][0]['price'] * $params['item_details'][0]['quantity']);
        $this->assertSame(Order::RESERVATION_MINUTES, $params['expiry']['duration']);
        $this->assertLessThanOrEqual(50, mb_strlen($params['item_details'][0]['name']));
    }
}
