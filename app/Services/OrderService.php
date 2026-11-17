<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\User;
use App\Payments\PaymentGateway;
use App\Payments\PaymentUpdate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    /** Hold seats and create a pending order. Throws ValidationException when not buyable or sold out. */
    public function reserve(User $user, TicketType $ticketType, int $quantity): Order
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah tiket minimal 1.']);
        }

        if (! $ticketType->isOnSale()) {
            throw ValidationException::withMessages(['ticket_type_id' => 'Tiket ini sedang tidak dijual.']);
        }

        return DB::transaction(function () use ($user, $ticketType, $quantity) {
            // Atomic check-and-increment: works without row locks (SQLite) and cannot oversell under concurrency.
            $reserved = TicketType::whereKey($ticketType->getKey())
                ->whereRaw('sold + ? <= quota', [$quantity])
                ->increment('sold', $quantity);

            if ($reserved === 0) {
                throw ValidationException::withMessages(['quantity' => 'Sisa kuota tidak mencukupi.']);
            }

            return Order::create([
                'code' => 'ORD-'.Str::upper(Str::ulid()),
                'user_id' => $user->getKey(),
                'event_id' => $ticketType->event_id,
                'ticket_type_id' => $ticketType->getKey(),
                'quantity' => $quantity,
                'unit_price' => $ticketType->price,
                'total' => $ticketType->price * $quantity,
                'expires_at' => now()->addMinutes(Order::RESERVATION_MINUTES),
            ]);
        });
    }

    /**
     * Move an order from one of $from to $to exactly once. Returns false if another process already moved it.
     * Releases the held seats when $to gives them back.
     */
    public function transition(Order $order, OrderStatus $to, array $from = [OrderStatus::Pending], array $attributes = []): bool
    {
        return DB::transaction(function () use ($order, $to, $from, $attributes) {
            $moved = Order::whereKey($order->getKey())
                ->whereIn('status', array_map(fn (OrderStatus $s) => $s->value, $from))
                ->update(['status' => $to->value, ...$attributes, 'updated_at' => now()]);

            if ($moved === 0) {
                return false;
            }

            if ($to->releasesQuota()) {
                TicketType::whereKey($order->ticket_type_id)->decrement('sold', $order->quantity);
            }

            $order->refresh();

            return true;
        });
    }

    /** Apply a trusted gateway status (verified webhook or server-side status call). Returns whether the order changed. */
    public function applyUpdate(PaymentUpdate $update): bool
    {
        $order = Order::firstWhere('code', $update->orderCode);

        if ($order === null) {
            Log::warning('Payment update for unknown order', ['order' => $update->orderCode]);

            return false;
        }

        if ($update->grossAmount !== $order->total) {
            Log::error('Payment amount mismatch', ['order' => $order->code, 'expected' => $order->total, 'got' => $update->grossAmount]);

            return false;
        }

        $gatewayFields = array_filter([
            'payment_type' => $update->paymentType,
            'gateway_transaction_id' => $update->transactionId,
        ]);

        $changed = match ($update->status) {
            OrderStatus::Paid => $this->transition($order, OrderStatus::Paid, attributes: ['paid_at' => now(), ...$gatewayFields]),
            OrderStatus::Expired, OrderStatus::Cancelled => $this->transition($order, $update->status, attributes: $gatewayFields),
            OrderStatus::Refunded => $this->transition($order, OrderStatus::Refunded, [OrderStatus::Paid], $gatewayFields),
            OrderStatus::Pending => false,
        };

        if (! $changed && $update->status === OrderStatus::Paid && $order->refresh()->status !== OrderStatus::Paid) {
            // shortcut: money received for an order whose seats were already released; needs manual refund until auto-handling exists
            Log::critical('Payment received for non-pending order', ['order' => $order->code, 'status' => $order->status->value]);
        }

        return $changed;
    }

    /**
     * Refund a paid order through the gateway. Returns true when the gateway already reports it refunded,
     * false when it is still processing (a later webhook finishes it). Throws PaymentGatewayException on failure.
     */
    public function refund(Order $order, string $reason): bool
    {
        // shortcut: refund is not yet in the audit log; add it with activitylog in #29
        $update = $this->gateway->refund($order, $reason);

        return $update->status === OrderStatus::Refunded && $this->applyUpdate($update);
    }
}
