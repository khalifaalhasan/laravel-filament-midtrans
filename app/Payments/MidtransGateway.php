<?php

namespace App\Payments;

use App\Enums\OrderStatus;
use App\Models\Order;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;
use Throwable;

class MidtransGateway implements PaymentGateway
{
    public function __construct(private readonly string $serverKey, bool $isProduction)
    {
        Config::$serverKey = $serverKey;
        Config::$isProduction = $isProduction;
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function createCharge(Order $order): string
    {
        try {
            return Snap::getSnapToken($this->snapParams($order));
        } catch (Throwable $e) {
            throw new PaymentGatewayException('Gagal membuat transaksi Midtrans: '.$e->getMessage(), previous: $e);
        }
    }

    public function snapParams(Order $order): array
    {
        $order->loadMissing('items.ticketType', 'event', 'user');

        return [
            'transaction_details' => [
                'order_id' => $order->code,
                'gross_amount' => $order->total,
            ],
            'item_details' => $order->items->map(fn ($item) => [
                'id' => (string) $item->ticket_type_id,
                'price' => $item->unit_price,
                'quantity' => $item->quantity,
                'name' => mb_substr($order->event->name.' - '.$item->ticketType->name, 0, 50),
            ])->all(),
            'customer_details' => [
                'first_name' => $order->user->name,
                'email' => $order->user->email,
            ],
            // Midtrans stops accepting payment when our seat hold ends.
            'expiry' => [
                'start_time' => $order->created_at->format('Y-m-d H:i:s O'),
                'unit' => 'minute',
                'duration' => Order::RESERVATION_MINUTES,
            ],
        ];
    }

    public function verifyWebhook(array $payload): ?PaymentUpdate
    {
        foreach (['order_id', 'status_code', 'gross_amount', 'signature_key', 'transaction_status'] as $key) {
            if (! is_string($payload[$key] ?? null)) {
                return null;
            }
        }

        $expected = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].$this->serverKey);

        if (! hash_equals($expected, $payload['signature_key'])) {
            return null;
        }

        return $this->toUpdate((object) $payload);
    }

    public function getStatus(Order $order): ?PaymentUpdate
    {
        try {
            return $this->toUpdate(Transaction::status($order->code));
        } catch (Throwable $e) {
            if ($e->getCode() === 404) {
                return null;
            }

            throw new PaymentGatewayException('Gagal mengecek status Midtrans: '.$e->getMessage(), previous: $e);
        }
    }

    public function refund(Order $order, string $reason): PaymentUpdate
    {
        try {
            Transaction::refund($order->code, [
                'refund_key' => $order->code.'-refund',
                'amount' => $order->total,
                'reason' => mb_substr($reason, 0, 255),
            ]);

            // Confirm with a fresh status call rather than trusting the refund response shape.
            return $this->toUpdate(Transaction::status($order->code));
        } catch (Throwable $e) {
            throw new PaymentGatewayException('Refund Midtrans gagal: '.$e->getMessage(), previous: $e);
        }
    }

    public static function mapStatus(string $transactionStatus, ?string $fraudStatus = null): OrderStatus
    {
        return match ($transactionStatus) {
            'settlement' => OrderStatus::Paid,
            'capture' => match ($fraudStatus) {
                'accept', null => OrderStatus::Paid,
                'deny' => OrderStatus::Cancelled,
                default => OrderStatus::Pending, // challenge: wait for manual review
            },
            'deny', 'cancel', 'failure' => OrderStatus::Cancelled,
            'expire' => OrderStatus::Expired,
            'refund' => OrderStatus::Refunded,
            default => OrderStatus::Pending, // pending, authorize, partial_refund (not supported)
        };
    }

    private function toUpdate(object $data): PaymentUpdate
    {
        return new PaymentUpdate(
            orderCode: (string) $data->order_id,
            status: self::mapStatus((string) $data->transaction_status, isset($data->fraud_status) ? (string) $data->fraud_status : null),
            grossAmount: self::parseAmount((string) $data->gross_amount),
            transactionId: isset($data->transaction_id) ? (string) $data->transaction_id : null,
            transactionStatus: (string) $data->transaction_status,
            paymentType: isset($data->payment_type) ? (string) $data->payment_type : null,
        );
    }

    /** Midtrans sends "150000.00"; IDR has no minor unit, so anything but .00 is invalid. */
    private static function parseAmount(string $amount): int
    {
        if (! preg_match('/^(\d+)(?:\.00)?$/', $amount, $m)) {
            throw new PaymentGatewayException("Unexpected gross_amount: {$amount}");
        }

        return (int) $m[1];
    }
}
